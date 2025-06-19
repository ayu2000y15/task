<?php

namespace App\Http\Controllers;

use App\Models\EmailList;
use App\Models\SentEmail;   // ★ SentEmailモデルをuse
use App\Models\Subscriber;
use App\Models\BlacklistEntry;
use App\Models\EmailTemplate;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\SalesCampaignMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // ★ Storageファサードをuseする
use Illuminate\Http\JsonResponse;
use App\Models\SalesToolSetting;
use App\Models\SentEmailLog;
use Illuminate\Support\Str;
use App\Models\ManagedContact;
use Illuminate\Support\Facades\Validator; // Validator を use
use League\Csv\Reader; // league/csv を use
use League\Csv\Statement; // league/csv を use

use Maatwebsite\Excel\Facades\Excel; // ★★★ Excelファサードをuse ★★★
use App\Imports\ManagedContactsImport;

class SalesToolController extends Controller
{
    // 営業ツールへの包括的アクセス権限
    private const SALES_TOOL_ACCESS_PERMISSION = 'tools.sales.access';

    public function index(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $summary = [
            'total_email_lists' => EmailList::count(),
            'total_sent_emails_today' => SentEmail::whereDate('sent_at', today())->count(),
        ];
        return view('tools.sales.index', compact('summary'));
    }

    public function emailListsIndex(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // ★ 権限チェックを統一

        $emailLists = EmailList::withCount('subscribers')
            ->orderBy('name')->paginate(15);
        return view('tools.sales.email_lists.index', compact('emailLists'));
    }

    public function emailListsCreate(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // ★ 権限チェックを統一
        return view('tools.sales.email_lists.create');
    }

    public function emailListsStore(Request $request)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // ★ 権限チェックを統一

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:email_lists,name',
            'description' => 'nullable|string',
        ]);
        $emailList = EmailList::create($validatedData);
        return redirect()->route('tools.sales.email-lists.index')->with('success', 'メールリスト「' . $emailList->name . '」を作成しました。');
    }

    /**
     * Show the form for editing the specified email list.
     * 指定されたメールリストの編集フォームを表示します。
     */
    public function emailListsEdit(EmailList $emailList): View // ルートモデルバインディングで EmailList インスタンスを受け取る
    {
        // 営業ツール（メールリスト管理機能）へのアクセス権限をチェック
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        return view('tools.sales.email_lists.edit', compact('emailList'));
    }

    /**
     * Update the specified email list in storage.
     * 指定されたメールリストを更新します。
     * (このメソッドも今後の実装が必要です)
     */
    public function emailListsUpdate(Request $request, EmailList $emailList)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:email_lists,name,' . $emailList->id, // 更新時は自身のIDを除外
            'description' => 'nullable|string',
        ]);

        $emailList->update($validatedData);

        return redirect()->route('tools.sales.email-lists.index')->with('success', 'メールリスト「' . $emailList->name . '」を更新しました。');
    }

    /**
     * Remove the specified email list from storage.
     * 指定されたメールリストを削除します。
     * (このメソッドも今後の実装が必要です)
     */
    public function emailListsDestroy(EmailList $emailList)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $listName = $emailList->name; // 削除前に名前を取得
        $emailList->delete(); // ソフトデリートされます (EmailListモデルでSoftDeletesトレイトを使用している場合)

        return redirect()->route('tools.sales.email-lists.index')->with('success', 'メールリスト「' . $listName . '」を削除しました。');
    }

    /**
     * Display the specified email list and its subscribers.
     * 指定されたメールリストの詳細と購読者一覧を表示します。
     */
    public function emailListsShow(EmailList $emailList): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        // メールリストに紐づく購読者をページネーション付きで取得
        // ManagedContactの情報も一緒に取得 (Eager Loading)
        $subscribers = $emailList->subscribers()
            ->with('managedContact') // ★ ManagedContactをEager Load
            ->orderBy('created_at', 'desc') // Subscriberの登録日時順でソート（または他の基準）
            ->paginate(100); // 1ページあたり100件

        return view('tools.sales.email_lists.show', compact('emailList', 'subscribers'));
    }

    /**
     * Show the form for creating a new subscriber for the given email list.
     * 指定されたメールリストに新しい購読者を追加するためのフォームを表示します。
     */
    /**
     * Show the form for creating new subscribers for the given email list by selecting from ManagedContacts.
     * 指定されたメールリストに、管理連絡先から選択して新しい購読者を追加するためのフォームを表示します。
     */
    public function subscribersCreate(Request $request, EmailList $emailList)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $filterValues = $request->all();

        // --- 1. 基本クエリの生成 ---
        $query = ManagedContact::query()->where('status', 'active'); // 有効な連絡先のみ

        // --- 2. 除外リストの処理 ---
        // 自分自身のリストIDは常に対象
        $excludeListIds = [$emailList->id];
        // リクエストで追加の除外リストが指定されていればマージする
        if ($request->filled('exclude_lists') && is_array($request->input('exclude_lists'))) {
            $excludeListIds = array_merge($excludeListIds, $request->input('exclude_lists'));
        }
        // 重複を除外
        $excludeListIds = array_unique($excludeListIds);

        // 指定されたすべてのリストに存在しない連絡先を絞り込む
        $query->whereDoesntHave('subscribers', function ($q) use ($excludeListIds) {
            $q->whereIn('email_list_id', $excludeListIds);
        });


        // --- 3. ユーザーによるフィルターを適用 ---
        // キーワード検索
        if ($request->filled('keyword')) {
            $keyword = '%' . $request->input('keyword') . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', $keyword)
                    ->orWhere('name', 'like', $keyword)
                    ->orWhere('company_name', 'like', 'keyword');
            });
        }

        // --- 詳細フィルター (検索モード対応) ---
        $applyTextFilter = function ($query, $request, $fieldName) {
            $mode = $request->input("filter_{$fieldName}_mode", 'like'); // デフォルトは 'like'
            $value = $request->input("filter_{$fieldName}");
            $blankFilter = $request->input("blank_filter_{$fieldName}");

            if ($blankFilter === 'is_null') {
                $query->where(fn($q) => $q->whereNull($fieldName)->orWhere($fieldName, ''));
            } elseif ($blankFilter === 'is_not_null') {
                $query->where(fn($q) => $q->whereNotNull($fieldName)->where($fieldName, '!=', ''));
            } elseif ($request->filled("filter_{$fieldName}")) {
                switch ($mode) {
                    case 'exact':
                        $query->where($fieldName, '=', $value);
                        break;
                    case 'not_in':
                        $query->where($fieldName, 'NOT LIKE', '%' . $value . '%');
                        break;
                    case 'like':
                    default:
                        $query->where($fieldName, 'LIKE', '%' . $value . '%');
                        break;
                }
            }
        };

        // フィルターを適用するフィールドリスト
        $filterableTextFields = ['company_name', 'postal_code', 'address', 'industry', 'notes', 'source_info', 'establishment_date'];
        foreach ($filterableTextFields as $field) {
            $applyTextFilter($query, $request, $field);
        }

        // --- 4. 結果取得とビューへのデータ受け渡し ---
        $managedContacts = $query->latest('updated_at')->paginate(100);

        // 除外リスト選択用に、現在のリスト以外の全リストを取得
        $otherEmailLists = EmailList::where('id', '!=', $emailList->id)->orderBy('name')->get();

        return view('tools.sales.subscribers.create', compact(
            'emailList',
            'managedContacts',
            'filterValues',
            'otherEmailLists' // ★ビューに渡す
        ));
    }

    /**
     * Store newly created subscribers in storage for the given email list from selected ManagedContacts.
     * 選択された管理連絡先から、新しい購読者を指定されたメールリストに保存します。
     */
    public function subscribersStore(Request $request, EmailList $emailList)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $contactIdsToAdd = [];
        $isAddAllFiltered = $request->has('add_all_filtered_action');

        if ($isAddAllFiltered) {
            Log::info("Attempting to add all filtered contacts to EmailList ID: {$emailList->id}, Filters: ", $request->except(['_token', 'add_all_filtered_action']));
            $query = ManagedContact::query();

            // Keyword (from hidden input if present, or original request param)
            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function ($q) use ($keyword) {
                    if (Str::contains($keyword, ['%', '_'])) {
                        $q->where('email', 'like', $keyword)
                            ->orWhere('name', 'like', $keyword)
                            ->orWhere('company_name', 'like', $keyword);
                    } else {
                        $q->where('email', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%")
                            ->orWhere('company_name', 'like', "%{$keyword}%");
                    }
                });
            }
            // Advanced Filters (from hidden inputs or original request params)
            if ($request->filled('filter_company_name')) {
                $this->applyWildcardSearch($query, 'company_name', $request->input('filter_company_name'));
            }
            if ($request->filled('filter_postal_code')) {
                $this->applyWildcardSearch($query, 'postal_code', $request->input('filter_postal_code'));
            }
            if ($request->filled('filter_address')) {
                $this->applyWildcardSearch($query, 'address', $request->input('filter_address'));
            }
            if ($request->filled('filter_establishment_date_from')) {
                $query->whereDate('establishment_date', '>=', $request->input('filter_establishment_date_from'));
            }
            if ($request->filled('filter_establishment_date_to')) {
                $query->whereDate('establishment_date', '<=', $request->input('filter_establishment_date_to'));
            }
            if ($request->filled('filter_industry')) {
                $this->applyWildcardSearch($query, 'industry', $request->input('filter_industry'));
            }
            if ($request->filled('filter_notes')) {
                $this->applyWildcardSearch($query, 'notes', $request->input('filter_notes'));
            }

            if ($request->filled('filter_status') && $request->input('filter_status') !== '') {
                $query->where('status', $request->input('filter_status'));
            } else {
                $query->where('status', 'active');
            }

            // 既にこのEmailListに登録されているManagedContactのIDを取得
            $existingManagedContactIds = $emailList->subscribers()->pluck('managed_contact_id')->all();
            if (!empty($existingManagedContactIds)) {
                $query->whereNotIn('id', $existingManagedContactIds); // ManagedContactのIDで除外
            }

            $contactIdsToAdd = $query->pluck('id')->all();

            if (empty($contactIdsToAdd)) {
                return redirect()->route('tools.sales.email-lists.subscribers.create', $emailList)
                    ->with('info', 'フィルター条件に一致する追加可能な連絡先が見つかりませんでした。')
                    ->withInput($request->except(['add_all_filtered_action', '_token']));
            }
            Log::info(count($contactIdsToAdd) . " contacts found via 'add all filtered' for EmailList ID: {$emailList->id}. Processing...");
        } else {
            $validated = $request->validate([
                'managed_contact_ids' => 'present|array',
                'managed_contact_ids.*' => 'integer|exists:managed_contacts,id',
            ]);
            $contactIdsToAdd = $validated['managed_contact_ids'] ?? [];

            if (empty($contactIdsToAdd)) {
                return redirect()->route('tools.sales.email-lists.subscribers.create', $emailList)
                    ->with('warning', '追加する連絡先が選択されていません。')
                    ->withInput($request->except(['_token']));
            }
        }

        $addedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($contactIdsToAdd as $contactId) {
            $managedContact = ManagedContact::find($contactId);
            if (!$managedContact) {
                $errorCount++;
                Log::warning("ManagedContact ID {$contactId} not found. Skipping.");
                continue;
            }

            // 既に同じmanaged_contact_idがこのリストに存在するか確認
            $existingSubscriber = $emailList->subscribers()
                ->where('managed_contact_id', $managedContact->id)
                ->first();
            if ($existingSubscriber) {
                $skippedCount++;
                continue;
            }

            try {
                Subscriber::create([
                    'email_list_id' => $emailList->id,
                    'managed_contact_id' => $managedContact->id, // ★ ManagedContactのIDを紐付け
                    'email' => $managedContact->email, // ★ ManagedContactのemailをコピーして保持
                    'subscribed_at' => now(),
                    'status' => 'subscribed', // ★ Subscriber固有のステータス
                ]);
                $addedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Error creating subscriber from ManagedContact ID {$contactId} for EmailList ID {$emailList->id}: " . $e->getMessage(), ['exception' => $e]);
            }
        }

        $messageParts = [];
        if ($isAddAllFiltered) {
            $messageParts[] = "フィルター結果に基づき、";
        }
        if ($addedCount > 0) $messageParts[] = "{$addedCount}件の連絡先を購読者としてリストに追加しました。";
        if ($skippedCount > 0) $messageParts[] = "{$skippedCount}件は既にリストに登録済みのためスキップしました。"; // メッセージ変更
        if ($errorCount > 0) $messageParts[] = "{$errorCount}件の処理中にエラーが発生しました。詳細はログを確認してください。";

        if (empty($messageParts)) {
            $feedbackMessage = $isAddAllFiltered ? 'フィルター条件に一致する追加可能な連絡先が見つかりませんでした。' : '処理対象の連絡先がありませんでした。';
        } else {
            $feedbackMessage = implode(' ', $messageParts);
        }

        $messageType = 'info';
        if ($addedCount > 0 && $errorCount == 0) $messageType = 'success';
        elseif ($addedCount > 0) $messageType = 'success';
        elseif ($skippedCount > 0 && $errorCount == 0) $messageType = 'info';
        elseif ($errorCount > 0) $messageType = 'danger';
        else $messageType = 'warning';

        return redirect()->route('tools.sales.email-lists.show', $emailList)
            ->with($messageType, $feedbackMessage);
    }

    public function subscribersEdit(EmailList $emailList, Subscriber $subscriber): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        if ($subscriber->email_list_id !== $emailList->id) {
            abort(404);
        }
        return view('tools.sales.subscribers.edit', compact('emailList', 'subscriber'));
    }

    public function subscribersUpdate(Request $request, EmailList $emailList, Subscriber $subscriber)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        if ($subscriber->email_list_id !== $emailList->id) {
            abort(404); // 指定されたメールリストの購読者でない場合はエラー
        }

        // バリデーション対象はSubscriberモデルの$fillableに含まれるもの（主にステータス）
        $validatedData = $request->validate([
            // 'email' は基本的には変更させない方針。もし変更を許可する場合、
            // ManagedContactのemailも変更するのか、Subscriber.emailのみ変更するのかポリシーが必要。
            // ここでは、Subscriberのemailは変更不可とし、statusのみ更新可能とする。
            // 'email' => [
            //     'sometimes', // フォームに存在すればバリデーション
            //     'required',
            //     'string',
            //     'email',
            //     'max:255',
            //      // email と email_list_id の組み合わせでユニーク (自分自身は除く)
            //     Rule::unique('subscribers')->where(function ($query) use ($emailList) {
            //         return $query->where('email_list_id', $emailList->id);
            //     })->ignore($subscriber->id),
            // ],
            'status' => 'required|string|in:subscribed,unsubscribed,bounced,pending',
        ]);

        // 購読解除/再購読時の日時を更新
        if ($validatedData['status'] === 'unsubscribed' && $subscriber->status !== 'unsubscribed') {
            $validatedData['unsubscribed_at'] = now();
        } elseif ($validatedData['status'] === 'subscribed' && $subscriber->status !== 'subscribed') {
            // 再購読の場合、購読日を更新し、購読解除日をクリア
            $validatedData['subscribed_at'] = now();
            $validatedData['unsubscribed_at'] = null;
        }

        $subscriber->update($validatedData);

        // 表示用のメールアドレス (ManagedContactが存在すればそちらを優先)
        $displayEmail = $subscriber->managedContact ? $subscriber->managedContact->email : $subscriber->email;

        return redirect()->route('tools.sales.email-lists.show', $emailList)
            ->with('success', '購読者「' . $displayEmail . '」のステータスを更新しました。');
    }

    public function subscribersDestroy(EmailList $emailList, Subscriber $subscriber)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        if ($subscriber->email_list_id !== $emailList->id) {
            abort(404);
        }
        $subscriberEmail = $subscriber->email;
        $subscriber->delete();
        return redirect()->route('tools.sales.email-lists.show', $emailList)
            ->with('success', '購読者「' . $subscriberEmail . '」をリストから削除しました。');
    }

    /**
     * applyWildcardSearch (subscribersStoreで使用するヘルパーメソッド)
     * このメソッドはクラス内に定義されている想定ですが、もし未定義であれば追加してください。
     */
    private function applyWildcardSearch($query, $field, $value)
    {
        if (Str::contains($value, ['%', '_'])) {
            $query->where($field, 'like', $value);
        } else {
            $query->where($field, 'like', '%' . $value . '%');
        }
    }

    /**
     * Show the form for composing a new email.
     * 新規メール作成フォームを表示します。
     */
    public function composeEmail(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $emailLists = EmailList::orderBy('name')->pluck('name', 'id');
        $emailTemplates = EmailTemplate::orderBy('name')->pluck('name', 'id'); // ★ 追加: メールテンプレートを取得

        return view('tools.sales.emails.compose', compact('emailLists', 'emailTemplates')); // ★ emailTemplates を渡す
    }

    /**
     * Send the composed email.
     * 作成されたメールを送信（またはキューイング）します。
     */ public function sendEmail(Request $request)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $validatedData = $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string', // ここにはパーソナライズ前の本文が入る
            'email_list_id' => 'required|exists:email_lists,id',
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'required|email|max:255',
        ]);

        $emailList = EmailList::findOrFail($validatedData['email_list_id']);
        $subscribersQuery = $emailList->subscribers()->with('managedContact')->where('status', 'subscribed');

        if ($subscribersQuery->doesntExist()) {
            return redirect()->back()->withInput()->with('error', '選択されたメールリストに送信可能な購読者がいません。');
        }

        $maxEmailsPerMinute = SalesToolSetting::getSetting('max_emails_per_minute', 60);
        $delayBetweenEmails = $maxEmailsPerMinute > 0 ? max(1, round(60 / $maxEmailsPerMinute)) : 1;
        if ($maxEmailsPerMinute <= 0) {
            Log::warning('max_emails_per_minute setting is invalid or not set, using default 60 emails/min (delay 1s).');
        }

        // これらはパーソナライズ前のベースとなる件名と本文
        $baseSubject = $validatedData['subject'];
        $baseBodyHtml = $validatedData['body_html'];
        $senderEmail = $validatedData['sender_email'];
        $senderName = $validatedData['sender_name'] ?? config('mail.from.name');

        // SentEmailレコードにはパーソナライズ前の情報を保存
        $sentEmailRecord = SentEmail::create([
            'subject' => $baseSubject,
            'body_html' => $baseBodyHtml, // 元のHTML本文を保存
            'email_list_id' => $emailList->id,
            'sender_email' => $senderEmail,
            'sender_name' => $senderName,
            'sent_at' => now(),
            'status' => 'queuing',
        ]);

        // ▼▼▼ ダミー購読者を使用してプレビュー用HTMLを生成し、SentEmailレコードに保存 ▼▼▼
        try {
            // 1. ダミーのManagedContactとSubscriberオブジェクトを作成
            $dummyManagedContact = new \App\Models\ManagedContact([
                'email' => 'preview-user@example.com',
                'name' => 'プレビュー太郎',
                'company_name' => 'プレビュー株式会社',
                'postal_code' => '123-4567',
                'address' => '東京都プレビュー区プレビュー1-2-3',
                'phone_number' => '03-1234-5678',
                'fax_number' => '03-1234-5679',
                'url' => 'https://example.com/preview',
                'representative_name' => '代表 プレビュー',
                'establishment_date' => new \Carbon\Carbon('2020-01-01'),
                'industry' => '情報通信業（プレビュー）',
            ]);

            $dummySubscriber = new Subscriber([
                'email' => 'preview-user@example.com',
            ]);
            // 2. SubscriberにManagedContactリレーションを擬似的に設定
            $dummySubscriber->setRelation('managedContact', $dummyManagedContact);

            // 3. SalesCampaignMailをダミーデータでインスタンス化
            $previewMailable = new SalesCampaignMail(
                $baseSubject,
                $baseBodyHtml,
                $sentEmailRecord->id,
                $dummySubscriber->email, // Mailableにはダミーのメールアドレスを渡す
                $dummySubscriber       // ダミーのSubscriberオブジェクト
            );

            // 4. メールコンテンツのレンダリングと保存
            $mailContentObject = $previewMailable->content();
            $renderedPreviewHtml = $mailContentObject->htmlString;
            $sentEmailRecord->body_html = $renderedPreviewHtml;
            $sentEmailRecord->save();

            Log::info("ダミー購読者を使用してプレビュー用HTMLをSentEmail ID {$sentEmailRecord->id} に保存しました。");
        } catch (\Exception $e) {
            Log::error("プレビュー用HTMLの生成または保存中にエラーが発生しました (SentEmail ID: {$sentEmailRecord->id}): " . $e->getMessage(), ['exception' => $e]);
        }
        // ▲▲▲ ダミー購読者を使用したプレビュー用HTMLの生成と保存ここまで ▲▲▲

        $totalQueuedCount = 0;
        $blacklistedCount = 0;
        $failedQueueingCount = 0;
        $blacklistedEmails = BlacklistEntry::pluck('email')->all();
        $currentCumulativeDelay = 0;

        $subscribers = $subscribersQuery->get();

        foreach ($subscribers as $subscriber) {
            if (in_array($subscriber->email, $blacklistedEmails)) {
                $blacklistedCount++;
                Log::info("Mail to {$subscriber->email} skipped (blacklisted) for SentEmail ID {$sentEmailRecord->id}.");
                SentEmailLog::firstOrCreate(
                    ['sent_email_id' => $sentEmailRecord->id, 'recipient_email' => $subscriber->email],
                    [
                        'subscriber_id' => $subscriber->id,
                        'status' => 'skipped_blacklist',
                        'processed_at' => now(),
                        // message_identifier はMailable生成時に決まるので、ここでは仮のIDかnull
                        'message_identifier' => $sentEmailRecord->id . '_skipped_' . Str::uuid()->toString()
                    ]
                );
                continue;
            }

            try {

                // Mailableをインスタンス化 (ここで $subscriber を渡す)
                $mailable = new SalesCampaignMail(
                    $baseSubject,
                    $baseBodyHtml,
                    $sentEmailRecord->id,
                    $subscriber->email,
                    $subscriber // ★★★ Subscriberオブジェクトを渡す ★★★
                );

                Mail::to($subscriber->email, $subscriber->name)
                    ->later(now()->addSeconds($currentCumulativeDelay), $mailable->from($senderEmail, $senderName));

                // SentEmailLog に Mailable 内で生成された messageIdentifier を記録
                SentEmailLog::firstOrCreate(
                    [
                        'sent_email_id' => $sentEmailRecord->id,
                        'subscriber_id' => $subscriber->id,
                        'recipient_email' => $subscriber->email,
                    ],
                    [
                        'status' => 'queued',
                        'message_identifier' => $mailable->messageIdentifier, // ★ Mailableから取得
                        'original_message_id' => $mailable->messageIdentifier, // ★ 同様に設定 (バウンス処理で使うため)
                    ]
                );
                Log::info('SentEmailLog created/found in Controller for queueing:', [
                    'sent_email_id' => $sentEmailRecord->id,
                    'recipient_email' => $subscriber->email,
                    'status' => 'queued',
                    'message_identifier' => $mailable->messageIdentifier
                ]);

                $totalQueuedCount++;
                $currentCumulativeDelay += $delayBetweenEmails;
            } catch (\Exception $e) {
                $failedQueueingCount++;
                Log::error("Mail queueing failed for {$subscriber->email} for SentEmail ID {$sentEmailRecord->id}: " . $e->getMessage());
                SentEmailLog::firstOrCreate(
                    ['sent_email_id' => $sentEmailRecord->id, 'recipient_email' => $subscriber->email],
                    [
                        'subscriber_id' => $subscriber->id,
                        'status' => 'queue_failed',
                        'error_message' => Str::limit($e->getMessage(), 1000),
                        'processed_at' => now(),
                        'message_identifier' => $sentEmailRecord->id . '_qfailed_' . Str::uuid()->toString()
                    ]
                );
            }
        }

        // ... (SentEmailレコードの最終ステータス更新とリダイレクト処理は前回と同様) ...
        $actualSubscribersCount = $subscribers->count();
        // ... (ステータス更新ロジック) ...
        if ($totalQueuedCount > 0) {
            $sentEmailRecord->status = 'queued';
        } elseif ($actualSubscribersCount > 0 && $blacklistedCount === $actualSubscribersCount) {
            $sentEmailRecord->status = 'all_blacklisted';
        } elseif ($actualSubscribersCount > 0 && $failedQueueingCount === $actualSubscribersCount) {
            $sentEmailRecord->status = 'all_queue_failed';
        } elseif ($actualSubscribersCount > 0 && ($blacklistedCount + $failedQueueingCount) === $actualSubscribersCount) {
            $sentEmailRecord->status = 'all_skipped_or_failed';
        } else if ($actualSubscribersCount == 0) { // この分岐は最初の $subscribersQuery->doesntExist() で処理されるはず
            $sentEmailRecord->status = 'no_recipients';
        } else {
            $sentEmailRecord->status = 'processing_issue';
        }
        $sentEmailRecord->save();

        // ... (メッセージ作成とリダイレクト) ...
        $messageParts = [];
        if ($totalQueuedCount > 0) $messageParts[] = "{$totalQueuedCount}件のメールを送信キューに追加しました。";
        if ($blacklistedCount > 0) $messageParts[] = "{$blacklistedCount}件がブラックリストのためスキップ。";
        if ($failedQueueingCount > 0) $messageParts[] = "{$failedQueueingCount}件がキュー投入失敗。";
        // ...
        $finalMessage = implode(' ', $messageParts);
        if ($totalQueuedCount > 0) $finalMessage .= " 設定された間隔で順次送信されます。";
        elseif (empty($finalMessage)) $finalMessage = "メール送信処理は実行されましたが、キューに追加されたメールはありませんでした。";


        return redirect()->route('tools.sales.index')->with('success', $finalMessage);
    }


    /**
     * Display a listing of the blacklisted email addresses.
     * ブラックリストに登録されたメールアドレスの一覧を表示します。
     */
    public function blacklistIndex(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // またはブラックリスト管理専用の権限

        $blacklistedEmails = BlacklistEntry::orderBy('email')->paginate(20); // 1ページあたり20件

        return view('tools.sales.blacklist.index', compact('blacklistedEmails'));
    }

    /**
     * Store a newly created email address in the blacklist.
     * 新しいメールアドレスをブラックリストに登録します。
     */
    public function blacklistStore(Request $request)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // またはブラックリスト管理専用の権限

        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255|unique:blacklist_entries,email',
            'reason' => 'nullable|string|max:1000',
        ]);

        BlacklistEntry::create([
            'email' => $validatedData['email'],
            'reason' => $validatedData['reason'],
            'added_by_user_id' => Auth::id(), // 登録したユーザーIDを記録
        ]);

        return redirect()->route('tools.sales.blacklist.index')
            ->with('success', 'メールアドレス「' . $validatedData['email'] . '」をブラックリストに登録しました。');
    }

    /**
     * Remove the specified email address from the blacklist.
     * 指定されたメールアドレスをブラックリストから削除します。
     */
    public function blacklistDestroy(BlacklistEntry $blacklistEntry) // ルートモデルバインディングを利用
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // またはブラックリスト管理専用の権限

        $email = $blacklistEntry->email;
        $blacklistEntry->delete();

        return redirect()->route('tools.sales.blacklist.index')
            ->with('success', 'メールアドレス「' . $email . '」をブラックリストから削除しました。');
    }

    /**
     * Handle image uploads from TinyMCE editor.
     * TinyMCEエディタからの画像アップロードを処理します。
     */
    public function uploadImageForTinyMCE(Request $request): JsonResponse
    {
        try {
            $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                // errorキーの値をオブジェクトに変更して、TinyMCEが期待する可能性のある形式に近づける
                return response()->json(['error' => ['message' => '無効なファイルがアップロードされました。']], 400);
            }

            // バリデーションルールに webp を追加、ファイル名が安全であることを確認
            $validatedData = $request->validate([
                'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // 2MBまでの画像ファイル
            ]);

            $file = $validatedData['file']; // バリデーション済みのファイルを取得

            // ファイルを 'public' ディスクの 'editor_images' ディレクトリに保存
            // store() メソッドはユニークなファイル名を自動生成します
            $path = $file->store('editor_images', 'public');

            if (!$path) {
                // store() が false を返した場合 (通常は例外がスローされるが念のため)
                Log::error('TinyMCE image upload: File storage failed but store() did not throw an exception. File: ' . $file->getClientOriginalName());
                return response()->json(['error' => ['message' => 'ファイルの保存に失敗しました。サーバーログを確認してください。']], 500);
            }

            $url = Storage::disk('public')->url($path);
            Log::info('ファイルURL：' . $url . '' . $file->getClientOriginalName());
            // TinyMCEが期待するJSONレスポンス形式: { location: "URL" }
            return response()->json(['location' => $url]);
        } catch (ValidationException $e) {
            // バリデーション例外をキャッチしてJSONで返す
            Log::warning('TinyMCE image upload validation failed: ', $e->errors());
            return response()->json(['error' => ['message' => 'バリデーションエラー: ' . $e->getMessage(), 'details' => $e->errors()]], 422);
        } catch (\Throwable $e) { // その他のすべての例外 (Exception と Error) をキャッチ
            // 詳細なエラー情報をログに出力
            Log::error('TinyMCE image upload critical error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString() // スタックトレース全体
            ]);
            // クライアントには汎用的なエラーメッセージを返す
            return response()->json(['error' => ['message' => '画像のアップロード中に予期せぬサーバーエラーが発生しました。管理者に連絡してください。']], 500);
        }
    }

    /**
     * Show the form for editing the sales tool settings.
     * 営業ツールの設定編集フォームを表示します。
     */
    public function settingsEdit(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // または設定管理専用の権限

        // sales_tool_settings テーブルには通常1レコードのみ存在すると想定
        // firstOrCreate を使用して、レコードがなければデフォルト値で作成し、あればそれを取得
        $settings = SalesToolSetting::firstOrCreate(
            ['id' => 1], // 常にID=1のレコードを対象とする (または他のユニークなキー)
            [ // レコードが存在しない場合に作成されるデフォルト値
                'send_interval_minutes' => 5,
                'emails_per_batch' => 100,
                'batch_delay_seconds' => 60,
                'image_sending_enabled' => true,
            ]
        );

        return view('tools.sales.settings.edit', compact('settings'));
    }

    /**
     * Update the sales tool settings in storage.
     * 営業ツールの設定を更新します。
     */
    public function settingsUpdate(Request $request)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $validatedData = $request->validate([
            'max_emails_per_minute' => 'required|integer|min:1', // ★ バリデーション対象
            'image_sending_enabled' => 'nullable|boolean',
            // 不要になった古いレート制御カラムのバリデーションは削除
        ]);

        $validatedData['image_sending_enabled'] = $request->has('image_sending_enabled');

        // SalesToolSettingモデルのupdateSettingsメソッドまたはfirstOrCreate()->update()で保存
        $settings = SalesToolSetting::updateOrCreate( // もしモデルにupdateSettingsがなければ
            ['id' => 1],                             // このように直接更新
            [ // デフォルト値 (レコードがない場合)
                'max_emails_per_minute' => $validatedData['max_emails_per_minute'] ?? 60,
                'image_sending_enabled' => $validatedData['image_sending_enabled'] ?? true,
            ]
        );
        // 既にレコードがある場合は update のみ
        // $settings = SalesToolSetting::firstOrCreate(['id' => 1]);
        // $settings->fill($validatedData);
        // $settings->save();


        return redirect()->route('tools.sales.settings.edit')
            ->with('success', '営業ツールの設定を更新しました。');
    }

    /**
     * Display a listing of sent (or currently processing) emails.
     * 送信済み（または処理中）のメール一覧を表示します。
     */
    public function sentEmailsIndex(Request $request): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $query = SentEmail::with(['emailList', 'recipientLogs'])->orderBy('sent_at', 'desc');
        // $query = SentEmail::with('emailList')
        //     ->withCount([
        //         'recipientLogs as total_recipients_count',
        //         'recipientLogs as successful_sends_count' => function ($q) {
        //             $q->where('status', 'sent');
        //         },
        //         'recipientLogs as failed_sends_count' => function ($q) {
        //             $q->whereIn('status', ['failed', 'bounced', 'queue_failed']);
        //         },
        //         'recipientLogs as skipped_blacklist_count' => function ($q) {
        //             $q->where('status', 'skipped_blacklist');
        //         },
        //         'recipientLogs as still_queued_count' => function ($q) {
        //             $q->where('status', 'queued');
        //         } // ★ 追加
        //     ])
        //     ->orderBy('sent_at', 'desc');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('subject', 'like', "%{$keyword}%")
                    ->orWhereHas('emailList', function ($q_list) use ($keyword) {
                        $q_list->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        $sentEmails = $query->paginate(15)->appends($request->except('page'));

        return view('tools.sales.emails.sent.index', compact('sentEmails'));
    }

    /**
     * Display the specified sent email and its recipient logs.
     * 指定された送信メールの詳細と、各受信者への送信ログを表示します。
     */
    public function sentEmailsShow(SentEmail $sentEmail): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $recipientLogs = $sentEmail->recipientLogs()
            ->with('subscriber')
            ->orderBy('created_at', 'desc')
            ->paginate(25);
        $summary = [
            'total' => $sentEmail->recipientLogs()->count(),
            'sent' => $sentEmail->recipientLogs()->where('status', 'sent')->count(),
            'failed' => $sentEmail->recipientLogs()->whereIn('status', ['failed', 'bounced', 'queue_failed'])->count(),
            'queued' => $sentEmail->recipientLogs()->where('status', 'queued')->count(),
            'skipped_blacklist' => $sentEmail->recipientLogs()->where('status', 'skipped_blacklist')->count(),
        ];

        return view('tools.sales.emails.sent.show', compact('sentEmail', 'recipientLogs', 'summary'));
    }

    /**
     * Display a listing of the managed contacts.
     * 管理連絡先の一覧を表示します。
     */
    public function managedContactsIndex(Request $request)
    {
        $query = ManagedContact::query();
        $filterValues = $request->all();

        // キーワード検索
        if ($request->filled('keyword')) {
            $keyword = '%' . $request->input('keyword') . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', $keyword)
                    ->orWhere('name', 'like', $keyword)
                    ->orWhere('company_name', 'like', $keyword);
            });
        }

        // --- ▼▼▼【ここから詳細フィルターのロジックを修正】▼▼▼ ---

        // 各フィルター項目を動的に処理するヘルパー関数
        $applyTextFilter = function ($query, $request, $fieldName) {
            $blankFilter = $request->input("blank_filter_{$fieldName}");
            $textValue = $request->input("filter_{$fieldName}");

            if ($blankFilter === 'is_null') {
                $query->where(fn($q) => $q->whereNull($fieldName)->orWhere($fieldName, ''));
            } elseif ($blankFilter === 'is_not_null') {
                $query->where(fn($q) => $q->whereNotNull($fieldName)->where($fieldName, '!=', ''));
            } elseif ($request->filled("filter_{$fieldName}")) {
                $query->where($fieldName, 'like', '%' . $textValue . '%');
            }
        };

        // ヘルパー関数を使って各フィルターを適用
        $applyTextFilter($query, $request, 'company_name');
        $applyTextFilter($query, $request, 'postal_code');
        $applyTextFilter($query, $request, 'address');
        $applyTextFilter($query, $request, 'industry');
        $applyTextFilter($query, $request, 'notes');
        $applyTextFilter($query, $request, 'establishment_date');

        // --- ▲▲▲【ここまで詳細フィルターのロジックを修正】▲▲▲ ---

        // ステータス
        if ($request->filled('filter_status')) {
            $query->where('status', $request->input('filter_status'));
        }

        $managedContacts = $query->latest('updated_at')->paginate(15);

        $statusOptions = ManagedContact::STATUS_OPTIONS ?? [];

        return view('tools.sales.managed_contacts.index', compact(
            'managedContacts',
            'filterValues',
            'statusOptions'
        ));
    }

    /**
     * Show the form for creating a new managed contact.
     * 新規管理連絡先の作成フォームを表示します。
     */
    public function managedContactsCreate(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        return view('tools.sales.managed_contacts.create');
    }

    /**
     * Store a newly created managed contact in storage.
     * 新規管理連絡先を保存します。
     */
    public function managedContactsStore(Request $request)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255|unique:managed_contacts,email',
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'phone_number' => 'nullable|string|max:30',
            'fax_number' => 'nullable|string|max:30',
            'url' => 'nullable|string|url|max:255',
            'representative_name' => 'nullable|string|max:255',
            'establishment_date' => 'nullable|string|max:255', // dateフォーマット指定
            'industry' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:active,do_not_contact,archived', // 適切なステータス値
        ]);
        if (empty($validatedData['status'])) {
            $validatedData['status'] = 'active';
        }
        if (isset($validatedData['establishment_date']) && $validatedData['establishment_date'] === '') {
            $validatedData['establishment_date'] = null;
        }

        // ★★★ 登録元情報を追加 ★★★
        $validatedData['source_info'] = '手入力: ' . Auth::user()->name;

        $managedContact = ManagedContact::create($validatedData);

        return redirect()->route('tools.sales.managed-contacts.index')
            ->with('success', '管理連絡先「' . $managedContact->email . '」を作成しました。');
    }

    /**
     * Show the form for editing the specified managed contact.
     * 指定された管理連絡先の編集フォームを表示します。
     */
    public function managedContactsEdit(ManagedContact $managedContact): View // ルートモデルバインディング
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        return view('tools.sales.managed_contacts.edit', compact('managedContact'));
    }

    /**
     * Update the specified managed contact in storage.
     * 指定された管理連絡先を更新します。
     */
    public function managedContactsUpdate(Request $request, ManagedContact $managedContact)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255|unique:managed_contacts,email,' . $managedContact->id,
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'phone_number' => 'nullable|string|max:30',
            'fax_number' => 'nullable|string|max:30',
            'url' => 'nullable|string|url|max:255',
            'representative_name' => 'nullable|string|max:255',
            'establishment_date' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|string|in:active,do_not_contact,archived',
        ]);
        if (isset($validatedData['establishment_date']) && $validatedData['establishment_date'] === '') {
            $validatedData['establishment_date'] = null;
        }

        // ★★★ 更新元情報を追加 ★★★
        $validatedData['source_info'] = '手入力(更新): ' . Auth::user()->name;

        $managedContact->update($validatedData);

        return redirect()->route('tools.sales.managed-contacts.index')
            ->with('success', '管理連絡先「' . $managedContact->email . '」を更新しました。');
    }

    /**
     * Remove the specified managed contact from storage.
     * 指定された管理連絡先を削除します。
     */
    public function managedContactsDestroy(ManagedContact $managedContact)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        $email = $managedContact->email;
        // TODO: この連絡先がメールリストのSubscriberとして使用されている場合の考慮が必要か？
        // (アプローチAでは、Subscriberはコピーなので、ManagedContactを削除してもSubscriberは残る)
        // ソフトデリートをManagedContactモデルで有効にしている場合は、$managedContact->delete()でソフトデリート。
        $managedContact->delete();

        return redirect()->route('tools.sales.managed-contacts.index')
            ->with('success', '管理連絡先「' . $email . '」を削除しました。');
    }

    /**
     * CSVまたはExcelファイルから管理連絡先をインポートします。
     * (managedContactsImportCsvメソッドを置き換える新しいメソッド)
     */
    public function importContacts(Request $request)
    {
        @set_time_limit(180);
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx|max:8192', // ★ xlsxを追加
        ]);

        $file = $request->file('csv_file');
        $originalFileName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        // ヘッダーのマッピング定義
        $columnMappings = [
            'email' => ['メールアドレス', 'Email', 'email_address', 'メール'],
            'name' => ['名前', '氏名', 'Name', 'フルネーム'],
            'company_name' => ['会社名', 'Company Name', '法人名', '所属'],
            'postal_code' => ['郵便番号', 'Postal Code', '郵便'],
            'address' => ['住所', 'Address'],
            'phone_number' => ['電話番号', 'Phone Number', '電話'],
            'fax_number' => ['FAX番号', 'FAX Number', 'FAX'],
            'url' => ['URL', 'Website', 'ウェブサイト'],
            'representative_name' => ['代表者名', 'Representative Name', '代表'],
            'establishment_date' => ['設立年月日', 'Establishment Date', '設立日'],
            'industry' => ['業種', 'Industry'],
            'notes' => ['備考', 'Notes', 'メモ'],
            'status' => ['ステータス', 'Status'],
        ];

        // ファイルタイプに応じて処理を分岐
        if (in_array($extension, ['csv', 'txt'])) {
            $records = $this->readCsvRecords($file, $columnMappings);
        } elseif ($extension === 'xlsx') {
            $records = $this->readXlsxRecords($file, $columnMappings);
        } else {
            return redirect()->back()->with('error', '未対応のファイル形式です。');
        }

        if (is_string($records)) { // エラーメッセージが返された場合
            return redirect()->back()->with('error', $records);
        }

        // データベースへの登録処理
        list($importedCount, $updatedCount, $failedCount) = $this->processImportRecords($records, $originalFileName);

        // 結果メッセージの生成とリダイレクト
        $message = "インポート処理が完了しました（{$originalFileName}）。";
        $message .= " {$importedCount}件を新規登録、{$updatedCount}件を更新しました。";
        $messageType = 'success';
        if ($failedCount > 0) {
            $messageType = 'warning';
            $message .= " {$failedCount}件の処理に失敗しました（詳細はログを確認）。";
        }

        return redirect()->route('tools.sales.managed-contacts.index')->with($messageType, $message);
    }

    /**
     * ヘルパー：CSVファイルを読み込んで整形されたレコード配列を返す
     */
    private function readCsvRecords($file, $columnMappings)
    {
        try {
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0);
            $header = array_map('trim', $csv->getHeader());
            $recordsIterator = Statement::create()->process($csv);
        } catch (\Exception $e) {
            Log::error('Import Error - CSV Read: ' . $e->getMessage());
            return 'CSVファイルの読み込みに失敗しました。';
        }

        $headerToModelMap = $this->mapHeaderToModel($header, $columnMappings);
        if (empty($headerToModelMap['email'])) {
            return '必須のヘッダー「メールアドレス」が見つかりません。';
        }

        $records = [];
        foreach ($recordsIterator as $record) {
            $rowData = [];
            foreach ($headerToModelMap as $modelAttribute => $headerName) {
                $rowData[$modelAttribute] = $record[$headerName] ?? null;
            }
            $records[] = $rowData;
        }
        return $records;
    }

    /**
     * ヘルパー：XLSXファイルを読み込んで整形されたレコード配列を返す
     */
    private function readXlsxRecords($file, $columnMappings)
    {
        try {
            $rows = Excel::toCollection(new \stdClass(), $file)[0]; // 最初のシートのみ
            if ($rows->isEmpty()) {
                return 'Excelファイルが空です。';
            }
            $header = $rows->first()->map(fn($cell) => trim($cell))->toArray();
            $dataRows = $rows->slice(1);
        } catch (\Exception $e) {
            Log::error('Import Error - XLSX Read: ' . $e->getMessage());
            return 'Excelファイルの読み込みに失敗しました。';
        }

        $headerToModelMap = $this->mapHeaderToModel($header, $columnMappings);
        if (empty($headerToModelMap['email'])) {
            return '必須のヘッダー「メールアドレス」が見つかりません。';
        }

        $records = [];
        foreach ($dataRows as $row) {
            $rowData = [];
            foreach ($headerToModelMap as $modelAttribute => $headerName) {
                $colIndex = array_search($headerName, $header);
                $rowData[$modelAttribute] = $row[$colIndex] ?? null;
            }
            $records[] = $rowData;
        }
        return $records;
    }

    /**
     * ヘルパー：CSV/Excelのヘッダーとモデルの属性をマッピングする
     */
    private function mapHeaderToModel($header, $columnMappings)
    {
        $map = [];
        foreach ($columnMappings as $modelAttribute => $possibleHeaders) {
            foreach ($possibleHeaders as $possibleHeader) {
                if (in_array($possibleHeader, $header)) {
                    $map[$modelAttribute] = $possibleHeader;
                    break;
                }
            }
        }
        return $map;
    }

    /**
     * ヘルパー：整形されたレコードをデータベースに登録・更新する
     */
    private function processImportRecords($records, $sourceFileName)
    {
        $imported = 0;
        $updated = 0;
        $failed = 0;

        foreach ($records as $record) {
            $rowData = array_map(fn($value) => is_string($value) ? trim($value) : $value, $record);

            $validator = Validator::make($rowData, [
                'email' => 'required|email|max:255',
                'name' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:1000',
                'phone_number' => 'nullable|string|max:30',
                'fax_number' => 'nullable|string|max:30',
                'url' => 'nullable|string|url|max:255',
                'representative_name' => 'nullable|string|max:255',
                'establishment_date' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $failed++;
                Log::warning('Import Validation Failed:', ['data' => $rowData, 'errors' => $validator->errors()->all()]);
                continue;
            }

            $dataToUpsert = $validator->validated();
            $dataToUpsert['source_info'] = "インポート: {$sourceFileName}";

            if (empty($dataToUpsert['status'])) $dataToUpsert['status'] = 'active';

            if (!empty($dataToUpsert['establishment_date'])) {
                try {
                    $dataToUpsert['establishment_date'] = \Carbon\Carbon::parse($dataToUpsert['establishment_date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $dataToUpsert['establishment_date'] = null;
                }
            }

            try {
                $contact = ManagedContact::updateOrCreate(
                    ['email' => $dataToUpsert['email']],
                    $dataToUpsert
                );

                if ($contact->wasRecentlyCreated) $imported++;
                elseif ($contact->wasChanged()) $updated++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Import DB Error:', ['email' => $dataToUpsert['email'], 'error' => $e->getMessage()]);
            }
        }
        return [$imported, $updated, $failed];
    }

    /**
     * Display a listing of the email templates.
     */
    public function templatesIndex(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // または専用権限

        $templates = EmailTemplate::orderBy('name')->paginate(15);
        return view('tools.sales.templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new email template.
     */
    public function templatesCreate(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        return view('tools.sales.templates.create');
    }

    /**
     * Store a newly created email template in storage.
     */
    public function templatesStore(Request $request)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:email_templates,name',
            'subject' => 'nullable|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
        ]);

        $validatedData['created_by_user_id'] = Auth::id();
        $template = EmailTemplate::create($validatedData);

        return redirect()->route('tools.sales.templates.index')
            ->with('success', 'メールテンプレート「' . $template->name . '」を作成しました。');
    }

    /**
     * Show the form for editing the specified email template.
     */
    public function templatesEdit(EmailTemplate $template): View // ルートモデルバインディング
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        return view('tools.sales.templates.edit', compact('template'));
    }

    /**
     * Update the specified email template in storage.
     */
    public function templatesUpdate(Request $request, EmailTemplate $template)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:email_templates,name,' . $template->id,
            'subject' => 'nullable|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
        ]);

        $template->update($validatedData);

        return redirect()->route('tools.sales.templates.index')
            ->with('success', 'メールテンプレート「' . $template->name . '」を更新しました。');
    }

    /**
     * Remove the specified email template from storage.
     */
    public function templatesDestroy(EmailTemplate $template)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        $templateName = $template->name;
        $template->delete(); // ソフトデリート
        return redirect()->route('tools.sales.templates.index')
            ->with('success', 'メールテンプレート「' . $templateName . '」を削除しました。');
    }

    /**
     * Get content of a specific email template (for AJAX calls from compose screen).
     * 特定のメールテンプレートの内容をJSONで返します（メール作成画面からのAJAX呼び出し用）。
     */
    public function getEmailTemplateContent(EmailTemplate $template): JsonResponse
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        return response()->json([
            'subject' => $template->subject,
            'body_html' => $template->body_html,
            'body_text' => $template->body_text,
        ]);
    }

    /**
     * メールアドレスの重複をAJAXでチェック
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['exists' => false, 'message' => '無効なメールアドレスです。']);
        }

        $exists = ManagedContact::where('email', $request->input('email'))->exists();

        return response()->json(['exists' => $exists]);
    }
}
