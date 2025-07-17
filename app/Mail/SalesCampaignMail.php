<?php

namespace App\Mail;

use App\Models\Subscriber; // Subscriberモデルをuse
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler; // DomCrawler を use

class SalesCampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $baseSubject;       // 置換前の元の件名
    public string $baseBodyHtml;      // 置換前の元のHTML本文
    public int $sentEmailRecordId;    // SentEmailのID
    public string $recipientEmail;     // 受信者のメールアドレス
    public string $messageIdentifier; // 送信ごとのユニークID (トラッキング用)
    public array $subscriberData;     // 購読者の属性情報

    /**
     * Create a new message instance.
     * @param string $subject         元の件名
     * @param string $htmlContent     元のHTML本文
     * @param int $sentEmailRecordId  SentEmailのID
     * @param string $recipientEmail  受信者のメールアドレス
     * @param Subscriber $subscriber  購読者モデルのインスタンス
     */
    public function __construct(string $subject, string $htmlContent, int $sentEmailRecordId, string $recipientEmail, Subscriber $subscriber)
    {
        $this->baseSubject = $subject;
        $this->baseBodyHtml = $htmlContent;
        $this->sentEmailRecordId = $sentEmailRecordId;
        $this->recipientEmail = $recipientEmail;
        $this->messageIdentifier = $this->sentEmailRecordId . '_' . Str::uuid()->toString();

        // Subscriberに紐づくManagedContactの情報を取得
        $contact = $subscriber->managedContact;

        // 購読者データをプレースホルダー置換用に配列として保持
        // ManagedContactが存在する場合、その情報を使用する
        $this->subscriberData = [
            'email' => $subscriber->email, // Subscriberモデルが持つメールアドレスを正とする
            'name' => $contact ? $contact->name : null,
            'company_name' => $contact ? $contact->company_name : null,
            'postal_code' => $contact ? $contact->postal_code : null,
            'address' => $contact ? $contact->address : null,
            'phone_number' => $contact ? $contact->phone_number : null,
            'fax_number' => $contact ? $contact->fax_number : null,
            'url' => $contact ? $contact->url : null,
            'representative_name' => $contact ? $contact->representative_name : null,
            // 日付が設定されている場合のみフォーマットする
            'establishment_date' => $contact ? $contact->establishment_date : null,
            'industry' => $contact ? $contact->industry : null,
        ];
    }

    /**
     * プレースホルダーを実際の値に置き換えるヘルパーメソッド
     * @param string|null $content
     * @return string
     */
    protected function personalizeContent(?string $content): string
    {
        if ($content === null) {
            return '';
        }
        $processedContent = $content;
        foreach ($this->subscriberData as $key => $value) {
            $replacement = $value ?? ''; // nullの場合は空文字に
            $processedContent = str_replace("{{{$key}}}", $replacement, $processedContent);
            $processedContent = str_replace("{{ {$key} }}", $replacement, $processedContent); // スペースありのパターン
        }
        return $processedContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $personalizedSubject = $this->personalizeContent($this->baseSubject);
        return new Envelope(
            subject: $personalizedSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $personalizedHtmlContent = $this->personalizeContent($this->baseBodyHtml);

        // --- クリックトラッキング用リンク書き換え処理 ---
        $pattern = '/(?<!href="|src="|>)(https?:\/\/[^\s<]+)/i';
        $replacement = '<a href="$1">$1</a>';
        $personalizedHtmlContent = preg_replace($pattern, $replacement, $personalizedHtmlContent);

        $crawler = new Crawler($personalizedHtmlContent);
        $modifiedHtmlForLinks = $personalizedHtmlContent;

        if ($crawler->filter('a')->count() > 0) {
            $crawler->filter('a')->each(function (Crawler $node) {
                $originalHref = $node->attr('href');
                if (
                    $originalHref &&
                    !empty(trim($originalHref)) &&
                    !Str::startsWith(strtolower(trim($originalHref)), ['mailto:', 'tel:', '#', 'javascript:']) &&
                    !Str::contains($originalHref, url('/track/click')) && // 既にトラッキングURLでないか
                    !Str::contains($originalHref, url('/track/open'))   // 開封トラッキングピクセルも除外
                ) {
                    $trackingUrl = route('track.click', [
                        'identifier' => $this->messageIdentifier,
                        'url' => $originalHref // 元のURLはエンコードせずに渡す (routeヘルパーが処理)
                    ]);
                    $node->getNode(0)->setAttribute('href', $trackingUrl);
                }
            });
            // $this->baseBodyHtml が完全なHTMLドキュメントかフラグメントかで取得方法を調整
            if (strpos(strtolower($personalizedHtmlContent), '<body') === false) {
                $modifiedHtmlForLinks = $crawler->filter('body')->html(); // bodyタグの中身だけ取得
            } else {
                $modifiedHtmlForLinks = $crawler->html(); // 完全なHTMLとして取得
            }
        }
        // --- リンクの書き換え処理ここまで ---

        // 開封トラッキングピクセルを追加
        $trackingPixelUrl = route('track.open', ['identifier' => $this->messageIdentifier]);
        $trackingPixelHtml = "<img src=\"{$trackingPixelUrl}\" width=\"1\" height=\"1\" alt=\"\" style=\"border:0;height:1px;width:1px;position:absolute;left:-9999px;\" />";

        // ▼▼▼ 配信停止リンクを追加 ▼▼▼
        $unsubscribeUrl = route('unsubscribe.confirm', ['identifier' => $this->messageIdentifier]); // ★★★ routeの行き先を 'unsubscribe.confirm' に変更 ★★★
        // シンプルなフッターの例
        $unsubscribeHtml = "<br><br><p style=\"text-align:center;font-size:10px;color:#888888;margin-top:20px;\">";
        $unsubscribeHtml .= "このメールの配信停止をご希望の場合は、<a href=\"{$unsubscribeUrl}\" target=\"_blank\" style=\"color:#888888;text-decoration:underline;\">こちら</a>をクリックしてください。";
        $unsubscribeHtml .= "</p>";
        // ▲▲▲ ここまで ▲▲▲

        // 最終的なHTMLコンテンツ (本文 + 配信停止リンク + 開封トラッキングピクセル)
        // 配信停止リンクは本文の最後、トラッキングピクセルはさらにその最後に配置するのが一般的
        $finalHtmlContent = $modifiedHtmlForLinks . $unsubscribeHtml . $trackingPixelHtml;

        // プレーンテキスト版も用意する場合は、そちらもパーソナライズ処理を行う
        // $baseBodyText = ... (もしあれば)
        // $personalizedTextContent = $this->personalizeContent($baseBodyText);

        return new Content(
            htmlString: $finalHtmlContent,
            // textString: $personalizedTextContent,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * カスタムヘッダーの設定など (Return-Path, Message Identifierなど)
     */
    public function build()
    {
        $bounceAddress = config('mail.bounce_address');

        $this->withSymfonyMessage(function (\Symfony\Component\Mime\Email $message) use ($bounceAddress) {
            if ($bounceAddress) {
                $message->returnPath($bounceAddress);
            }
            // 送信メールと受信者を特定するためのカスタムヘッダー
            $message->getHeaders()->addTextHeader('X-Mailer-Sent-Email-Record-ID', (string)$this->sentEmailRecordId);
            $message->getHeaders()->addTextHeader('X-Mailer-Recipient-Email', $this->recipientEmail);
            // バウンスメールとSentEmailLogを紐付けるためのユニークID
            $message->getHeaders()->addTextHeader('X-Mailer-Message-Identifier', $this->messageIdentifier);
        });
        return $this; // buildメソッドはMailableインスタンスを返す必要あり
    }
}
