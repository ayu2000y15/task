<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ShiftChangeRequest;

class ShiftChangeRequested extends Notification
{
    use Queueable;

    protected $shiftRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(ShiftChangeRequest $shiftRequest)
    {
        $this->shiftRequest = $shiftRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // メールとデータベースの両方で通知
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $requesterName = $this->shiftRequest->user->name;
        $requestDate = $this->shiftRequest->date->format('n月j日');
        $url = route('shift-change-requests.index'); // 承認者が確認する一覧ページのURL

        return (new MailMessage)
            ->subject("【要承認】シフト変更申請のお知らせ")
            ->greeting("{$notifiable->name} 様")
            ->line("{$requesterName} さんからシフト変更の申請がありました。")
            ->line("対象日：{$requestDate}")
            ->line("内容を確認し、承認または否認の処理を行ってください。")
            ->action('申請を確認する', $url)
            ->line('よろしくお願いいたします。');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->shiftRequest->id,
            'message' => "{$this->shiftRequest->user->name}さんからシフト変更申請が届きました。",
            'url' => route('shift-change-requests.index'),
        ];
    }
}
