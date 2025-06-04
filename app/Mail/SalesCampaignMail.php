<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
// use Illuminate\Mail\Mailables\Address; // Laravel 9+ では不要な場合あり

class SalesCampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $mailSubject;
    public string $mailBodyHtml;
    public int $sentEmailRecordId;
    public string $recipientEmail;
    public string $messageIdentifier;


    public function __construct(string $subject, string $htmlContent, int $sentEmailRecordId, string $recipientEmail)
    {
        $this->mailSubject = $subject;
        $this->mailBodyHtml = $htmlContent;
        $this->sentEmailRecordId = $sentEmailRecordId;
        $this->recipientEmail = $recipientEmail;
        $this->messageIdentifier = $sentEmailRecordId . '_' . Str::uuid()->toString();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        // --- リンクの書き換え処理 ---
        $crawler = new Crawler($this->mailBodyHtml);
        $modifiedHtml = $this->mailBodyHtml; // 元のHTMLを保持

        // <a> タグを全て取得
        $links = $crawler->filter('a');

        if ($links->count() > 0) {
            $links->each(function (Crawler $node, $i) use (&$modifiedHtml) {
                $originalHref = $node->attr('href');

                // href属性があり、空でなく、トラッキング対象外のリンクでないか確認
                // (例: 'mailto:' リンクや、既にトラッキング済み、自ドメインの特定パスなどは除外)
                if (
                    $originalHref &&
                    !empty(trim($originalHref)) &&
                    !Str::startsWith(strtolower(trim($originalHref)), ['mailto:', 'tel:', '#', 'javascript:']) &&
                    !Str::contains($originalHref, url('/track/click'))
                ) { // 既にトラッキングURLでないことを確認

                    // トラッキングURLを生成
                    $trackingUrl = route('track.click', [
                        'identifier' => $this->messageIdentifier, // SentEmailLog を特定するためのID
                        'url' => urlencode($originalHref)        // 元のURLをエンコードして渡す
                    ]);

                    // DomCrawlerで直接DOMを書き換えるのは少し複雑なので、
                    // ここでは文字列置換で対応する (より堅牢なのはDOM操作)
                    // 注意: この置換方法は、href属性の値がHTML内でユニークでない場合に問題を起こす可能性があります。
                    //       例えば、<a href="url1">text1</a> と <a href="url1">text2</a> がある場合、両方書き換わります。
                    //       より安全なのは、Crawlerのノード操作で直接属性を書き換えることです。
                    //       $node->getNode(0)->setAttribute('href', $trackingUrl); したうえで $crawler->html() で取得する。

                    // 以下はDomCrawlerを使ったより安全な書き換えの試み (ただし、元のHTML全体を再構築する必要がある)
                    // $node->getNode(0)->setAttribute('href', $trackingUrl);
                    // (この方法だと、元の $this->mailBodyHtml を変更するのではなく、$crawler->html() を使う)

                    // 一旦、よりシンプルな文字列置換で試みます。
                    // HTMLの特性上、完璧ではないことを理解した上で使用してください。
                    // href="ORIGINAL" を href="TRACKING_URL" に置換
                    $escapedOriginalHref = htmlspecialchars($originalHref, ENT_QUOTES, 'UTF-8');
                    $escapedTrackingUrl = htmlspecialchars($trackingUrl, ENT_QUOTES, 'UTF-8');

                    // 単純な文字列置換はリスクがあるため、より確実なDOM操作を推奨
                    // $modifiedHtml = str_replace(
                    //     "href=\"{$escapedOriginalHref}\"",
                    //     "href=\"{$escapedTrackingUrl}\"",
                    //     $modifiedHtml
                    // );
                    // $modifiedHtml = str_replace(
                    //     "href='{$escapedOriginalHref}'",
                    //     "href='{$escapedTrackingUrl}'",
                    //     $modifiedHtml
                    // );
                }
            });
            // DOM操作で属性を書き換えた場合、書き換え後のHTMLを取得
            // $modifiedHtml = $crawler->html();
            // ただし、上記 $links->each() 内での $node->getNode(0)->setAttribute() は
            // $crawler オブジェクト自体を変更するので、$crawler->html() で新しいHTMLを取得できます。

            // $links->each() 内でDOMを直接変更するアプローチ
            $crawler->filter('a')->each(function (Crawler $node) {
                $originalHref = $node->attr('href');
                if (
                    $originalHref &&
                    !empty(trim($originalHref)) &&
                    !Str::startsWith(strtolower(trim($originalHref)), ['mailto:', 'tel:', '#', 'javascript:']) &&
                    !Str::contains($originalHref, url('/track/click'))
                ) {

                    $trackingUrl = route('track.click', [
                        'identifier' => $this->messageIdentifier,
                        'url' => $originalHref // ここでは生のURLを渡す (route()が適切に処理してくれる)
                        // Controller側で urldecode() する必要がある場合は注意
                    ]);
                    $node->getNode(0)->setAttribute('href', $trackingUrl);
                }
            });
            // HTML全体を取得（もし<html><body>タグがなければ追加される）
            // もし $this->mailBodyHtml が完全なHTML文書でない場合（フラグメントの場合）、
            // $crawler->filter('body')->html() や $crawler->html() の挙動に注意
            if ($links->count() > 0) { // リンクがあった場合のみ $crawler->html() を使う
                // $this->mailBodyHtml がHTMLフラグメントの場合、<body>タグ内のHTMLを取得
                if (strpos(strtolower($this->mailBodyHtml), '<body') === false) {
                    $modifiedHtml = $crawler->filter('body')->html();
                } else {
                    $modifiedHtml = $crawler->html();
                }
            }
        }
        // --- リンクの書き換え処理ここまで ---


        // 開封トラッキングピクセルを本文に追加
        $trackingPixelUrl = route('track.open', ['identifier' => $this->messageIdentifier]);
        $trackingPixelHtml = "<img src=\"{$trackingPixelUrl}\" width=\"1\" height=\"1\" alt=\"\" style=\"border:0;height:1px;width:1px;\" />"; // スタイル調整

        $finalHtmlContent = $modifiedHtml . $trackingPixelHtml;

        return new Content(
            htmlString: $finalHtmlContent,
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the message headers.
     *
     * @return \Illuminate\Mail\Mailables\Headers
     */
    public function headers(): \Illuminate\Mail\Mailables\Headers // ★ Laravel 9+
    {
        $bounceAddress = config('mail.bounce_address');
        $headers = new \Illuminate\Mail\Mailables\Headers();
        if ($bounceAddress) {
            // Return-Path は通常、MTAが最終的に設定しますが、
            // Sender ヘッダーを設定することで間接的に影響を与えることがあります。
            // 直接 Return-Path を設定する標準的な方法はMailableのトップレベルにはありません。
            // SwiftMailerインスタンスにアクセスして設定します。
        }
        // ここでX-Headersなどを追加することも可能
        // 例: $headers->addTextHeader('X-SentEmailLog-ID', $this->sentEmailRecordId . '-' . md5($this->recipientEmail));
        return $headers;
    }


    /**
     * SwiftMailerインスタンスに直接アクセスしてReturn-Pathを設定
     * このメソッドはLaravel 8以前の withSwiftMessage に相当するような形で
     * 送信処理のカスタマイズポイントとして利用できますが、
     * Laravel 9以降では Mailer の send メソッドのイベントや、
     * Mailable の events メソッドでメッセージをカスタマイズするのがより現代的です。
     *
     * もし、より直接的にReturn-Pathを設定したい場合、
     * Symfony Mailerを使用するLaravel 9以降では、
     * 送信イベントをリッスンしてメッセージを操作するのが一つの方法です。
     *
     * ここでは、Mailableの送信パイプラインにフックする簡単な代替案として、
     * カスタムヘッダーを通じてMTAにヒントを与えるか、
     * または、実際には`config/mail.php`の`sendmail`パス設定や
     * SMTPトランスポートの設定でReturn-Pathを指定する方法がより一般的です。
     *
     * 今回は、多くのMTAが`Sender`ヘッダーや`From`ヘッダーに基づいて
     * Return-Pathを適切に設定することを期待し、
     * 明示的なReturn-Path設定は省略し、config/mail.php の 'from' アドレスや
     * .env の MAIL_FROM_ADDRESS がバウンス処理において適切に機能することを前提とします。
     *
     * もし厳密なReturn-Path制御が必要な場合は、MTA側の設定か、
     * LaravelのMail Sendingイベントを利用したカスタマイズが必要になります。
     *
     * ここでは、送信時にユニークIDをヘッダーに埋め込む例を示します。
     * これによりバウンスメール解析時にどの送信に対応するか特定しやすくなります。
     */
    public function build()
    {
        $bounceAddress = config('mail.bounce_address');

        $this->withSymfonyMessage(function (\Symfony\Component\Mime\Email $message) use ($bounceAddress) {
            if ($bounceAddress) {
                $message->returnPath($bounceAddress);
            }
            $message->getHeaders()->addTextHeader('X-Mailer-Sent-Email-Record-ID', (string)$this->sentEmailRecordId); // 文字列にキャスト
            $message->getHeaders()->addTextHeader('X-Mailer-Recipient-Email', $this->recipientEmail);
            $message->getHeaders()->addTextHeader('X-Mailer-Message-Identifier', $this->messageIdentifier); // ★ 追加
        });
        return $this;
    }
}
