<?php

namespace App\Http\Controllers;

use App\Models\EmailList;
use App\Models\SentEmail;   // ★ SentEmailモデルをuse
use App\Models\Subscriber;
use App\Models\BlacklistEntry;
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
        $subscribers = $emailList->subscribers()->orderBy('email')->paginate(15); // 1ページあたり15件

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
    public function subscribersCreate(Request $request, EmailList $emailList): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $query = ManagedContact::query();

        // Keyword search (quick search for selecting contacts)
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('company_name', 'like', "%{$keyword}%");
            });
        }

        // Advanced Filters for selecting ManagedContacts
        if ($request->filled('filter_company_name')) {
            $query->where('company_name', 'like', '%' . $request->input('filter_company_name') . '%');
        }
        if ($request->filled('filter_postal_code')) {
            $query->where('postal_code', 'like', '%' . $request->input('filter_postal_code') . '%');
        }
        if ($request->filled('filter_address')) {
            $query->where('address', 'like', '%' . $request->input('filter_address') . '%');
        }
        if ($request->filled('filter_establishment_date_from')) {
            $query->whereDate('establishment_date', '>=', $request->input('filter_establishment_date_from'));
        }
        if ($request->filled('filter_establishment_date_to')) {
            $query->whereDate('establishment_date', '<=', $request->input('filter_establishment_date_to'));
        }
        if ($request->filled('filter_industry')) {
            $query->where('industry', 'like', '%' . $request->input('filter_industry') . '%');
        }
        if ($request->filled('filter_notes')) {
            $query->where('notes', 'like', '%' . $request->input('filter_notes') . '%');
        }
        if ($request->filled('filter_status') && $request->input('filter_status') !== '') {
            $query->where('status', $request->input('filter_status'));
        } else {
            // デフォルトでは 'active' の連絡先のみを表示候補とする
            $query->where('status', 'active');
        }

        // Exclude contacts already subscribed to this EmailList
        $existingSubscriberEmails = $emailList->subscribers()->pluck('email')->all();
        if (!empty($existingSubscriberEmails)) {
            $query->whereNotIn('email', $existingSubscriberEmails);
        }

        $managedContacts = $query->orderBy('email')->paginate(500)->appends($request->query());

        $statusOptions = [ // For the ManagedContact status filter dropdown
            'active' => '有効',
            'do_not_contact' => '連絡不要',
            'archived' => 'アーカイブ済',
        ];

        // Capture all relevant filter inputs for repopulating the form
        $filterValues = $request->only([
            'keyword',
            'filter_company_name',
            'filter_postal_code',
            'filter_address',
            'filter_establishment_date_from',
            'filter_establishment_date_to',
            'filter_industry',
            'filter_notes',
            'filter_status',
        ]);

        return view('tools.sales.subscribers.create', compact('emailList', 'managedContacts', 'statusOptions', 'filterValues'));
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
            // フィルター条件に基づいて全ての対象IDを取得
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
                $query->where('status', 'active'); // デフォルトで 'active' の連絡先のみを対象
            }

            $existingSubscriberEmails = $emailList->subscribers()->pluck('email')->all();
            if (!empty($existingSubscriberEmails)) {
                $query->whereNotIn('email', $existingSubscriberEmails);
            }

            // 注意: 大量データの場合、サーバー負荷やタイムアウトの可能性があるため、
            // 本番環境では件数制限やバックグラウンド処理を検討してください。
            // 例: $contactIdsToAdd = $query->limit(1000)->pluck('id')->all();
            $contactIdsToAdd = $query->pluck('id')->all();

            if (empty($contactIdsToAdd)) {
                return redirect()->route('tools.sales.email-lists.subscribers.create', $emailList) // showではなくcreateに戻す
                    ->with('info', 'フィルター条件に一致する追加可能な連絡先が見つかりませんでした。')
                    ->withInput($request->except(['add_all_filtered_action', '_token'])); // フィルター条件をフォームに再表示
            }
            Log::info(count($contactIdsToAdd) . " contacts found via 'add all filtered' for EmailList ID: {$emailList->id}. Processing...");
        } else {
            // 通常のチェックボックス選択による処理
            $validated = $request->validate([
                'managed_contact_ids' => 'present|array',
                'managed_contact_ids.*' => 'integer|exists:managed_contacts,id',
            ]);
            $contactIdsToAdd = $validated['managed_contact_ids'] ?? [];

            if (empty($contactIdsToAdd)) {
                return redirect()->route('tools.sales.email-lists.subscribers.create', $emailList) // showではなくcreateに戻す
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

            $existingSubscriber = $emailList->subscribers()->where('email', $managedContact->email)->first();
            if ($existingSubscriber) {
                $skippedCount++;
                continue;
            }

            try {
                Subscriber::create([
                    'email_list_id' => $emailList->id,
                    'email' => $managedContact->email,
                    'name' => $managedContact->name,
                    'company_name' => $managedContact->company_name,
                    'postal_code' => $managedContact->postal_code,
                    'address' => $managedContact->address,
                    'phone_number' => $managedContact->phone_number,
                    'fax_number' => $managedContact->fax_number,
                    'url' => $managedContact->url,
                    'representative_name' => $managedContact->representative_name,
                    'establishment_date' => $managedContact->establishment_date,
                    'industry' => $managedContact->industry,
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
        if ($skippedCount > 0) $messageParts[] = "{$skippedCount}件は既にリストに登録済み、または対象外のためスキップしました。";
        if ($errorCount > 0) $messageParts[] = "{$errorCount}件の処理中にエラーが発生しました。詳細はログを確認してください。";

        if (empty($messageParts)) {
            $feedbackMessage = $isAddAllFiltered ? 'フィルター条件に一致する追加可能な連絡先が見つかりませんでした。' : '処理対象の連絡先がありませんでした。';
        } else {
            $feedbackMessage = implode(' ', $messageParts);
        }

        $messageType = 'info';
        if ($addedCount > 0 && $errorCount == 0) $messageType = 'success';
        elseif ($addedCount > 0) $messageType = 'success'; // 一部エラーでも成功件数があれば success
        elseif ($skippedCount > 0 && $errorCount == 0) $messageType = 'info';
        elseif ($errorCount > 0) $messageType = 'danger';
        else $messageType = 'warning';

        // 処理後はリスト詳細ページにリダイレクト
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
            abort(404);
        }

        $validatedData = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('subscribers')->where(function ($query) use ($emailList) {
                    return $query->where('email_list_id', $emailList->id);
                })->ignore($subscriber->id),
            ],
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',        // ★ 追加
            'address' => 'nullable|string|max:1000',        // ★ 追加
            'phone_number' => 'nullable|string|max:30',       // ★ 追加
            'fax_number' => 'nullable|string|max:30',         // ★ 追加
            'url' => 'nullable|string|url|max:255',          // ★ 追加
            'representative_name' => 'nullable|string|max:255', // ★ 追加
            'establishment_date' => 'nullable|date',          // ★ 追加
            'industry' => 'nullable|string|max:255',
            // 'job_title' => 'nullable|string|max:255',      // 必要なら復活
            'status' => 'required|string|in:subscribed,unsubscribed,bounced,pending',
        ]);

        if (isset($validatedData['establishment_date']) && $validatedData['establishment_date'] === '') { // 空文字の場合nullを許容
            $validatedData['establishment_date'] = null;
        }

        $subscriber->update($validatedData);

        return redirect()->route('tools.sales.email-lists.show', $emailList)
            ->with('success', '購読者「' . $subscriber->email . '」の情報を更新しました。');
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
     * Show the form for composing a new email.
     * 新規メール作成フォームを表示します。
     */
    public function composeEmail(): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // またはメール送信専用の権限

        $emailLists = EmailList::orderBy('name')->pluck('name', 'id'); // 送信先リスト選択用

        return view('tools.sales.emails.compose', compact('emailLists'));
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
        $subscribersQuery = $emailList->subscribers()->where('status', 'subscribed');

        if ($subscribersQuery->doesntExist()) {
            return redirect()->back()->withInput()->with('error', '選択されたメールリストに送信可能な購読者がいません。');
        }

        $maxEmailsPerMinute = SalesToolSetting::getSetting('max_emails_per_minute', 60);
        $delayBetweenEmails = $maxEmailsPerMinute > 0 ? max(1, round(60 / $maxEmailsPerMinute)) : 1;
        if ($maxEmailsPerMinute <= 0) { // 念のため
            Log::warning('max_emails_per_minute setting is invalid or not set, using default 60 emails/min (delay 1s).');
        }


        $senderEmail = $validatedData['sender_email'];
        $senderName = $validatedData['sender_name'] ?? config('mail.from.name');
        $subject = $validatedData['subject'];
        $bodyHtml = $validatedData['body_html'];

        $sentEmailRecord = SentEmail::create([
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'email_list_id' => $emailList->id,
            'sender_email' => $senderEmail,
            'sender_name' => $senderName,
            'sent_at' => now(),
            'status' => 'queuing',
        ]);

        $totalQueuedCount = 0;
        $blacklistedCount = 0;
        $failedQueueingCount = 0; // キューイング失敗をカウント
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
                        'message_identifier' => $sentEmailRecord->id . '_' . Str::uuid()->toString() // スキップ時もユニークID生成
                    ]
                );
                continue;
            }

            try {
                $mailable = new SalesCampaignMail(
                    $subject,
                    $bodyHtml,
                    $sentEmailRecord->id,
                    $subscriber->email
                    // Mailable内部で messageIdentifier が生成される
                );

                Mail::to($subscriber->email, $subscriber->name)
                    ->later(now()->addSeconds($currentCumulativeDelay), $mailable->from($senderEmail, $senderName));

                SentEmailLog::firstOrCreate(
                    [
                        'sent_email_id' => $sentEmailRecord->id,
                        'subscriber_id' => $subscriber->id,
                        'recipient_email' => $subscriber->email,
                    ],
                    [
                        'status' => 'queued', // ★★★ ここが 'queued' であることを確認
                        'message_identifier' => $mailable->messageIdentifier, // Mailableから取得
                    ]
                );
                Log::info('SentEmailLog created/found in Controller for queueing:', [ // ★ ログ追加
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
                        'subscriber_id' => $subscriber->id, // subscriber_id も含める
                        'status' => 'queue_failed',
                        'error_message' => Str::limit($e->getMessage(), 1000),
                        'processed_at' => now(),
                        'message_identifier' => $sentEmailRecord->id . '_' . Str::uuid()->toString() // 失敗時もユニークID生成
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
        } else if ($actualSubscribersCount == 0) {
            $sentEmailRecord->status = 'no_recipients';
        } else { // 何らかの理由でキューに入らなかったが、上記以外のケース
            $sentEmailRecord->status = 'processing_issue';
        }
        $sentEmailRecord->save();

        $messageParts = [];
        if ($totalQueuedCount > 0) {
            $messageParts[] = "{$totalQueuedCount}件のメールを送信キューに追加しました。";
        }
        if ($blacklistedCount > 0) {
            $messageParts[] = "{$blacklistedCount}件のメールアドレスがブラックリストのためスキップしました。";
        }
        if ($failedQueueingCount > 0) {
            $messageParts[] = "{$failedQueueingCount}件のメールがキュー投入に失敗しました。詳細はログを確認してください。";
        }

        if (empty($messageParts) && $actualSubscribersCount > 0) {
            $messageParts[] = "メールのキューイング処理が完了しましたが、実際にキューに追加されたメールはありませんでした。";
        } elseif (empty($messageParts) && $actualSubscribersCount == 0) {
            $messageParts[] = "送信対象の購読者が見つかりませんでした。";
        }

        $finalMessage = implode(' ', $messageParts);
        if ($totalQueuedCount > 0) {
            $finalMessage .= " 設定された間隔で順次送信されます。";
        } elseif (empty($finalMessage)) {
            $finalMessage = "メール送信処理は実行されましたが、特筆すべき結果はありませんでした。";
        }

        return redirect()->route('tools.sales.index')
            ->with('success', $finalMessage);
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
    public function managedContactsIndex(Request $request): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $query = ManagedContact::query();

        // Keyword search (quick search)
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('company_name', 'like', "%{$keyword}%");
            });
        }

        // Advanced Filters
        if ($request->filled('filter_company_name')) {
            $query->where('company_name', 'like', '%' . $request->input('filter_company_name') . '%');
        }
        if ($request->filled('filter_postal_code')) {
            // 郵便番号は部分一致より前方一致か完全一致が良い場合もあるが、ここでは部分一致
            $query->where('postal_code', 'like', '%' . $request->input('filter_postal_code') . '%');
        }
        if ($request->filled('filter_address')) {
            $query->where('address', 'like', '%' . $request->input('filter_address') . '%');
        }
        if ($request->filled('filter_establishment_date_from')) {
            $query->whereDate('establishment_date', '>=', $request->input('filter_establishment_date_from'));
        }
        if ($request->filled('filter_establishment_date_to')) {
            $query->whereDate('establishment_date', '<=', $request->input('filter_establishment_date_to'));
        }
        if ($request->filled('filter_industry')) {
            $query->where('industry', 'like', '%' . $request->input('filter_industry') . '%');
        }
        if ($request->filled('filter_notes')) {
            $query->where('notes', 'like', '%' . $request->input('filter_notes') . '%');
        }
        if ($request->filled('filter_status') && $request->input('filter_status') !== '') {
            $query->where('status', $request->input('filter_status'));
        }

        $managedContacts = $query->orderBy('created_at', 'desc')->paginate(100)->appends($request->query()); //  appends($request->query()) ですべてのGETパラメータを引き継ぐ

        $statusOptions = [
            'active' => '有効',
            'do_not_contact' => '連絡不要',
            'archived' => 'アーカイブ済',
        ];

        // ビューに渡すフィルター値の配列を準備（フィルターフォームの初期値設定用）
        $filterValues = $request->only([
            'keyword',
            'filter_company_name',
            'filter_postal_code',
            'filter_address',
            'filter_establishment_date_from',
            'filter_establishment_date_to',
            'filter_industry',
            'filter_notes',
            'filter_status',
        ]);

        $csvImportInterruptedMessage = null;
        if ($request->session()->has('csv_import_status')) {
            $importStatus = $request->session()->get('csv_import_status');
            if (isset($importStatus['in_progress']) && $importStatus['in_progress'] === true) {
                $fileName = $importStatus['file_name'] ?? '前回';
                $imported = $importStatus['imported_count'] ?? 0;
                $updated = $importStatus['updated_count'] ?? 0;
                $failed = $importStatus['failed_count'] ?? 0; // セッションには途中までの失敗件数が入る

                $csvImportInterruptedMessage = "{$fileName} のCSVインポート処理が中断された可能性があります。";
                if ($imported > 0 || $updated > 0 || $failed > 0) {
                    $csvImportInterruptedMessage .= " その時点までに {$imported}件が新規登録、{$updated}件が更新、{$failed}件が失敗として記録されています。";
                } else {
                    $csvImportInterruptedMessage .= " 処理された件数は記録されていません。";
                }
                $csvImportInterruptedMessage .= " データを確認し、必要に応じて再度インポートを実行してください。";
            }
            $request->session()->forget('csv_import_status'); // メッセージ表示後にセッション情報を削除
        }

        return view('tools.sales.managed_contacts.index', compact('managedContacts', 'statusOptions', 'filterValues'));
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
            'establishment_date' => 'nullable|date_format:Y-m-d', // dateフォーマット指定
            'industry' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:active,do_not_contact,archived', // 適切なステータス値
        ]);
        if (empty($validatedData['status'])) {
            $validatedData['status'] = 'active'; // デフォルトステータス
        }
        if (isset($validatedData['establishment_date']) && $validatedData['establishment_date'] === '') {
            $validatedData['establishment_date'] = null;
        }

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
            'establishment_date' => 'nullable|date_format:Y-m-d',
            'industry' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|string|in:active,do_not_contact,archived',
        ]);
        if (isset($validatedData['establishment_date']) && $validatedData['establishment_date'] === '') {
            $validatedData['establishment_date'] = null;
        }

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
     * Import managed contacts from a CSV file.
     * CSVファイルから管理連絡先をインポートします。
     */
    public function managedContactsImportCsv(Request $request)
    {
        // ★★★ 最大実行時間を180秒に設定 ★★★
        @set_time_limit(180); // エラー制御演算子 @ は、set_time_limit が無効な環境での警告を抑制するため

        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getRealPath();
        $originalFileName = $file->getClientOriginalName();

        // ★★★ インポート処理中ステータスをセッションに保存 ★★★
        $request->session()->put('csv_import_status', [
            'in_progress' => true,
            'file_name' => $originalFileName,
            'imported_count' => 0,
            'updated_count' => 0,
            'failed_count' => 0,
        ]);

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            // 必要に応じてエンコーディング設定
            // try {
            //     $csv->setCharset('SJIS-win'); // 例: Windowsで作成されたShift_JIS CSVの場合
            //     // BOMチェックと除去（league/csv v9.7.0+）
            //     if (str_starts_with(file_get_contents($filePath, false, null, 0, 3), "\xEF\xBB\xBF")) {
            //        $csv->setInputBOM(Reader::BOM_UTF8);
            //     }
            // } catch (\Exception $e) { /* Charset setting failed, try default */ }

            $csv->setHeaderOffset(0);
            $header = $csv->getHeader();
            $records = Statement::create()->process($csv);
        } catch (\Exception $e) {
            Log::error('CSV Import - File Read Error: ' . $e->getMessage(), ['file' => $originalFileName]);
            $request->session()->forget('csv_import_status'); // エラー時はセッションクリア
            return redirect()->back()->with('danger', 'CSVファイルの読み込みに失敗しました。ファイル形式やエンコーディングを確認してください。');
        }

        $columnMappings = [ /* ... (前回定義の通り) ... */
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
        $headerToModelMap = [];
        $actualCsvHeaders = array_map('trim', $header);
        foreach ($columnMappings as $modelAttribute => $possibleHeaders) {
            foreach ($possibleHeaders as $possibleHeader) {
                $columnIndex = array_search($possibleHeader, $actualCsvHeaders);
                if ($columnIndex !== false) {
                    $headerToModelMap[$modelAttribute] = $columnIndex;
                    break;
                }
            }
        }
        if (!isset($headerToModelMap['email'])) {
            $request->session()->forget('csv_import_status');
            return redirect()->back()->with('danger', 'CSVヘッダーに「メールアドレス」に該当する列が見つかりませんでした。');
        }

        $importedCount = 0;
        $updatedCount = 0;
        $failedCount = 0;
        $failedRowsDetails = [];

        foreach ($records as $index => $record) {
            $rowData = [];
            foreach ($headerToModelMap as $modelAttribute => $columnIndex) {
                if (isset($actualCsvHeaders[$columnIndex]) && isset($record[$actualCsvHeaders[$columnIndex]])) {
                    $rowData[$modelAttribute] = trim($record[$actualCsvHeaders[$columnIndex]]);
                } else {
                    $rowData[$modelAttribute] = null;
                }
            }

            $validator = Validator::make($rowData, [ /* ... (バリデーションルールは前回定義の通り) ... */
                'email' => 'required|email|max:255',
                'name' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:1000',
                'phone_number' => 'nullable|string|max:30',
                'fax_number' => 'nullable|string|max:30',
                'url' => 'nullable|string|url|max:255',
                'representative_name' => 'nullable|string|max:255',
                'establishment_date' => 'nullable|date_format:Y-m-d,Y/m/d',
                'industry' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'status' => 'nullable|string|in:active,do_not_contact,archived',
            ]);

            if ($validator->fails()) {
                $failedCount++;
                $failedRowsDetails[] = ['row' => $index + 2, 'email' => $rowData['email'] ?? 'N/A', 'errors' => $validator->errors()->all()];
                Log::warning('CSV Import - Validation Failed for row', ['file' => $originalFileName, 'row_index' => $index + 2, 'data' => $rowData, 'errors' => $validator->errors()->all()]);
                $request->session()->put('csv_import_status.failed_count', $failedCount); // 失敗件数もセッション更新
                continue;
            }

            $dataToUpsert = $validator->validated();
            if (!empty($dataToUpsert['establishment_date'])) {
                try {
                    $dataToUpsert['establishment_date'] = \Carbon\Carbon::parse($dataToUpsert['establishment_date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $dataToUpsert['establishment_date'] = null;
                }
            } else {
                $dataToUpsert['establishment_date'] = null;
            }
            if (empty($dataToUpsert['status'])) {
                $dataToUpsert['status'] = 'active';
            }

            try {
                $contact = ManagedContact::updateOrCreate(
                    ['email' => $dataToUpsert['email']],
                    $dataToUpsert
                );

                if ($contact->wasRecentlyCreated) {
                    $importedCount++;
                    $request->session()->put('csv_import_status.imported_count', $importedCount);
                } else if ($contact->wasChanged()) {
                    $updatedCount++;
                    $request->session()->put('csv_import_status.updated_count', $updatedCount);
                }
            } catch (\Exception $e) {
                $failedCount++;
                $failedRowsDetails[] = ['row' => $index + 2, 'email' => $dataToUpsert['email'] ?? 'N/A', 'errors' => ['データベースエラー: ' . Str::limit($e->getMessage(), 100)]];
                Log::error('CSV Import - DB Error for email: ' . ($dataToUpsert['email'] ?? 'N/A'), ['file' => $originalFileName, 'message' => $e->getMessage()]);
                $request->session()->put('csv_import_status.failed_count', $failedCount);
            }
        }

        // ★★★ 正常完了時はセッション情報をクリア ★★★
        $request->session()->forget('csv_import_status');

        $message = "CSVインポート処理が完了しました（{$originalFileName}）。";
        $message .= " {$importedCount}件の新規連絡先を登録しました。";
        $message .= " {$updatedCount}件の既存連絡先を更新しました。";
        if ($failedCount > 0) {
            $messageType = 'warning'; // 失敗がある場合は warning
            $message .= " {$failedCount}件の処理に失敗しました。";
            Log::warning('CSV Import Summary: Failed rows present.', ['file' => $originalFileName, 'details' => $failedRowsDetails]);
            $message .= ' 詳細はログを確認してください。';
        } else {
            $messageType = 'success';
        }

        return redirect()->route('tools.sales.managed-contacts.index')
            ->with($messageType, $message);
    }
}
