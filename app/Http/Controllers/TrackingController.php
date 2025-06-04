<?php

namespace App\Http\Controllers;

use App\Models\SentEmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response; // Responseファサードをuse
use Illuminate\Support\Facades\Redirect;

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
}
