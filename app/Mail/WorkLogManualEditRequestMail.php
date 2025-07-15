<?php
namespace App\Mail;

use App\Models\WorkLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WorkLogManualEditRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $manualLog;

    public function __construct(WorkLog $manualLog)
    {
        $this->manualLog = $manualLog;
    }

    public function build()
    {
        return $this->subject('作業ログ手修正申請が届きました')
            ->view('emails.work_logs.manual_edit_request');
    }
}
