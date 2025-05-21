<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactSendmail extends Mailable
{
    use Queueable, SerializesModels;

    public $referenceNumber;
    public $contactCategoryName;
    public $companyName;
    public $name;
    public $age;
    public $mail;
    public $tel;
    public $subject;
    public $subject2;
    public $content;
    public $root;
    public $mailTo;

    public $mailCc = null;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($content, $category, $root, $sendMail)
    {
        $this->root = $root;
        if ($root == 'mail') {
            $this->subject = '送信が完了しました 【' . $content['subject'] . '】';
            $this->mailTo = $content['email'];
        } else {
            $this->subject = 'HPから問い合わせを受け付けました 【問い合わせ番号：' . $content['reference_number'] . '】';
            if (!is_null($sendMail)) {
                $this->mailTo = $sendMail['ITEM'];
            }
        }

        $this->referenceNumber = $content['reference_number'];
        $this->contactCategoryName = $category;
        $this->mail = $content['email'];
        $this->name = $content['name'];
        $this->subject2 = $content['subject'];
        $this->content = $content['message'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //ccがある場合はこっち
        if ($this->mailCc <> null) {
            return $this
                ->to($this->mailTo)
                ->cc($this->mailCc)
                ->subject($this->subject)
                ->text($this->root)
                ->with([
                    'contactCategoryName' => $this->contactCategoryName,
                    'name' => $this->name,
                    'email' => $this->mail,
                    'subject' => '【' . $this->subject2 . '】',
                    'content' => $this->content
                ]);
        }
        return $this
            ->to($this->mailTo)
            ->subject($this->subject)
            ->text($this->root)
            ->with([
                'contactCategoryName' => $this->contactCategoryName,
                'name' => $this->name,
                'email' => $this->mail,
                'subject' => '【' . $this->subject2 . '】',
                'content' => $this->content
            ]);
    }
}
