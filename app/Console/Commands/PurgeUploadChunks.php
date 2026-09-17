<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Clears away pieces of uploads that were never finished -- a browser closed
 * mid-upload, a connection that never came back.
 */
class PurgeUploadChunks extends Command
{
    protected $signature = 'documents:purge-chunks {--hours=24 : how old an unfinished upload must be}';

    protected $description = 'Delete the leftovers of uploads that never finished';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHours((int) $this->option('hours'))->getTimestamp();
        $removed = 0;

        foreach ($disk->directories('chunk-uploads') as $perUser) {
            foreach ($disk->directories($perUser) as $upload) {
                $files = $disk->files($upload);
                $newest = collect($files)->map(fn ($file) => $disk->lastModified($file))->max() ?? 0;

                if ($newest < $cutoff) {
                    $disk->deleteDirectory($upload);
                    $removed++;
                }
            }
        }

        $this->info($removed === 0 ? 'No unfinished uploads to clear.' : "{$removed} unfinished upload(s) cleared.");

        return self::SUCCESS;
    }
}
