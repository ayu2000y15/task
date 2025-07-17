<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\FormFieldCategory;

class ExternalFormCompletionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public FormFieldCategory $formCategory,
        public array $submissionData,
        public string $userEmail,
        public string $userName = ''
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->formCategory->form_title
                ? $this->formCategory->form_title . ' - 送信完了'
                : $this->formCategory->display_name . ' - 送信完了',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.external_form_completion',
            text: 'emails.external_form_completion_text',
            with: [
                'formCategory' => $this->formCategory,
                'submissionData' => $this->submissionData,
                'userEmail' => $this->userEmail,
                'userName' => $this->userName,
            ]
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
