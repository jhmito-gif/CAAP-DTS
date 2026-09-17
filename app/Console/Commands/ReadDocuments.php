<?php

namespace App\Console\Commands;

use App\Models\DocumentText;
use App\Support\DocumentReader;
use Illuminate\Console\Command;

/**
 * Reads queued documents so their contents can be searched.
 *
 * Run on a schedule (see routes/console.php). Scans go through OCR and take a
 * second or two a page, so each run takes a batch and stops -- the next run
 * picks up where it left off.
 */
class ReadDocuments extends Command
{
    protected $signature = 'documents:read
                            {--limit=20 : how many files to read this run}
                            {--queue-all : queue every file that has never been read}
                            {--retry : include files that failed before}
                            {--id= : read one file, as "attachment:14" or "document:3"}';

    protected $description = 'Read documents so they can be searched by their contents';

    public function handle(DocumentReader $reader): int
    {
        if ($this->option('queue-all')) {
            $this->info($reader->queueEverything() . ' file(s) queued.');
        }

        if ($one = $this->option('id')) {
            return $this->readOne($reader, (string) $one);
        }

        $rows = DocumentText::query()
            ->where(fn ($query) => $query
                ->where('status', 'pending')
                ->when($this->option('retry'), fn ($q) => $q->orWhere(fn ($r) => $r->where('status', 'failed')->where('attempts', '<', 3))))
            ->orderBy('updated_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($rows->isEmpty()) {
            $this->line('Nothing waiting to be read.');

            return self::SUCCESS;
        }

        $done = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $read = $reader->read($row);

            if ($read->status === 'done') {
                $done++;
                $this->line(sprintf(
                    '  %s %d: %s, %d page(s), %d characters',
                    $read->source_type,
                    $read->source_id,
                    $read->method,
                    $read->pages,
                    $read->characters,
                ));
            } else {
                $failed++;
                $this->warn(sprintf('  %s %d: %s (%s)', $read->source_type, $read->source_id, $read->status, $read->failure));
            }
        }

        $this->info("Read {$done} file(s)" . ($failed ? ", {$failed} could not be read." : '.'));

        return self::SUCCESS;
    }

    private function readOne(DocumentReader $reader, string $id): int
    {
        [$type, $key] = array_pad(explode(':', $id, 2), 2, null);

        if (! in_array($type, ['attachment', 'document'], true) || ! is_numeric($key)) {
            $this->error('Use --id=attachment:14 or --id=document:3.');

            return self::FAILURE;
        }

        $row = DocumentText::firstOrCreate(
            ['source_type' => $type, 'source_id' => (int) $key],
            ['status' => 'pending'],
        );

        $read = $reader->read($row->fill(['status' => 'pending']));

        $this->line("{$read->source_type} {$read->source_id}: {$read->status}" . ($read->failure ? " ({$read->failure})" : ''));

        return $read->status === 'done' ? self::SUCCESS : self::FAILURE;
    }
}
