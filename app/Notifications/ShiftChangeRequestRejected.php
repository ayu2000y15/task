<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ShiftChangeRequest;

class ShiftChangeRequestRejected extends Notification
{
    use Queueable;

    protected $shiftRequest;

    public function __construct(ShiftChangeRequest $shiftRequest)
    {
        $this->shiftRequest = $shiftRequest;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $requestDate = $this->shiftRequest->date->format('n月j日');
        $rejecterName = $this->shiftRequest->approver->name;
        $rejectionReason = $this->shiftRequest->rejection_reason;
        $url = route('schedule.monthly', ['month' => $this->shiftRequest->date->format('Y-m')]);

        return (new MailMessage)
            ->subject("【否認】シフト変更申請が否認されました")
            ->greeting("{$notifiable->name} 様")
            ->line("申請されていたシフト変更が否認されました。")
            ->line("対象日：{$requestDate}")
            ->line("処理者：{$rejecterName}")
            ->line("否認理由：")
            ->line($rejectionReason) // 否認理由を本文に記載
            ->action('スケジュールを確認する', $url)
            ->line('ご確認の上、必要に応じて再度ご調整ください。');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->shiftRequest->id,
            'message' => "{$this->shiftRequest->date->format('n/j')}のシフト変更申請が否認されました。",
            'url' => route('schedule.monthly', ['month' => $this->shiftRequest->date->format('Y-m')]),
        ];
    }
}
