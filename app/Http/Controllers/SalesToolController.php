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

        $emailLists = EmailList::orderBy('name')->paginate(15);
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
    public function subscribersCreate(EmailList $emailList): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);
        return view('tools.sales.subscribers.create', compact('emailList'));
    }

    /**
     * Store a newly created subscriber in storage for the given email list.
     * 新しい購読者を指定されたメールリストに保存します。
     */
    public function subscribersStore(Request $request, EmailList $emailList)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        $validatedData = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('subscribers')->where(function ($query) use ($emailList) {
                    return $query->where('email_list_id', $emailList->id);
                }),
            ],
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',        // ★ 追加
            'address' => 'nullable|string|max:1000',        // ★ 追加 (max長は適宜調整)
            'phone_number' => 'nullable|string|max:30',       // ★ 追加
            'fax_number' => 'nullable|string|max:30',         // ★ 追加
            'url' => 'nullable|string|url|max:255',          // ★ 追加 (urlバリデーション)
            'representative_name' => 'nullable|string|max:255', // ★ 追加
            'establishment_date' => 'nullable|date',          // ★ 追加
            'industry' => 'nullable|string|max:255',
            // 'job_title' => 'nullable|string|max:255',      // 必要なら復活
            'status' => 'nullable|string|in:subscribed,unsubscribed,bounced,pending',
        ]);

        $subscriberData = array_merge($validatedData, ['email_list_id' => $emailList->id]);
        if (empty($subscriberData['status'])) {
            $subscriberData['status'] = 'subscribed';
        }
        if (isset($subscriberData['establishment_date']) && $subscriberData['establishment_date'] === '') { // 空文字の場合nullを許容
            $subscriberData['establishment_date'] = null;
        }


        $subscriber = Subscriber::create($subscriberData);

        return redirect()->route('tools.sales.email-lists.show', $emailList)
            ->with('success', '購読者「' . $subscriber->email . '」をリストに追加しました。');
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
    public function sendEmail(Request $request)
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION);

        // ... (バリデーション、購読者取得、設定値取得は前回と同様) ...
        $validatedData = $request->validate([ /* ... */]);
        $emailList = EmailList::findOrFail($validatedData['email_list_id']);
        $subscribers = $emailList->subscribers()->where('status', 'subscribed')->get();

        if ($subscribers->isEmpty()) {
            return redirect()->back()->withInput()->with('error', '選択されたメールリストに送信可能な購読者がいません。');
        }

        $maxEmailsPerMinute = SalesToolSetting::getSetting('max_emails_per_minute', 60);
        $delayBetweenEmails = $maxEmailsPerMinute > 0 ? max(1, round(60 / $maxEmailsPerMinute)) : 1;

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
            'sent_at' => now(), // 送信指示日時
            'status' => 'queuing',
        ]);

        $totalQueuedCount = 0;
        $blacklistedCount = 0;
        $blacklistedEmails = BlacklistEntry::pluck('email')->all();
        $currentCumulativeDelay = 0;

        foreach ($subscribers as $subscriber) {
            if (in_array($subscriber->email, $blacklistedEmails)) {
                $blacklistedCount++;
                Log::info("Mail to {$subscriber->email} skipped (blacklisted) for SentEmail ID {$sentEmailRecord->id}.");
                // ★ ブラックリストによるスキップをSentEmailLogに記録
                SentEmailLog::create([
                    'sent_email_id' => $sentEmailRecord->id,
                    'subscriber_id' => $subscriber->id,
                    'recipient_email' => $subscriber->email,
                    'status' => 'skipped_blacklist', // 新しいステータス
                    'processed_at' => now(),
                ]);
                continue;
            }

            try {
                $mailable = new SalesCampaignMail($subject, $bodyHtml);
                Mail::to($subscriber->email, $subscriber->name)
                    ->later(now()->addSeconds($currentCumulativeDelay), $mailable->from($senderEmail, $senderName));

                // ★ キュー投入時にSentEmailLogに記録
                SentEmailLog::create([
                    'sent_email_id' => $sentEmailRecord->id,
                    'subscriber_id' => $subscriber->id,
                    'recipient_email' => $subscriber->email,
                    'status' => 'queued', // キュー投入済み
                    // processed_at はキューワーカーが実際に処理を開始したときに更新するのが理想
                ]);

                $totalQueuedCount++;
                $currentCumulativeDelay += $delayBetweenEmails;
            } catch (\Exception $e) {
                Log::error("Mail queueing failed for {$subscriber->email} for SentEmail ID {$sentEmailRecord->id}: " . $e->getMessage());
                // ★ キューイング失敗もSentEmailLogに記録
                SentEmailLog::create([
                    'sent_email_id' => $sentEmailRecord->id,
                    'subscriber_id' => $subscriber->id,
                    'recipient_email' => $subscriber->email,
                    'status' => 'queue_failed',
                    'error_message' => Str::limit($e->getMessage(), 250), // エラーメッセージを記録
                    'processed_at' => now(),
                ]);
            }
        }

        // ... (SentEmailレコードのステータス更新とリダイレクト処理は前回と同様) ...
        // SentEmailのステータス更新ロジックは、SentEmailLogの集計に基づいてより精緻にできる
        if ($totalQueuedCount > 0) {
            $sentEmailRecord->status = 'queued'; // 少なくとも1つキューに入った
        } elseif ($blacklistedCount > 0 && $totalQueuedCount === 0 && !$subscribers->isEmpty()) {
            $sentEmailRecord->status = 'all_blacklisted_or_failed'; // 全員ブラックリストまたはキューイング失敗
        } elseif (!$subscribers->isEmpty() && $totalQueuedCount === 0) {
            $sentEmailRecord->status = 'all_queue_failed'; // キュー投入に全て失敗
        } else {
            $sentEmailRecord->status = 'no_recipients';
        }
        // $sentEmailRecord->total_recipients = $subscribers->count(); // 総対象者数
        // $sentEmailRecord->successfully_queued_recipients = $totalQueuedCount; // 実際にキューに入った数
        $sentEmailRecord->save();

        // ... (メッセージ作成とリダイレクトは前回と同様) ...
        $messageParts = [];
        if ($totalQueuedCount > 0) {
            $messageParts[] = "{$totalQueuedCount}件のメールを送信キューに追加しました。";
        }
        if ($blacklistedCount > 0) {
            $messageParts[] = "{$blacklistedCount}件のメールアドレスがブラックリストのためスキップしました。";
        }
        // (キューイング失敗件数もメッセージに含める場合は、別途カウントが必要)

        if (empty($messageParts) && !$subscribers->isEmpty()) {
            $messageParts[] = "メールのキューイング処理が完了しましたが、実際にキューに追加されたメールはありませんでした（ブラックリスト、またはキューイングエラーの可能性があります）。";
        } elseif (empty($messageParts)) {
            $messageParts[] = "送信対象の購読者が見つかりませんでした。";
        }
        $finalMessage = implode(' ', $messageParts) . ($totalQueuedCount > 0 ? "設定された間隔で順次送信されます。" : "");

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
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // または設定管理専用の権限

        $validatedData = $request->validate([
            'send_interval_minutes' => 'required|integer|min:1',         // 最低1分
            'emails_per_batch' => 'required|integer|min:1|max:1000',  // 1回あたり1～1000通
            'batch_delay_seconds' => 'required|integer|min:0',        // 最低0秒 (遅延なし)
            'image_sending_enabled' => 'nullable|boolean',
        ]);

        // boolean 値の処理: チェックボックスがオフの場合、リクエストにそのキーが含まれないため
        $validatedData['image_sending_enabled'] = $request->has('image_sending_enabled');


        // sales_tool_settings テーブルには通常1レコードのみ存在すると想定
        $settings = SalesToolSetting::firstOrCreate(
            ['id' => 1],
            [ // もし万が一レコードがなかった場合の初期値 (通常はeditで作成されているはず)
                'send_interval_minutes' => 5,
                'emails_per_batch' => 100,
                'batch_delay_seconds' => 60,
                'image_sending_enabled' => true,
            ]
        );

        $settings->update($validatedData);

        return redirect()->route('tools.sales.settings.edit')
            ->with('success', '営業ツールの設定を更新しました。');
    }

    /**
     * Display a listing of sent (or currently processing) emails.
     * 送信済み（または処理中）のメール一覧を表示します。
     */
    public function sentEmailsIndex(Request $request): View
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // または専用の閲覧権限

        $query = SentEmail::with('emailList') // EmailListもEager Loadする
            ->withCount([
                'recipientLogs as total_recipients_count',
                'recipientLogs as successful_sends_count' => function ($query) {
                    $query->where('status', 'sent'); // 'sent' ステータスを成功と仮定
                },
                'recipientLogs as failed_sends_count' => function ($query) {
                    $query->whereIn('status', ['failed', 'bounced', 'queue_failed']); // これらを失敗と仮定
                },
                'recipientLogs as skipped_blacklist_count' => function ($query) {
                    $query->where('status', 'skipped_blacklist');
                }
            ])
            ->orderBy('sent_at', 'desc');

        // (任意) キーワード検索などのフィルターを追加可能
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
    public function sentEmailsShow(SentEmail $sentEmail): View // ルートモデルバインディング
    {
        $this->authorize(self::SALES_TOOL_ACCESS_PERMISSION); // または専用の閲覧権限

        // 関連する送信ログをページネーション付きで取得
        $recipientLogs = $sentEmail->recipientLogs()
            ->with('subscriber') // Subscriber情報もEager Load (任意)
            ->orderBy('created_at', 'desc') // または processed_at など
            ->paginate(25); // 1ページあたり25件

        // (任意) サマリー情報をビューに渡す
        $summary = [
            'total' => $sentEmail->recipientLogs()->count(),
            'sent' => $sentEmail->recipientLogs()->where('status', 'sent')->count(),
            'failed' => $sentEmail->recipientLogs()->whereIn('status', ['failed', 'bounced', 'queue_failed'])->count(),
            'queued' => $sentEmail->recipientLogs()->where('status', 'queued')->count(),
            'skipped_blacklist' => $sentEmail->recipientLogs()->where('status', 'skipped_blacklist')->count(),
            // 開封数やクリック数は将来的に
        ];


        return view('tools.sales.emails.sent.show', compact('sentEmail', 'recipientLogs', 'summary'));
    }
}
