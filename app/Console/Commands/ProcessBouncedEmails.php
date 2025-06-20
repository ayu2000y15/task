<?php

namespace App\Console\Commands;

use App\Models\BlacklistEntry;
use Illuminate\Console\Command;
use App\Models\SentEmailLog;
use App\Models\Subscriber;
use App\Models\SentEmail;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\Header;
use Illuminate\Support\Str;

class ProcessBouncedEmails extends Command
{
    protected $signature = 'emails:process-bounces';
    protected $description = 'バウンスメールボックスを確認し、送信メールログのステータスを更新し、ハードバウンスをブラックリストに登録します。';

    public function handle(): int
    {
        $this->info('バウンスメールの処理を開始します...');
        Log::channel('schedule')->info('ProcessBouncedEmails コマンドを開始しました。');

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
            $this->error('バウンスメールボックスの接続情報が正しく設定されていません。');
            Log::channel('schedule')->error('ProcessBouncedEmails: バウンスメールボックスの接続情報が設定されていません。');
            return Command::FAILURE;
        }

        try {
            $clientManager = new ClientManager();
            $client = $clientManager->make($config);
            $client->connect();
            $this->info("バウンスメールボックスへの接続に成功しました: " . $config['host']);

            $inbox = $client->getFolder('INBOX');
            $messages = $inbox->messages()->unseen()->leaveUnread(false)->get();

            if ($messages->isEmpty()) {
                $this->info('処理対象の新しいバウンスメールはありません。');
                Log::channel('schedule')->info('ProcessBouncedEmails: 新しいメッセージは見つかりませんでした。');
                return Command::SUCCESS;
            }

            $this->info("処理対象の新しいメッセージを {$messages->count()} 件見つけました。");
            $processedCount = 0;
            $updatedLogCount = 0;
            $blacklistedCount = 0;

            foreach ($messages as $message) {
                /** @var \Webklex\PHPIMAP\Message $message */
                $uid = $message->getUid();
                $messageSubjectHeader = $message->getSubject();
                $subjectString = $messageSubjectHeader instanceof Header ? $messageSubjectHeader->toString() : (is_string($messageSubjectHeader) ? $messageSubjectHeader : 'N/A');
                $this->line("メッセージを処理中 UID: {$uid} 件名: {$subjectString}");
                Log::channel('schedule')->info("ProcessBouncedEmails: UID {$uid} を処理中, 件名: {$subjectString}");

                $messageIdentifier = null;
                $sentEmailLog = null;
                $processedThisMessageByFallback = false;
                $parsedBounceReasonFromBody = null;

                // 試行1-4: X-Mailer-Message-Identifier の探索
                $customIdentifierHeader = $message->getHeader('x-mailer-message-identifier');
                if ($customIdentifierHeader instanceof Header && $customIdentifierHeader->count() > 0) {
                    $messageIdentifier = trim($customIdentifierHeader->first());
                    Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): X-Mailer-Message-Identifierを直接のヘッダーで発見: {$messageIdentifier}");
                }
                if (empty($messageIdentifier) && $message->hasAttachments()) {
                    $attachments = $message->getAttachments();
                    if (is_iterable($attachments)) {
                        foreach ($attachments as $attachment) {
                            if (is_object($attachment) && method_exists($attachment, 'getContentType') && strtolower($attachment->getContentType()) == 'message/rfc822') {
                                $content = $attachment->getContent();
                                if (preg_match('/^X-Mailer-Message-Identifier:\s*(.*)$/im', (string)$content, $matches)) {
                                    $messageIdentifier = trim($matches[1]);
                                    Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): X-Mailer-Message-Identifierを添付ファイル内で発見: {$messageIdentifier}");
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
                                    Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): X-Mailer-Message-Identifierをパート内で発見: {$messageIdentifier}");
                                    break;
                                }
                            }
                        }
                    }
                }
                if (empty($messageIdentifier)) {
                    $bodyForSearch = ($message->hasTextBody() ? $message->getTextBody() : "") . "\n" . ($message->hasHTMLBody() ? $message->getHTMLBody() : "");
                    if (!empty($bodyForSearch)) {
                        Log::channel('schedule')->debug("ProcessBouncedEmails (UID {$uid}): 本文内でX-Mailer-Message-Identifierを探索中...", ['body_snippet' => Str::limit($bodyForSearch, 200)]);
                        if (preg_match('/(?:^|\n|\r)\s*X-Mailer-Message-Identifier:\s*([^\s\n\r]+)/im', $bodyForSearch, $matches)) {
                            $messageIdentifier = trim($matches[1]);
                            Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): X-Mailer-Message-Identifierを本文内で発見: {$messageIdentifier}");
                        }
                    }
                }

                // --- メイン処理ロジック ---
                if (!empty($messageIdentifier)) {
                    $sentEmailLog = SentEmailLog::channel('schedule')->where('message_identifier', $messageIdentifier)
                        ->whereIn('status', ['queued', 'sent'])
                        ->first();
                    if (!$sentEmailLog) {
                        Log::channel('schedule')->warning("ProcessBouncedEmails (UID {$uid}): 識別子 '{$messageIdentifier}' は見つかりましたが、一致するSentEmailLogがありません。この識別子でのバウンス処理は行われません。");
                    }
                } else {
                    // --- 試行5: フォールバック - バウンス本文内の受信者メールアドレスで特定 ---
                    Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): X-Mailer-Message-Identifierが見つかりません。受信者メールアドレスによるフォールバックを試みます。");
                    $bouncedRecipientEmail = null;
                    $parsedBounceReasonFromBody = "バウンス (UID: {$uid})";

                    $fallbackBodySearch = "";
                    $textBody = $message->getTextBody();
                    $htmlBody = $message->getHTMLBody();
                    if (is_string($textBody) && !empty($textBody)) $fallbackBodySearch .= $textBody;
                    if (is_string($htmlBody) && !empty($htmlBody)) $fallbackBodySearch .= "\n" . $htmlBody;

                    if (!empty($fallbackBodySearch)) {
                        Log::channel('schedule')->debug("ProcessBouncedEmails (UID {$uid}): 本文内でフォールバック検索中...", ['body_snippet' => Str::limit($fallbackBodySearch, 500)]);
                        if (preg_match('/<([^>@\s]+@[^>@\s]+)>[:\s]+(.+)/im', $fallbackBodySearch, $matchesEmailReason)) {
                            $bouncedRecipientEmail = trim($matchesEmailReason[1]);
                            $parsedBounceReasonFromBody = trim($matchesEmailReason[2]);
                            Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): フォールバックにより、本文から受信者 '{$bouncedRecipientEmail}' と理由 '{$parsedBounceReasonFromBody}' を解析しました (パターン <email>:reason)。");
                        } elseif (preg_match_all('/(?:Failed Recipient|Final-Recipient)\s*:\s*(?:rfc822\s*;\s*)?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $fallbackBodySearch, $recipientMatches)) {
                            if (!empty($recipientMatches[1])) {
                                $bouncedRecipientEmail = trim($recipientMatches[1][0]);
                                if (preg_match('/Diagnostic-Code\s*:\s*[a-zA-Z]+\s*;\s*(.+)/im', $fallbackBodySearch, $diagMatches)) {
                                    $parsedBounceReasonFromBody = trim($diagMatches[1]);
                                } elseif (preg_match('/Status\s*:\s*(\d\.\d\.\d+)/im', $fallbackBodySearch, $statusMatches)) {
                                    $parsedBounceReasonFromBody = "Status: " . trim($statusMatches[1]);
                                }
                                Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): フォールバックにより、本文から受信者 '{$bouncedRecipientEmail}' を解析しました (パターン Final-Recipient)。理由のヒント: {$parsedBounceReasonFromBody}");
                            }
                        }
                    }

                    if ($bouncedRecipientEmail) {
                        $sentEmailLog = SentEmailLog::channel('schedule')->where('recipient_email', $bouncedRecipientEmail)
                            ->whereIn('status', ['sent', 'queued'])
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if ($sentEmailLog) {
                            Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): フォールバックにより、受信者 {$bouncedRecipientEmail} のSentEmailLog ID {$sentEmailLog->id} を発見しました。理由のベース: '{$parsedBounceReasonFromBody}'");
                            $processedThisMessageByFallback = true;
                        } else {
                            Log::channel('schedule')->warning("ProcessBouncedEmails (UID {$uid}): フォールバックで受信者 {$bouncedRecipientEmail} を解析しましたが、一致する 'sent' または 'queued' 状態のSentEmailLogが見つかりませんでした。");
                        }
                    } else {
                        Log::channel('schedule')->warning("ProcessBouncedEmails (UID {$uid}): フォールバックで本文から受信者のメールアドレスを解析できませんでした。");
                    }
                }

                // --- DSNの処理とログの更新 (いずれかの方法で $sentEmailLog が設定された場合) ---
                if ($sentEmailLog) {
                    $baseReasonForDsnParsing = $processedThisMessageByFallback ? ($parsedBounceReasonFromBody ?? "フォールバックによるバウンス (UID: {$uid})") : "バウンス (ID一致; UID: {$uid})";
                    list($finalBounceReason, $isHardBounce) = $this->parseDsnDetailsFromMessage($message, $uid, $subjectString, $baseReasonForDsnParsing);

                    // 1. SentEmailLogのステータスを 'bounced' に更新
                    $sentEmailLog->status = 'bounced';
                    $sentEmailLog->error_message = Str::limit($finalBounceReason, 1000);
                    $sentEmailLog->processed_at = now();
                    $sentEmailLog->save();
                    $updatedLogCount++;
                    Log::channel('schedule')->info("SentEmailLog ID {$sentEmailLog->id} を 'bounced' に更新しました。理由: {$finalBounceReason}。ハードバウンス: " . ($isHardBounce ? "はい" : "いいえ"));

                    // 2. バウンスが確認された時点で、関連する購読者のステータスを「解除済」に更新
                    if ($sentEmailLog->subscriber) {
                        $subscriber = $sentEmailLog->subscriber;
                        if ($subscriber->status !== 'unsubscribed') {
                            $subscriber->status = 'unsubscribed';
                            $subscriber->unsubscribed_at = now();
                            $subscriber->save();
                            Log::channel('schedule')->info("バウンスのため、購読者ID {$subscriber->id} (Email: {$subscriber->email}) のステータスを「解除済」に更新しました。");
                        }
                    }

                    // 3. ハードバウンスの場合、ブラックリスト登録と連絡先担当者の更新を実行
                    if ($isHardBounce) {
                        $emailToBlacklist = $sentEmailLog->recipient_email;

                        $blacklistEntry = BlacklistEntry::firstOrCreate(
                            ['email' => $emailToBlacklist],
                            ['reason' => Str::limit("ハードバウンス: " . $finalBounceReason, 255), 'added_by_user_id' => null]
                        );
                        if ($blacklistEntry->wasRecentlyCreated) {
                            Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): ハードバウンスのため、メールアドレス {$emailToBlacklist} をブラックリストに追加しました。");
                            $blacklistedCount++;
                        } else {
                            Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): メールアドレス {$emailToBlacklist} は既にブラックリストに登録されていました（ハードバウンスを再検出）。");
                        }

                        if ($sentEmailLog->subscriber && $sentEmailLog->subscriber->managedContact) {
                            $contact = $sentEmailLog->subscriber->managedContact;
                            if ($contact->status === 'active') {
                                $contact->status = 'do_not_contact';
                                $note = "自動更新: メールがハードバウンスしたためステータスを「連絡不要」に変更 (" . now()->format('Y-m-d') . ")";
                                $contact->notes = trim(($contact->notes ? $contact->notes . "\n" : "") . $note);
                                $contact->save();
                                Log::channel('schedule')->info("ハードバウンスのため、連絡先担当者ID {$contact->id} (Email: {$contact->email}) のステータスを「連絡不要」に更新しました。");
                            }
                        } else {
                            Log::channel('schedule')->warning("ProcessBouncedEmails (UID {$uid}): ステータスを更新する対象の連絡先担当者が見つかりませんでした (SentEmailLog ID {$sentEmailLog->id})。");
                        }
                    }

                    $this->updateParentSentEmailStatusIfNeeded($sentEmailLog->sent_email_id);
                    $message->setFlag('Seen');
                    $processedCount++;
                } else {
                    Log::channel('schedule')->warning("ProcessBouncedEmails (UID {$uid}): 更新対象のSentEmailLogがありません。このメッセージは既読にしてスキップします。", ['subject' => $subjectString, 'identifier_found' => !empty($messageIdentifier)]);
                    $message->setFlag('Seen');
                }
            } // foreach終了

            $this->info("処理を終了しました。新規合計: {$messages->count()}, 処理済: {$processedCount}, ログ更新数: {$updatedLogCount}, ブラックリスト追加数: {$blacklistedCount}.");
            Log::channel('schedule')->info("ProcessBouncedEmails コマンドを終了しました。新規合計: {$messages->count()}, 処理済: {$processedCount}, ログ更新数: {$updatedLogCount}, ブラックリスト追加数: {$blacklistedCount}.");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("バウンスメールの処理中にエラーが発生しました: " . $e->getMessage());
            Log::channel('schedule')->error("ProcessBouncedEmails 重大エラー: " . $e->getMessage() . "\n" . $e->getTraceAsString());
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
                    Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): DSNパートを解析しました。理由: {$bounceReason}, 初期ハードバウンス判定: " . ($isHardBounce ? "はい" : "いいえ"));
                    break;
                }
            }
        } else {
            Log::channel('schedule')->warning("ProcessBouncedEmails (UID {$uid}): DSN解析でgetParts()がイテレート不可能でした。理由: '{$defaultReason}' に基づいて処理を継続します。", ['subject' => $subjectString]);
        }

        if (!$diagnosticCode) {
            if (preg_match('/(host unknown|domain name not found|user unknown|no such user|mailbox unavailable|address rejected|does not exist|Host not found)/i', $bounceReason)) {
                $isHardBounce = true;
            } elseif (preg_match('/(mailbox full|quota exceeded)/i', $bounceReason)) {
                $isHardBounce = false;
            }
        }
        if (Str::contains($bounceReason, ['Host not found', 'Host or domain name not found', 'Name service error for name='], true) && !$isHardBounce) {
            Log::channel('schedule')->info("ProcessBouncedEmails (UID {$uid}): 理由 '{$bounceReason}' がハードバウンスを示唆するため、isHardBounceをtrueに設定します。");
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
            Log::channel('schedule')->info("親のSentEmail ID {$sentEmailId} のステータスを '{$newStatus}' に更新しました。統計: 成功:{$successful}, 失敗/バウンス:{$bouncedOrFailed}, スキップ:{$skipped}, キュー内:{$stillQueued}, 合計:{$totalLogs}, 初期対象:{$initialTargetCount}");
        }
    }
}
