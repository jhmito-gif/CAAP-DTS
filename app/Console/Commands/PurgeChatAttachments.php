<?php

namespace App\Console\Commands;

use App\Models\ChatAttachment;
use Illuminate\Console\Command;

class PurgeChatAttachments extends Command
{
    protected $signature = 'chat:purge-attachments';

    protected $description = 'Permanently delete chat file attachments past their retention window';

    public function handle(): int
    {
        $count = 0;

        ChatAttachment::expired()->chunkById(200, function ($attachments) use (&$count) {
            foreach ($attachments as $attachment) {
                $message = $attachment->message;
                $attachment->delete(); // deleting hook removes the file from disk
                $count++;

                // Remove a now-empty, file-only message.
                if ($message && trim((string) $message->body) === '' && $message->attachments()->count() === 0) {
                    $message->delete();
                }
            }
        });

        $this->info("Purged {$count} expired chat attachment(s).");

        return self::SUCCESS;
    }
}
