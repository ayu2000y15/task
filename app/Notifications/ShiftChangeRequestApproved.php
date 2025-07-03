<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ShiftChangeRequest;

class ShiftChangeRequestApproved extends Notification
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
        $approverName = $this->shiftRequest->approver->name;
        $url = route('schedule.monthly', ['month' => $this->shiftRequest->date->format('Y-m')]);

        return (new MailMessage)
            ->subject("【承認】シフト変更申請が承認されました")
            ->greeting("{$notifiable->name} 様")
            ->line("申請されていたシフト変更が承認されました。")
            ->line("対象日：{$requestDate}")
            ->line("承認者：{$approverName}")
            ->line("更新されたスケジュールがカレンダーに反映されています。")
            ->action('スケジュールを確認する', $url)
            ->line('ご確認のほど、よろしくお願いいたします。');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->shiftRequest->id,
            'message' => "{$this->shiftRequest->date->format('n/j')}のシフト変更申請が承認されました。",
            'url' => route('schedule.monthly', ['month' => $this->shiftRequest->date->format('Y-m')]),
        ];
    }
}
