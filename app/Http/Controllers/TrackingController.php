<?php

namespace App\Http\Controllers;

use App\Models\SentEmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response; // Responseファサードをuse
use Illuminate\Support\Facades\Redirect;
use App\Models\BlacklistEntry;

class TrackingController extends Controller
{
    /**
     * Record an email open event.
     *
     * @param string $identifier (例: SentEmailLogのmessage_identifier または暗号化されたID)
     * @return \Illuminate\Http\Response
     */
    public function open(string $identifier)
    {
        Log::info("Tracking open attempt for identifier: {$identifier}");

        // identifierを使ってSentEmailLogを検索
        // ここでは、SentEmailLogの 'message_identifier' カラムに保存されたユニークIDを使用すると仮定
        $logEntry = SentEmailLog::where('message_identifier', $identifier)
            ->whereNull('opened_at') // まだ開封されていないログのみを対象 (複数回記録を防ぐ)
            ->first();

        if ($logEntry) {
            $logEntry->opened_at = now();
            // (任意) ステータスも 'opened' などに変更するなら
            // if ($logEntry->status === 'sent' || $logEntry->status === 'delivered') { // 送信成功/配信済の場合のみ開封とみなす
            //     $logEntry->status = 'opened';
            // }
            $logEntry->save();

            Log::info("Email open recorded for SentEmailLog ID: {$logEntry->id}, Identifier: {$identifier}");

            // (任意) 親のSentEmailの開封数を更新するなどの集計処理 (パフォーマンス考慮)
            // $this->updateParentSentEmailOpenStats($logEntry->sent_email_id);

        } else {
            Log::warning("Tracking open: No matching SentEmailLog found or already opened for identifier: {$identifier}");
        }

        // 1x1ピクセルの透明なGIF画像を返す
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        return Response::make($pixel, 200, ['Content-Type' => 'image/gif']);
    }

    /**
     * Record an email link click event and redirect to the original URL.
     *
     * @param Request $request
     * @param string $identifier (例: SentEmailLogのmessage_identifier)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function click(Request $request, string $identifier) // ★★★ このメソッドを追加 ★★★
    {
        Log::info("Tracking click attempt for identifier: {$identifier}");

        $originalUrl = $request->query('url'); // クエリパラメータ 'url' から元のURLを取得

        if (empty($originalUrl)) {
            Log::warning("Tracking click: Original URL not provided for identifier: {$identifier}.");
            // フォールバックとしてホームページなどにリダイレクトするか、エラーページを表示
            return Redirect::to('/'); // または適切なエラー処理
        }

        // identifierを使ってSentEmailLogを検索
        $logEntry = SentEmailLog::where('message_identifier', $identifier)->first();

        if ($logEntry) {
            // 最初のクリックのみを記録する場合、または毎回記録して集計する場合がある
            if (is_null($logEntry->clicked_at)) { // 最初のクリックのみ日時を記録
                $logEntry->clicked_at = now();
            }
            // (任意) クリックカウントを増やす場合
            // $logEntry->increment('click_count'); // SentEmailLogにclick_countカラムが必要

            // (任意) クリックされたリンクURLを保存する場合
            // $logEntry->last_clicked_link = $originalUrl; // SentEmailLogにlast_clicked_linkカラムが必要

            // (任意) ステータスも 'clicked' などに変更するなら
            // if ($logEntry->status === 'sent' || $logEntry->status === 'opened' || $logEntry->status === 'delivered') {
            //    $logEntry->status = 'clicked';
            // }
            $logEntry->save();

            Log::info("Email click recorded for SentEmailLog ID: {$logEntry->id}, Identifier: {$identifier}, Original URL: {$originalUrl}");
        } else {
            Log::warning("Tracking click: No matching SentEmailLog found for identifier: {$identifier}. Original URL: {$originalUrl}");
        }

        // 元のURLにリダイレクト
        // 301 (Moved Permanently) または 302 (Found) / 307 (Temporary Redirect) を使用
        return Redirect::away($originalUrl, 302);
    }

    /**
     * Handle email unsubscribe request.
     *
     * @param string $identifier (SentEmailLogのmessage_identifier)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function unsubscribe(string $identifier)
    {
        Log::info("Unsubscribe attempt for identifier: {$identifier}");

        // identifierを使ってSentEmailLogを検索 (ここからSubscriberとEmailListを特定)
        $logEntry = SentEmailLog::where('message_identifier', $identifier)->with('subscriber.emailList')->first();

        if (!$logEntry || !$logEntry->subscriber) {
            Log::warning("Unsubscribe: No matching SentEmailLog or Subscriber found for identifier: {$identifier}");
            // 適切なエラーページまたは汎用的な完了ページへリダイレクト
            return view('tools.sales.unsubscribe.thanks', ['message' => '配信停止処理中にエラーが発生しました。お手数ですが再度お試しいただくか、管理者にご連絡ください。', 'error' => true]);
        }

        $subscriber = $logEntry->subscriber;
        $emailList = $logEntry->subscriber->emailList; // Subscriberが属するEmailListを取得

        if ($subscriber->status === 'unsubscribed' && $subscriber->email_list_id === $emailList->id) {
            // 既にこのリストから配信停止済みの場合
            Log::info("Unsubscribe: Subscriber ID {$subscriber->id} already unsubscribed from EmailList ID {$emailList->id}. Identifier: {$identifier}");
            return view('tools.sales.unsubscribe.thanks', ['email' => $subscriber->email, 'listName' => $emailList->name, 'alreadyUnsubscribed' => true]);
        }

        // 購読者のステータスを更新
        $subscriber->status = 'unsubscribed';
        $subscriber->unsubscribed_at = now();
        $subscriber->save();

        //ブラックリストへ追加
        BlacklistEntry::create([
            'email' => $subscriber->email,
            'reason' => '配信停止リンクより',
            'added_by_user_id' => null, // 登録したユーザーIDを記録
        ]);

        // (任意) SentEmailLogのステータスも更新
        $logEntry->status = 'unsubscribed_via_link'; // 新しいステータス
        // $logEntry->clicked_at = now(); // クリックとしても記録する場合
        $logEntry->save();

        Log::info("Subscriber ID {$subscriber->id} ({$subscriber->email}) unsubscribed from EmailList ID {$emailList->id}. Identifier: {$identifier}");

        // (任意) 親のSentEmailの集計情報を更新するロジックを呼び出すことも可能

        return view('tools.sales.unsubscribe.thanks', ['email' => $subscriber->email, 'listName' => $emailList->name]);
    }
}
