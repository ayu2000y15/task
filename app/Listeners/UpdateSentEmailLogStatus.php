<?php

namespace App\Listeners;

use App\Models\SentEmailLog;
use App\Models\SentEmail;
use App\Mail\SalesCampaignMail;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Mail\SendQueuedMailable;
use Symfony\Component\Mime\MessageConverter; // ★ Symfony MessageConverter を use

class UpdateSentEmailLogStatus
{
    public function __construct()
    {
        //
    }

    public function handleJobProcessed(JobProcessed $event): void
    {
        Log::info('UpdateSentEmailLogStatus: handleJobProcessed CALLED.');
        $payload = $event->job->payload();
        $jobCommand = unserialize($payload['data']['command']);

        if ($jobCommand instanceof SendQueuedMailable && $jobCommand->mailable instanceof SalesCampaignMail) {
            $mailableInstance = $jobCommand->mailable;

            Log::info('UpdateSentEmailLogStatus: SalesCampaignMail job processed via SendQueuedMailable.', [
                'sentEmailRecordId' => $mailableInstance->sentEmailRecordId ?? 'N/A',
                'recipientEmail' => $mailableInstance->recipientEmail ?? 'N/A'
            ]);

            if (isset($mailableInstance->recipientEmail) && isset($mailableInstance->sentEmailRecordId)) {
                $sentEmailLog = SentEmailLog::where('sent_email_id', $mailableInstance->sentEmailRecordId)
                    ->where('recipient_email', $mailableInstance->recipientEmail)
                    ->where('status', 'queued') // キュー投入時のログを検索
                    ->first();

                if ($sentEmailLog) {
                    $sentEmailLog->status = 'sent'; // MTAへの引き渡し成功
                    $sentEmailLog->processed_at = now();
                    $sentEmailLog->error_message = null;

                    // ▼▼▼ Message-IDの取得と保存 ▼▼▼
                    // JobProcessedイベントの$event->jobオブジェクトからSymfonyのEmailオブジェクトを取得し、Message-IDを取得する
                    // これはLaravelのバージョンや内部構造に依存する可能性あり
                    // $event->job->getSymfonyMessage() のようなメソッドがあれば理想的だが、直接は提供されていないことが多い。
                    // Mailableが実際に送信された後のMessageオブジェクトからIDを取得する必要がある。
                    // MailableにMessage IDを保持するプロパティを追加し、送信イベントでセットするのが一つの方法。
                    // ここでは、Mailableの `$messageIdentifier` を `original_message_id` として利用することを試みます。
                    // （より正確には、実際の Message-ID を使うべきですが、Mailable に渡したユニークIDを代用します）
                    // SalesCampaignMail の $messageIdentifier が送信ごとのユニークIDなのでこれを使用
                    if (!empty($mailableInstance->messageIdentifier)) {
                        $sentEmailLog->original_message_id = $mailableInstance->messageIdentifier;
                    }
                    // ▲▲▲ ここまで ▲▲▲

                    $sentEmailLog->save();
                    Log::info("SentEmailLog ID {$sentEmailLog->id} for {$mailableInstance->recipientEmail} (SentEmail ID {$mailableInstance->sentEmailRecordId}) updated to 'sent'. Message Identifier: " . ($mailableInstance->messageIdentifier ?? 'N/A'));
                    $this->updateParentSentEmailStatus($mailableInstance->sentEmailRecordId);
                } else {
                    Log::warning('UpdateSentEmailLogStatus: No matching "queued" SentEmailLog found for processed job.', [
                        'sentEmailRecordId' => $mailableInstance->sentEmailRecordId ?? 'N/A',
                        'recipientEmail' => $mailableInstance->recipientEmail ?? 'N/A',
                    ]);
                }
            } else {
                Log::warning('UpdateSentEmailLogStatus: SalesCampaignMail job processed but required IDs not found in Mailable properties.', (array)($mailableInstance ?? []));
            }
        } else {
            Log::info('UpdateSentEmailLogStatus: Processed job is not a relevant SalesCampaignMail via SendQueuedMailable.', [
                'job_name' => $event->job->resolveName(),
                'command_class' => is_object($jobCommand) ? get_class($jobCommand) : 'Not an object'
            ]);
        }
    }

    public function handleJobFailed(JobFailed $event): void
    {
        Log::info('UpdateSentEmailLogStatus: handleJobFailed CALLED.');
        $payload = $event->job->payload();
        $jobCommand = unserialize($payload['data']['command']);
        $errorMessage = Str::limit($event->exception->getMessage(), 1000);

        if ($jobCommand instanceof SendQueuedMailable && $jobCommand->mailable instanceof SalesCampaignMail) {
            $mailableInstance = $jobCommand->mailable;

            Log::info('UpdateSentEmailLogStatus: SalesCampaignMail job failed via SendQueuedMailable.', [
                'sentEmailRecordId' => $mailableInstance->sentEmailRecordId ?? 'N/A',
                'recipientEmail' => $mailableInstance->recipientEmail ?? 'N/A',
                'error' => $errorMessage
            ]);

            if (isset($mailableInstance->recipientEmail) && isset($mailableInstance->sentEmailRecordId)) {
                $sentEmailLog = SentEmailLog::where('sent_email_id', $mailableInstance->sentEmailRecordId)
                    ->where('recipient_email', $mailableInstance->recipientEmail)
                    ->where('status', 'queued') // キュー投入時のログを検索
                    ->first();

                if ($sentEmailLog) {
                    $sentEmailLog->status = 'failed'; // キューワーカーによる処理失敗
                    $sentEmailLog->processed_at = now();
                    $sentEmailLog->error_message = $errorMessage;
                    // original_message_id はこの時点では不明、または $mailableInstance->messageIdentifier を使う
                    if (!empty($mailableInstance->messageIdentifier)) {
                        $sentEmailLog->original_message_id = $mailableInstance->messageIdentifier;
                    }
                    $sentEmailLog->save();
                    Log::error("SentEmailLog ID {$sentEmailLog->id} for {$mailableInstance->recipientEmail} (SentEmail ID {$mailableInstance->sentEmailRecordId}) updated to 'failed'. Error: " . $errorMessage);
                    $this->updateParentSentEmailStatus($mailableInstance->sentEmailRecordId);
                } else {
                    Log::warning('UpdateSentEmailLogStatus: No matching "queued" SentEmailLog found for failed job.', [
                        'sentEmailRecordId' => $mailableInstance->sentEmailRecordId ?? 'N/A',
                        'recipientEmail' => $mailableInstance->recipientEmail ?? 'N/A',
                    ]);
                }
            } else {
                Log::warning('UpdateSentEmailLogStatus: SalesCampaignMail job failed but required IDs not found in Mailable properties.', (array)($mailableInstance ?? []));
            }
        } else {
            Log::info('UpdateSentEmailLogStatus: Failed job is not a relevant SalesCampaignMail via SendQueuedMailable.', [
                'job_name' => $event->job->resolveName(),
                'command_class' => is_object($jobCommand) ? get_class($jobCommand) : 'Not an object',
                'error' => $errorMessage
            ]);
        }
    }

    // ... (updateParentSentEmailStatus, subscribe メソッドは変更なし) ...
    protected function updateParentSentEmailStatus(int $sentEmailId): void
    {
        $sentEmail = SentEmail::with('recipientLogs')->find($sentEmailId);
        if (!$sentEmail) return;
        $totalLogs = $sentEmail->recipientLogs->count();
        if ($totalLogs === 0) return;
        $successful = $sentEmail->recipientLogs->where('status', 'sent')->count();
        $bouncedOrFailed = $sentEmail->recipientLogs->whereIn('status', ['failed', 'bounced', 'queue_failed'])->count();
        $skipped = $sentEmail->recipientLogs->where('status', 'skipped_blacklist')->count();
        $stillQueued = $sentEmail->recipientLogs->where('status', 'queued')->count();
        $newStatus = $sentEmail->status;
        if ($stillQueued === 0) {
            $nonSkippedTotal = $totalLogs - $skipped;
            if ($nonSkippedTotal <= 0 && $skipped > 0) {
                $newStatus = 'all_skipped';
            } elseif ($successful === $nonSkippedTotal && $nonSkippedTotal > 0) {
                $newStatus = 'completed_sent';
            } elseif ($bouncedOrFailed === $nonSkippedTotal && $nonSkippedTotal > 0) {
                $newStatus = 'all_failed_or_bounced';
            } elseif ($successful > 0 || $bouncedOrFailed > 0) {
                $newStatus = 'partially_completed';
            } elseif ($nonSkippedTotal === 0 && $skipped === 0) {
                $newStatus = 'no_recipients_processed';
            }
        } else {
            $newStatus = 'processing';
        }
        if ($sentEmail->status !== $newStatus) {
            $sentEmail->status = $newStatus;
            $sentEmail->save();
            Log::info("Parent SentEmail ID {$sentEmailId} status updated to '{$newStatus}'. Successful: {$successful}, Failed: {$bouncedOrFailed}, Skipped: {$skipped}, Still Queued: {$stillQueued}, Total Logs: {$totalLogs}");
        }
    }

    public function subscribe($events): void
    {
        $events->listen(
            JobProcessed::class,
            [UpdateSentEmailLogStatus::class, 'handleJobProcessed']
        );
        $events->listen(
            JobFailed::class,
            [UpdateSentEmailLogStatus::class, 'handleJobFailed']
        );
    }
}
