<?php
namespace App\Mail;

use App\Models\WorkLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WorkLogManualEditResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public $manualLog;
    public $approved;

    public function __construct(WorkLog $manualLog, bool $approved)
    {
        $this->manualLog = $manualLog;
        $this->approved = $approved;
    }

    public function build()
    {
        $subject = $this->approved ? '作業ログ手修正が承認されました' : '作業ログ手修正が拒否されました';
        return $this->subject($subject)
            ->view('emails.work_logs.manual_edit_result');
    }
}
