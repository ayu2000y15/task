<?php

namespace App\Console\Commands;

use App\Models\BlacklistEntry;
use Illuminate\Console\Command;
use App\Models\SentEmailLog;
use App\Models\Subscriber; // Subscriberモデルのuseは残しても問題ありませんが、直接の更新はなくなります
use App\Models\SentEmail;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\Header;
use Illuminate\Support\Str;

class ProcessBouncedEmails extends Command
{
    protected $signature = 'emails:process-bounces';
    protected $description = 'Checks the bounce mailbox, updates sent email log statuses, and blacklists hard bounces.'; // 説明を調整

    public function handle(): int
    {
        $this->info('Starting to process bounced emails...');
        Log::info('ProcessBouncedEmails command started.');

        $config = [
            'host'          => config('mail.bounce_mailbox.host'),
            'port'          => config('mail.bounce_mailbox.port'),
            'encryption'    => config('mail.bounce_mailbox.encryption'),
            'validate_cert' => config('mail.bounce_mailbox.validate_cert', true),
            'username'      => config('mail.bounce_mailbox.username'),
            'password'      => config('mail.bounce_mailbox.password'),
            'protocol'      => config('mail.bounce_mailbox.protocol', 'imap'),
        ];

        if (empty($config['host']) || empty($config['username']) || empty($config['password'])) {
            $this->error('Bounce mailbox connection details are not configured correctly.');
            Log::error('ProcessBouncedEmails: Bounce mailbox connection details not configured.');
            return Command::FAILURE;
        }

        try {
            $clientManager = new ClientManager();
            $client = $clientManager->make($config);
            $client->connect();
            $this->info("Successfully connected to the bounce mailbox: " . $config['host']);

            $inbox = $client->getFolder('INBOX');
            $messages = $inbox->messages()->unseen()->leaveUnread(false)->get();

            if ($messages->isEmpty()) {
                $this->info('No new bounced emails to process.');
                Log::info('ProcessBouncedEmails: No new messages found.');
                return Command::SUCCESS;
            }

            $this->info("Found {$messages->count()} new messages to process.");
            $processedCount = 0;
            $updatedLogCount = 0;
            // $updatedSubscriberCount = 0; // ★ 購読者更新数のカウントを削除
            $blacklistedCount = 0;

            foreach ($messages as $message) {
                /** @var \Webklex\PHPIMAP\Message $message */
                $uid = $message->getUid();
                $messageSubjectHeader = $message->getSubject();
                $subjectString = $messageSubjectHeader instanceof Header ? $messageSubjectHeader->toString() : (is_string($messageSubjectHeader) ? $messageSubjectHeader : 'N/A');
                $this->line("Processing message UID: {$uid} Subject: {$subjectString}");
                Log::info("ProcessBouncedEmails: Processing UID {$uid}, Subject: {$subjectString}");

                $messageIdentifier = null;
                $sentEmailLog = null;
                $processedThisMessageByFallback = false;
                $parsedBounceReasonFromBody = null;

                // Attempt 1-4: Find X-Mailer-Message-Identifier (既存のロジック)
                $customIdentifierHeader = $message->getHeader('x-mailer-message-identifier');
                if ($customIdentifierHeader instanceof Header && $customIdentifierHeader->count() > 0) {
                    $messageIdentifier = trim($customIdentifierHeader->first());
                    Log::info("ProcessBouncedEmails (UID {$uid}): Found X-Mailer-Message-Identifier in direct headers: {$messageIdentifier}");
                }
                if (empty($messageIdentifier) && $message->hasAttachments()) {
                    $attachments = $message->getAttachments();
                    if (is_iterable($attachments)) {
                        foreach ($attachments as $attachment) {
                            if (is_object($attachment) && method_exists($attachment, 'getContentType') && strtolower($attachment->getContentType()) == 'message/rfc822') {
                                $content = $attachment->getContent();
                                if (preg_match('/^X-Mailer-Message-Identifier:\s*(.*)$/im', (string)$content, $matches)) {
                                    $messageIdentifier = trim($matches[1]);
                                    Log::info("ProcessBouncedEmails (UID {$uid}): Found X-Mailer-Message-Identifier in attachment: {$messageIdentifier}");
                                    break;
                                }
                            }
                        }
                    }
                }
                if (empty($messageIdentifier)) {
                    $parts = $message->getParts();
                    if (is_iterable($parts)) {
                        foreach ($parts as $part) {
                            if (is_object($part) && isset($part->content_type) && strtolower($part->content_type) == 'message/rfc822' && isset($part->content)) {
                                if (preg_match('/^X-Mailer-Message-Identifier:\s*(.*)$/im', (string)$part->content, $matches)) {
                                    $messageIdentifier = trim($matches[1]);
                                    Log::info("ProcessBouncedEmails (UID {$uid}): Found X-Mailer-Message-Identifier in part: {$messageIdentifier}");
                                    break;
                                }
                            }
                        }
                    }
                }
                if (empty($messageIdentifier)) {
                    $bodyForSearch = ($message->hasTextBody() ? $message->getTextBody() : "") . "\n" . ($message->hasHTMLBody() ? $message->getHTMLBody() : "");
                    if (!empty($bodyForSearch)) {
                        Log::debug("ProcessBouncedEmails (UID {$uid}): Searching for X-Mailer-Message-Identifier in body.", ['body_snippet' => Str::limit($bodyForSearch, 200)]);
                        if (preg_match('/(?:^|\n|\r)\s*X-Mailer-Message-Identifier:\s*([^\s\n\r]+)/im', $bodyForSearch, $matches)) {
                            $messageIdentifier = trim($matches[1]);
                            Log::info("ProcessBouncedEmails (UID {$uid}): Found X-Mailer-Message-Identifier in body: {$messageIdentifier}");
                        }
                    }
                }

                // --- Main Processing Logic ---
                if (!empty($messageIdentifier)) {
                    $sentEmailLog = SentEmailLog::where('message_identifier', $messageIdentifier)
                        ->whereIn('status', ['queued', 'sent'])
                        ->first();
                    if (!$sentEmailLog) {
                        Log::warning("ProcessBouncedEmails (UID {$uid}): Identifier '{$messageIdentifier}' found, but no matching SentEmailLog. Bounce not processed against a log for this identifier.");
                    }
                } else {
                    // --- Attempt 5: Fallback - Identify by recipient email in bounce body ---
                    Log::info("ProcessBouncedEmails (UID {$uid}): X-Mailer-Message-Identifier not found. Attempting fallback by recipient email.");
                    $bouncedRecipientEmail = null;
                    $parsedBounceReasonFromBody = "Bounce (UID: {$uid})";

                    $fallbackBodySearch = "";
                    $textBody = $message->getTextBody();
                    $htmlBody = $message->getHTMLBody();
                    if (is_string($textBody) && !empty($textBody)) $fallbackBodySearch .= $textBody;
                    if (is_string($htmlBody) && !empty($htmlBody)) $fallbackBodySearch .= "\n" . $htmlBody;

                    if (!empty($fallbackBodySearch)) {
                        Log::debug("ProcessBouncedEmails (UID {$uid}): Fallback search in body.", ['body_snippet' => Str::limit($fallbackBodySearch, 500)]);
                        if (preg_match('/<([^>@\s]+@[^>@\s]+)>[:\s]+(.+)/im', $fallbackBodySearch, $matchesEmailReason)) {
                            $bouncedRecipientEmail = trim($matchesEmailReason[1]);
                            $parsedBounceReasonFromBody = trim($matchesEmailReason[2]);
                            Log::info("ProcessBouncedEmails (UID {$uid}): Fallback parsed recipient '{$bouncedRecipientEmail}' and reason '{$parsedBounceReasonFromBody}' from body (Pattern <email>:reason).");
                        } elseif (preg_match_all('/(?:Failed Recipient|Final-Recipient)\s*:\s*(?:rfc822\s*;\s*)?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $fallbackBodySearch, $recipientMatches)) {
                            if (!empty($recipientMatches[1])) {
                                $bouncedRecipientEmail = trim($recipientMatches[1][0]);
                                if (preg_match('/Diagnostic-Code\s*:\s*[a-zA-Z]+\s*;\s*(.+)/im', $fallbackBodySearch, $diagMatches)) {
                                    $parsedBounceReasonFromBody = trim($diagMatches[1]);
                                } elseif (preg_match('/Status\s*:\s*(\d\.\d\.\d+)/im', $fallbackBodySearch, $statusMatches)) {
                                    $parsedBounceReasonFromBody = "Status: " . trim($statusMatches[1]);
                                }
                                Log::info("ProcessBouncedEmails (UID {$uid}): Fallback parsed recipient '{$bouncedRecipientEmail}' from body (Pattern Final-Recipient). Reason hint: {$parsedBounceReasonFromBody}");
                            }
                        }
                    }

                    if ($bouncedRecipientEmail) {
                        $sentEmailLog = SentEmailLog::where('recipient_email', $bouncedRecipientEmail)
                            ->whereIn('status', ['sent', 'queued'])
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if ($sentEmailLog) {
                            Log::info("ProcessBouncedEmails (UID {$uid}): Fallback found SentEmailLog ID {$sentEmailLog->id} for recipient {$bouncedRecipientEmail}. Base reason: '{$parsedBounceReasonFromBody}'");
                            $processedThisMessageByFallback = true;
                        } else {
                            Log::warning("ProcessBouncedEmails (UID {$uid}): Fallback parsed recipient {$bouncedRecipientEmail}, but no matching 'sent' or 'queued' SentEmailLog found.");
                        }
                    } else {
                        Log::warning("ProcessBouncedEmails (UID {$uid}): Fallback could not parse a recipient email from body.");
                    }
                }

                // --- Process DSN and Update Log (if $sentEmailLog is set by either method) ---
                if ($sentEmailLog) {
                    $baseReasonForDsnParsing = $processedThisMessageByFallback ? ($parsedBounceReasonFromBody ?? "Fallback Bounce (UID: {$uid})") : "Bounce (ID Match; UID: {$uid})";
                    list($finalBounceReason, $isHardBounce) = $this->parseDsnDetailsFromMessage($message, $uid, $subjectString, $baseReasonForDsnParsing);

                    $sentEmailLog->status = 'bounced';
                    $sentEmailLog->error_message = Str::limit($finalBounceReason, 1000);
                    $sentEmailLog->processed_at = now();
                    $sentEmailLog->save();
                    $updatedLogCount++;
                    Log::info("SentEmailLog ID {$sentEmailLog->id} updated to 'bounced'. Reason: {$finalBounceReason}. Hard bounce: " . ($isHardBounce ? "Yes" : "No"));

                    // ★★★ Subscriber のステータス更新処理を削除（またはコメントアウト） ★★★
                    /*
                    if ($sentEmailLog->subscriber) {
                        $newSubscriberStatus = $isHardBounce ? 'bounced_hard' : 'bounced_soft';
                        if ($sentEmailLog->subscriber->status !== $newSubscriberStatus) {
                            $sentEmailLog->subscriber->status = $newSubscriberStatus;
                            $sentEmailLog->subscriber->save();
                            Log::info("Subscriber ID {$sentEmailLog->subscriber_id} status updated to '{$newSubscriberStatus}'.");
                        }
                        // $updatedSubscriberCount++; // ★ 購読者更新数のカウントを削除
                    }
                    */
                    // ★★★ Subscriber のステータス更新処理ここまで ★★★

                    if ($isHardBounce) {
                        $emailToBlacklist = $sentEmailLog->recipient_email;
                        $blacklistEntry = BlacklistEntry::firstOrCreate(
                            ['email' => $emailToBlacklist],
                            [
                                'reason' => Str::limit("Hard bounce: " . $finalBounceReason, 255),
                                'added_by_user_id' => null
                            ]
                        );
                        if ($blacklistEntry->wasRecentlyCreated) {
                            Log::info("ProcessBouncedEmails (UID {$uid}): Email {$emailToBlacklist} added to blacklist due to hard bounce.");
                            $blacklistedCount++;
                        } else {
                            Log::info("ProcessBouncedEmails (UID {$uid}): Email {$emailToBlacklist} was already in blacklist (hard bounce detected again).");
                        }
                    }

                    $this->updateParentSentEmailStatusIfNeeded($sentEmailLog->sent_email_id);

                    $message->setFlag('Seen');
                    $processedCount++;
                } else {
                    Log::warning("ProcessBouncedEmails (UID {$uid}): No SentEmailLog to update. Message will be marked as seen and skipped.", ['subject' => $subjectString, 'identifier_found' => !empty($messageIdentifier)]);
                    $message->setFlag('Seen');
                }
            } // End foreach message

            // ★ ログメッセージから $updatedSubscriberCount を削除
            $this->info("Finished processing. Total new: {$messages->count()}, Processed: {$processedCount}, Updated Logs: {$updatedLogCount}, Added to Blacklist: {$blacklistedCount}.");
            Log::info("ProcessBouncedEmails command finished. Total new: {$messages->count()}, Processed: {$processedCount}, Updated Logs: {$updatedLogCount}, Blacklisted: {$blacklistedCount}.");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("An error occurred while processing bounces: " . $e->getMessage());
            Log::error("ProcessBouncedEmails CRITICAL error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function parseDsnDetailsFromMessage(Message $message, string $uid, string $subjectString, string $defaultReason): array
    {
        $bounceReason = $defaultReason;
        $diagnosticCode = null;
        $isHardBounce = false;

        if (preg_match('/(host unknown|domain name not found|user unknown|no such user|mailbox unavailable|address rejected|does not exist|Host not found)/i', $defaultReason)) {
            $isHardBounce = true;
        }

        $dsnMessageParts = $message->getParts();
        if (is_iterable($dsnMessageParts)) {
            foreach ($dsnMessageParts as $part) {
                if (is_object($part) && isset($part->content_type) && strtolower($part->content_type) == 'message/delivery-status' && isset($part->content)) {
                    $dsnContent = $part->content;
                    if (is_array($dsnContent)) $dsnContent = implode("\n", $dsnContent);

                    if (preg_match('/Diagnostic-Code:\s*smtp;\s*(.*)/im', $dsnContent, $matches)) {
                        $diagnosticCode = trim($matches[1]);
                        $bounceReason = "DSN: " . $diagnosticCode;
                        if (preg_match('/^5\.\d{1,3}\.\d{1,3}/', $diagnosticCode)) $isHardBounce = true;
                        else if (preg_match('/^4\.\d{1,3}\.\d{1,3}/', $diagnosticCode)) $isHardBounce = false;
                    } elseif (preg_match('/Status:\s*([45]\.\d{1,3}\.\d{1,3})/im', $dsnContent, $matchesStatus)) {
                        $bounceReason = "DSN Status: " . $matchesStatus[1];
                        if (Str::startsWith($matchesStatus[1], '5.')) $isHardBounce = true;
                        else if (Str::startsWith($matchesStatus[1], '4.')) $isHardBounce = false;
                    }
                    Log::info("ProcessBouncedEmails (UID {$uid}): Parsed DSN part. Reason: {$bounceReason}, Initial HardBounce: " . ($isHardBounce ? "Yes" : "No"));
                    break;
                }
            }
        } else {
            Log::warning("ProcessBouncedEmails (UID {$uid}): getParts() non-iterable for DSN parsing. Relying on reason: '{$defaultReason}'.", ['subject' => $subjectString]);
        }

        if (!$diagnosticCode) {
            if (preg_match('/(host unknown|domain name not found|user unknown|no such user|mailbox unavailable|address rejected|does not exist|Host not found)/i', $bounceReason)) {
                $isHardBounce = true;
            } elseif (preg_match('/(mailbox full|quota exceeded)/i', $bounceReason)) {
                $isHardBounce = false;
            }
        }
        if (Str::contains($bounceReason, ['Host not found', 'Host or domain name not found', 'Name service error for name='], true) && !$isHardBounce) {
            Log::info("ProcessBouncedEmails (UID {$uid}): Reason '{$bounceReason}' suggests hard bounce, setting isHardBounce to true.");
            $isHardBounce = true;
        }

        return [$bounceReason, $isHardBounce];
    }

    protected function updateParentSentEmailStatusIfNeeded(int $sentEmailId): void
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
        $initialTargetCount = $totalLogs - $skipped;
        if ($stillQueued === 0) {
            if ($initialTargetCount > 0) {
                if ($successful === $initialTargetCount) $newStatus = 'completed_all_sent';
                elseif ($bouncedOrFailed === $initialTargetCount) $newStatus = 'completed_all_failed_or_bounced';
                elseif ($successful + $bouncedOrFailed === $initialTargetCount) $newStatus = 'completed_partially';
                else $newStatus = 'processing_issue';
            } elseif ($initialTargetCount === 0 && $skipped > 0) $newStatus = 'all_skipped';
            elseif ($initialTargetCount === 0 && $skipped === 0) $newStatus = 'completed_with_no_valid_targets';
        } else {
            $newStatus = 'processing';
        }
        if ($sentEmail->status !== $newStatus) {
            $sentEmail->status = $newStatus;
            $sentEmail->save();
            Log::info("Parent SentEmail ID {$sentEmailId} status updated to '{$newStatus}'. Stats: S:{$successful}, F/B:{$bouncedOrFailed}, Skip:{$skipped}, Queue:{$stillQueued}, Total:{$totalLogs}, InitialTargets: {$initialTargetCount}");
        }
    }
}
