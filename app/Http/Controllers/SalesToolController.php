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
use Illuminate\Pagination\LengthAwarePaginator; // ★ 追加
use Illuminate\Pagination\Paginator;

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
     * Show the form for creating new subscribers for the given email list by selecting from ManagedContacts.
     * 指定されたメールリストに、管理連絡先から選択して新しい購読者を追加するためのフォームを表示します。
     */
    public function subscribersCreate(Request $request, EmailList $emailList)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $filterValues = $request->all();

        // 1. 共通のヘルパーメソッドを使ってクエリを構築
        $query = $this->buildFilteredContactsQuery($request, $emailList);

        // 2. 最大検索結果数をリクエストから取得
        $searchLimit = (int) $request->input('limit', 1000);
        // 安全のため、上限と下限を設定
        if ($searchLimit < 10) $searchLimit = 10;
        if ($searchLimit > 13000) $searchLimit = 13000;

        // 3. クエリに最大件数の制限を適用し、結果を一度に取得
        $contactsCollection = $query->latest('updated_at')->limit($searchLimit)->get();

        // 4. 取得したコレクションから手動でページネーションを作成
        $perPage = 100; // 1ページあたりの表示件数（固定）
        $currentPage = Paginator::resolveCurrentPage('page');

        $currentPageItems = $contactsCollection->slice(($currentPage - 1) * $perPage, $perPage);

        $managedContacts = new LengthAwarePaginator(
            $currentPageItems,
            $contactsCollection->count(), // 総件数は取得したコレクションの件数
            $perPage,
            $currentPage,
            // ページネーションリンクがフィルター条件を維持するように設定
            ['path' => Paginator::resolveCurrentPath()]
        );

        // 除外リスト選択用に、現在のリスト以外の全リストを取得
        $otherEmailLists = EmailList::where('id', '!=', $emailList->id)->orderBy('name')->get();

        return view('tools.sales.subscribers.create', compact(
            'emailList',
            'managedContacts',
            'filterValues',
            'otherEmailLists'
        ));
    }

    /**
     * Store newly created subscribers in storage for the given email list from selected ManagedContacts.
     * 選択された管理連絡先から、新しい購読者を指定されたメールリストに保存します。
     */ public function subscribersStore(Request $request, EmailList $emailList)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $dailyLimit = SalesToolSetting::getSetting('daily_send_limit', 10000);
        $currentSubscribersCount = $emailList->subscribers()->count();
        $contactIdsToAdd = [];
        $isAddAllFiltered = $request->has('add_all_filtered_action');

        if ($isAddAllFiltered) {
            // 「フィルター結果を全て追加」の場合、追加される件数を事前に計算
            $query = $this->buildFilteredContactsQuery($request, $emailList);

            $limit = (int) $request->input('limit', 13000); // デフォルトは多めに設定
            if ($limit < 10) $limit = 10;
            if ($limit > 13000) $limit = 13000;

            // クエリに最大件数と順序を適用してからカウント
            $countToAdd = $query->latest('updated_at')->limit($limit)->pluck('id')->count();
        } else {
            // 「チェックした連絡先を追加」の場合
            $validated = $request->validate([
                'managed_contact_ids' => 'present|array',
                'managed_contact_ids.*' => 'integer|exists:managed_contacts,id',
            ]);
            $contactIdsToAdd = $validated['managed_contact_ids'] ?? [];
            $countToAdd = count($contactIdsToAdd);
        }

        if (($currentSubscribersCount + $countToAdd) > $dailyLimit) {
            $message = "リストの上限エラー: この操作を行うと、リストの購読者数が1日の最大送信数 ({$dailyLimit}件) を超えてしまいます。";
            $message .= " (現在の購読者数: {$currentSubscribersCount}件 / 追加しようとした件数: {$countToAdd}件)";

            Log::warning("Subscriber addition blocked for EmailList ID {$emailList->id}: Daily limit exceeded.", [
                'daily_limit' => $dailyLimit,
                'current_count' => $currentSubscribersCount,
                'to_add_count' => $countToAdd,
            ]);

            return redirect()->back()
                ->with('error', $message)
                ->withInput($request->except(['_token']));
        }


        // 「フィルター結果を全て追加」の場合のIDリスト取得
        if ($isAddAllFiltered) {
            $query = $this->buildFilteredContactsQuery($request, $emailList);

            // ★★★ ここにも同様に最大件数を適用するロジックを追加 ★★★
            $limit = (int) $request->input('limit', 13000);
            if ($limit < 10) $limit = 10;
            if ($limit > 13000) $limit = 13000;

            // クエリに最大件数と順序を適用してからIDを取得
            $contactIdsToAdd = $query->latest('updated_at')->limit($limit)->pluck('id')->all();
        }

        if (empty($contactIdsToAdd)) {
            $redirectRoute = $isAddAllFiltered ?
                redirect()->route('tools.sales.email-lists.subscribers.create', $emailList)->withInput($request->except(['add_all_filtered_action', '_token'])) :
                redirect()->route('tools.sales.email-lists.subscribers.create', $emailList)->withInput($request->except(['_token']));

            return $redirectRoute->with('warning', '追加する連絡先が見つかりませんでした。');
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
                    'managed_contact_id' => $managedContact->id,
                    'email' => $managedContact->email,
                    'subscribed_at' => now(),
                    'status' => 'subscribed',
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
        if ($skippedCount > 0) $messageParts[] = "{$skippedCount}件は既にリストに登録済みのためスキップしました。";
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


    /**
     * ★★★ 新しいヘルパーメソッドを追加 ★★★
     * フィルター条件に基づいて連絡先を取得するクエリを構築します。
     * (subscribersStoreメソッド内で重複していたロジックを共通化)
     */
    private function buildFilteredContactsQuery(Request $request, EmailList $emailList)
    {
        $query = ManagedContact::query();

        // --- 1. 除外リストの処理 ---
        $excludeListIds = [$emailList->id];
        if ($request->filled('exclude_lists') && is_array($request->input('exclude_lists'))) {
            $excludeListIds = array_merge($excludeListIds, $request->input('exclude_lists'));
        }
        $excludeListIds = array_unique($excludeListIds);
        $query->whereDoesntHave('subscribers', function ($q) use ($excludeListIds) {
            $q->whereIn('email_list_id', $excludeListIds);
        });

        // --- 2. 各フィルターの適用 ---
        // キーワード検索
        if ($request->filled('keyword')) {
            $keyword = '%' . $request->input('keyword') . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', $keyword)
                    ->orWhere('name', 'like', $keyword)
                    ->orWhere('company_name', 'like', 'keyword');
            });
        }

        // 詳細フィルター
        $applyTextFilter = function ($query, $request, $fieldName) {
            $mode = $request->input("filter_{$fieldName}_mode", 'like');
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

        $filterableTextFields = ['company_name', 'postal_code', 'address', 'industry', 'notes', 'source_info', 'establishment_date'];
        foreach ($filterableTextFields as $field) {
            $applyTextFilter($query, $request, $field);
        }

        // ステータスフィルター
        if ($request->filled('filter_status') && $request->input('filter_status') !== '') {
            $query->where('status', $request->input('filter_status'));
        } else {
            $query->where('status', 'active');
        }

        return $query;
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
     * Remove all subscribers from the specified email list.
     * 指定されたメールリストからすべての購読者を削除します。
     */
    public function subscribersDestroyAll(EmailList $emailList)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        try {
            // 購読者の数を取得
            $count = $emailList->subscribers()->count();

            // 購読者をすべて削除
            $emailList->subscribers()->delete();

            return redirect()->route('tools.sales.email-lists.index')
                ->with('success', 'メールリスト「' . $emailList->name . '」から ' . $count . ' 件の購読者をすべて削除しました。');
        } catch (\Exception $e) {
            Log::error("Error deleting all subscribers from EmailList ID {$emailList->id}: " . $e->getMessage());
            return redirect()->route('tools.sales.email-lists.index')
                ->with('danger', '購読者の一括削除中にエラーが発生しました。');
        }
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
     */
    /**
     * Send the composed email.
     * 作成されたメールを送信（またはキューイング）します。
     */
    public function sendEmail(Request $request)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $validatedData = $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'email_list_id' => 'required|exists:email_lists,id',
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'required|email|max:255',
        ]);

        $emailList = EmailList::findOrFail($validatedData['email_list_id']);
        $subscribersQuery = $emailList->subscribers()->with('managedContact')->where('status', 'subscribed');

        if ($subscribersQuery->doesntExist()) {
            return redirect()->back()->withInput()->with('error', '選択されたメールリストに送信可能な購読者がいません。');
        }

        // ★★★ ここから送信制御ロジックを大幅に変更 ★★★
        $settings = SalesToolSetting::first();
        if (!$settings) {
            // 設定が存在しない場合はフォールバックする（本番ではありえないが念のため）
            $settings = new SalesToolSetting([
                'daily_send_limit' => 10000,
                'send_timing_type' => 'fixed',
                'max_emails_per_minute' => 60,
            ]);
        }

        // // 1. 1日の送信上限チェック
        // $dailyLimit = $settings->daily_send_limit;
        // // 今日キューに追加されたメールの数をカウント
        // $todayQueuedCount = SentEmailLog::whereDate('created_at', today())->count();
        // $subscribersToQueueCount = $subscribersQuery->clone()->count(); // これからキューに入れる数

        // if (($todayQueuedCount + $subscribersToQueueCount) > $dailyLimit) {
        //     $message = "本日の送信上限 ({$dailyLimit}件) を超過するため、処理を中断しました。";
        //     $message .= " (本日キュー投入済: {$todayQueuedCount}件 / 今回の対象: {$subscribersToQueueCount}件)";
        //     return redirect()->back()->withInput()->with('error', $message);
        // }

        // 2. 送信間隔の計算準備
        $currentCumulativeDelayInMicroseconds = 0;
        if ($settings->send_timing_type === 'fixed') {
            $maxEmailsPerMinute = $settings->max_emails_per_minute > 0 ? $settings->max_emails_per_minute : 60;
            // 固定間隔をマイクロ秒で算出
            $delayBetweenEmailsInMicroseconds = (60 / $maxEmailsPerMinute) * 1000000;
        } else { // 'random'
            $minSeconds = $settings->random_send_min_seconds ?? 2;
            $maxSeconds = $settings->random_send_max_seconds ?? 10;
            // 最小値が最大値を超えないように補正
            if ($minSeconds > $maxSeconds) $minSeconds = $maxSeconds;
        }
        // ★★★ ここまで送信制御ロジック ★★★

        $baseSubject = $validatedData['subject'];
        $baseBodyHtml = $validatedData['body_html'];
        $senderEmail = $validatedData['sender_email'];
        $senderName = $validatedData['sender_name'] ?? config('mail.from.name');

        $sentEmailRecord = SentEmail::create([
            'subject' => $baseSubject,
            'body_html' => $baseBodyHtml,
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
                // ★★★ 遅延時間を計算してキューに投入 ★★★
                if ($settings->send_timing_type === 'fixed') {
                    $currentCumulativeDelayInMicroseconds += $delayBetweenEmailsInMicroseconds;
                } else { // random
                    // ループごとにランダムな遅延時間を加算
                    $currentCumulativeDelayInMicroseconds += rand($minSeconds * 1000000, $maxSeconds * 1000000);
                }

                $mailable = new SalesCampaignMail(
                    $baseSubject,
                    $baseBodyHtml,
                    $sentEmailRecord->id,
                    $subscriber->email,
                    $subscriber
                );

                // `later` にマイクロ秒単位で計算した遅延時間を渡す
                Mail::to($subscriber->email, $subscriber->name)
                    ->later(
                        now()->addMicroseconds($currentCumulativeDelayInMicroseconds),
                        $mailable->from($senderEmail, $senderName)
                    );

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
                // $currentCumulativeDelay は使わなくなったので削除
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

        $actualSubscribersCount = $subscribers->count();
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

        $messageParts = [];
        if ($totalQueuedCount > 0) $messageParts[] = "{$totalQueuedCount}件のメールを送信キューに追加しました。";
        if ($blacklistedCount > 0) $messageParts[] = "{$blacklistedCount}件がブラックリストのためスキップ。";
        if ($failedQueueingCount > 0) $messageParts[] = "{$failedQueueingCount}件がキュー投入失敗。";
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

        $blacklistedEmails = BlacklistEntry::orderBy('updated_at')->paginate(100); // 1ページあたり20件

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
            ['id' => 1],
            [
                'max_emails_per_minute' => 60,
                'image_sending_enabled' => true,
                'send_timing_type'      => 'fixed', // デフォルトは固定
                'random_send_min_seconds' => 2,
                'random_send_max_seconds' => 10,
                'daily_send_limit'      => 10000,
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

        // ★★★ バリデーションルールを更新 ★★★
        $validatedData = $request->validate([
            'daily_send_limit'      => 'required|integer|min:1',
            'send_timing_type'      => 'required|string|in:fixed,random',
            'max_emails_per_minute' => 'required_if:send_timing_type,fixed|integer|min:1',
            'random_send_min_seconds' => 'required_if:send_timing_type,random|integer|min:0',
            'random_send_max_seconds' => 'required_if:send_timing_type,random|integer|min:0|gte:random_send_min_seconds',
            'image_sending_enabled' => 'nullable|boolean',
        ]);

        $validatedData['image_sending_enabled'] = $request->has('image_sending_enabled');

        SalesToolSetting::updateOrCreate(['id' => 1], $validatedData);

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
            ->orderByRaw('processed_at IS NULL ASC, processed_at ASC')
            ->paginate(100);
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

        $managedContacts = $query->latest('updated_at')->paginate(500);

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
