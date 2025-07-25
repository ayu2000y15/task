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
        // 1. プレースホルダーを置換
        $personalizedHtmlContent = $this->personalizeContent($this->baseBodyHtml);
        $modifiedHtml = $personalizedHtmlContent; // 変更後のHTMLを格納する変数を初期化

        try {
            // 2. DomCrawlerでHTMLをパース
            $crawler = new Crawler($personalizedHtmlContent);

            // 3. 本文中のテキストURLを<a>タグに変換する
            // <a>や<style>タグの中は除外して処理する
            $this->autolinkPlainTextUrls($crawler);

            // 4.すべての<a>タグ(元からあったものと、上記で新規作成したもの両方)の
            //    href属性をトラッキング用に書き換える
            $links = $crawler->filter('a');
            if ($links->count() > 0) {
                $links->each(function (Crawler $node) {
                    $originalHref = $node->attr('href');

                    // トラッキング対象外のリンク（mailto:, tel: など）は除外
                    if (
                        $originalHref &&
                        !empty(trim($originalHref)) &&
                        !Str::of(trim($originalHref))->lower()->startsWith(['mailto:', 'tel:', '#', 'javascript:']) &&
                        !Str::contains($originalHref, url('/track/click')) &&
                        !Str::contains($originalHref, url('/track/open'))
                    ) {
                        $trackingUrl = route('track.click', [
                            'identifier' => $this->messageIdentifier,
                            'url' => $originalHref
                        ]);
                        $node->getNode(0)->setAttribute('href', $trackingUrl);
                    }
                });
            }

            // 5. 変更が適用されたHTMLを安全に再生成
            $modifiedHtml = $crawler->filter('body')->html();
        } catch (\Exception $e) {
            // パースエラーが発生した場合は、元のHTMLをそのまま使用し、エラーをログに記録
            \Illuminate\Support\Facades\Log::error('SalesCampaignMail HTML processing failed: ' . $e->getMessage(), [
                'sent_email_id' => $this->sentEmailRecordId,
                'html' => Str::limit($personalizedHtmlContent, 300)
            ]);
            $modifiedHtml = $personalizedHtmlContent; // エラー時は変更前のHTMLに戻す
        }


        // 開封トラッキングピクセルを追加
        $trackingPixelUrl = route('track.open', ['identifier' => $this->messageIdentifier]);
        $trackingPixelHtml = "<img src=\"{$trackingPixelUrl}\" width=\"1\" height=\"1\" alt=\"\" style=\"border:0;height:1px;width:1px;position:absolute;left:-9999px;\" />";

        // 配信停止リンクを追加
        $unsubscribeUrl = route('unsubscribe.confirm', ['identifier' => $this->messageIdentifier]);
        $unsubscribeHtml = "<br><br><p style=\"text-align:center;font-size:10px;color:#888888;margin-top:20px;\">";
        $unsubscribeHtml .= "このメールの配信停止をご希望の場合は、<a href=\"{$unsubscribeUrl}\" target=\"_blank\" style=\"color:#888888;text-decoration:underline;\">こちら</a>をクリックしてください。";
        $unsubscribeHtml .= "</p>";

        // 最終的なHTMLコンテンツを組み立て
        $finalHtmlContent = $modifiedHtml . $unsubscribeHtml . $trackingPixelHtml;

        return new Content(
            htmlString: $finalHtmlContent,
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

    /**
     * DOM内のプレーンテキストURLを検索し、<a>タグに変換します。
     *
     * @param Crawler $crawler
     */
    private function autolinkPlainTextUrls(Crawler $crawler): void
    {
        // <a>, <style>, <script> タグ内ではないテキストノードをXPathで取得
        $textNodes = $crawler->filterXPath('//text()[not(ancestor::a) and not(ancestor::style) and not(ancestor::script)]');

        foreach ($textNodes as $node) {
            // 正規表現でURLを検出
            $pattern = '/(https?:\/\/[^\s<>"\'`]+[a-zA-Z0-9\/])/i';
            $text = $node->nodeValue;

            // テキストをURLとそれ以外の部分に分割
            $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            // 分割後の要素が2つ以上あれば、URLが見つかったと判断
            if (is_array($parts) && count($parts) > 1) {
                $parentNode = $node->parentNode;
                $doc = $node->ownerDocument;

                foreach ($parts as $part) {
                    // 部分がURLなら<a>タグを生成
                    if (preg_match($pattern, $part)) {
                        $link = $doc->createElement('a', $part);
                        $link->setAttribute('href', $part);
                        $parentNode->insertBefore($link, $node);
                    } else {
                        // URLでなければ、そのままテキストノードとして挿入
                        $textNode = $doc->createTextNode($part);
                        $parentNode->insertBefore($textNode, $node);
                    }
                }
                // 元の結合されたテキストノードを削除
                $parentNode->removeChild($node);
            }
        }
    }
}
