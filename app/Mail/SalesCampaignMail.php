<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // ★★★ この行を追加 ★★★
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class SalesCampaignMail extends Mailable implements ShouldQueue // ★★★ `implements ShouldQueue` を追加 ★★★
{
    use Queueable, SerializesModels;

    public string $mailSubject;
    public string $mailBodyHtml;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $htmlContent)
    {
        $this->mailSubject = $subject;
        $this->mailBodyHtml = $htmlContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->mailBodyHtml,
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
}
