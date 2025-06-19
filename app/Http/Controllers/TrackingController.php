<?php

namespace App\Http\Controllers;

use App\Models\SentEmailLog;
use App\Models\Subscriber; // Subscriberモデルを追加
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redirect;
use App\Models\BlacklistEntry;

class TrackingController extends Controller
{
    /**
     * Record an email open event.
     */
    public function open(string $identifier)
    {
        Log::info("Tracking open attempt for identifier: {$identifier}");

        $logEntry = SentEmailLog::where('message_identifier', $identifier)
            ->whereNull('opened_at')
            ->first();

        if ($logEntry) {
            $logEntry->opened_at = now();
            $logEntry->save();
            Log::info("Email open recorded for SentEmailLog ID: {$logEntry->id}, Identifier: {$identifier}");
        } else {
            Log::warning("Tracking open: No matching SentEmailLog found or already opened for identifier: {$identifier}");
        }

        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        return Response::make($pixel, 200, ['Content-Type' => 'image/gif']);
    }

    /**
     * Record an email link click event and redirect to the original URL.
     */
    public function click(Request $request, string $identifier)
    {
        Log::info("Tracking click attempt for identifier: {$identifier}");

        $originalUrl = $request->query('url');

        if (empty($originalUrl)) {
            Log::warning("Tracking click: Original URL not provided for identifier: {$identifier}.");
            return Redirect::to('/');
        }

        $logEntry = SentEmailLog::where('message_identifier', $identifier)->first();

        if ($logEntry) {
            if (is_null($logEntry->clicked_at)) {
                $logEntry->clicked_at = now();
            }
            $logEntry->save();
            Log::info("Email click recorded for SentEmailLog ID: {$logEntry->id}, Identifier: {$identifier}, Original URL: {$originalUrl}");
        } else {
            Log::warning("Tracking click: No matching SentEmailLog found for identifier: {$identifier}. Original URL: {$originalUrl}");
        }

        return Redirect::away($originalUrl, 302);
    }

    // ★★★ 古い unsubscribe メソッドはここから完全に削除します ★★★

    /**
     * ★★★ [新規] 配信停止の確認ページを表示します。 ★★★
     */
    public function showUnsubscribeConfirmationPage(string $identifier)
    {
        Log::info("Unsubscribe confirmation page request for identifier: {$identifier}");

        $logEntry = SentEmailLog::where('message_identifier', $identifier)->with('subscriber')->first();

        if (!$logEntry || !$logEntry->subscriber) {
            Log::warning("Unsubscribe Confirmation: No matching log or subscriber for identifier: {$identifier}");
            return view('tools.sales.unsubscribe.thanks', ['message' => '無効なリクエストです。リンクが間違っているか、期限が切れている可能性があります。', 'error' => true]);
        }

        $subscriber = $logEntry->subscriber;

        if ($subscriber->status === 'unsubscribed') {
            Log::info("Unsubscribe Confirmation: Subscriber ID {$subscriber->id} already unsubscribed. Identifier: {$identifier}");
            return view('tools.sales.unsubscribe.thanks', ['email' => $subscriber->email, 'alreadyUnsubscribed' => true]);
        }

        return view('tools.sales.unsubscribe.confirm', [
            'identifier' => $identifier,
            'email' => $subscriber->email
        ]);
    }

    /**
     * ★★★ [新規] 実際の配信停止処理を実行します。 ★★★
     */
    public function processUnsubscribe(Request $request)
    {
        $identifier = $request->input('identifier');
        Log::info("Unsubscribe process attempt for identifier: {$identifier}");

        $logEntry = SentEmailLog::where('message_identifier', $identifier)->with('subscriber.emailList')->first();

        if (!$logEntry || !$logEntry->subscriber) {
            Log::warning("Unsubscribe Process: No matching log or subscriber for identifier: {$identifier}");
            return view('tools.sales.unsubscribe.thanks', ['message' => '配信停止処理中にエラーが発生しました。対象の購読者が見つかりません。', 'error' => true]);
        }

        $subscriber = $logEntry->subscriber;
        $emailList = $logEntry->subscriber->emailList;

        if ($subscriber->status === 'unsubscribed') {
            Log::info("Unsubscribe Process: Subscriber ID {$subscriber->id} was already unsubscribed. Identifier: {$identifier}");
            return view('tools.sales.unsubscribe.thanks', ['email' => $subscriber->email, 'listName' => $emailList->name ?? '', 'alreadyUnsubscribed' => true]);
        }

        // 購読者のステータスを更新
        $subscriber->status = 'unsubscribed';
        $subscriber->unsubscribed_at = now();
        $subscriber->save();

        // ブラックリストへ追加
        BlacklistEntry::firstOrCreate(
            ['email' => $subscriber->email],
            [
                'reason' => '配信停止リンクより',
                'added_by_user_id' => null,
            ]
        );

        // SentEmailLogのステータスも更新
        $logEntry->status = 'unsubscribed_via_link';
        $logEntry->save();

        Log::info("Subscriber ID {$subscriber->id} ({$subscriber->email}) successfully unsubscribed from EmailList ID {$emailList->id}. Identifier: {$identifier}");

        return view('tools.sales.unsubscribe.thanks', ['email' => $subscriber->email, 'listName' => $emailList->name ?? '']);
    }
}
