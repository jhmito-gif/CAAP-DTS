<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentText;
use Illuminate\Support\Facades\Process;

/**
 * Reads the words out of a document so it can be found by its contents.
 *
 * A PDF written by a word processor carries its text already and is read in a
 * fraction of a second. A scan carries none, so its pages are drawn and passed
 * through OCR, which takes a second or two a page -- hence the page cap.
 *
 * The file travels to Node over stdin, so the decrypted bytes are never
 * written to disk (resources/node/read-document.mjs).
 */
class DocumentReader
{
    /** Beyond this many pages, a scan is left half-read rather than holding up the queue. */
    public const MAX_PAGES = 30;

    /** Files larger than this are skipped: reading them costs more than it is worth. */
    public const MAX_BYTES = 40 * 1024 * 1024;

    /**
     * Read one queued file and store what it says.
     */
    public function read(DocumentText $row): DocumentText
    {
        $source = $row->source();

        if (! $source) {
            $row->update(['status' => 'failed', 'failure' => 'The file is no longer there.']);

            return $row;
        }

        $mime = (string) $source->mime_type;

        if (! $this->readable($mime)) {
            $row->update(['status' => 'skipped', 'failure' => 'Nothing to read in this kind of file.', 'read_at' => now()]);

            return $row;
        }

        try {
            $contents = $source->contents();
        } catch (\Throwable $error) {
            $row->update(['status' => 'failed', 'failure' => 'The file could not be opened.', 'attempts' => $row->attempts + 1]);

            return $row;
        }

        if (strlen($contents) > self::MAX_BYTES) {
            $row->update(['status' => 'skipped', 'failure' => 'The file is too large to read.', 'read_at' => now()]);

            return $row;
        }

        $result = Process::timeout(300)
            ->env($this->nodeEnvironment())
            ->input(json_encode([
                'file' => base64_encode($contents),
                'mime' => $mime,
                'maxPages' => self::MAX_PAGES,
                'cachePath' => $this->cachePath(),
            ], JSON_THROW_ON_ERROR))
            ->run([config('services.node.binary', 'node'), resource_path('node/read-document.mjs')]);

        if ($result->failed()) {
            $row->update([
                'status' => 'failed',
                'failure' => \Illuminate\Support\Str::limit(trim($result->errorOutput()) ?: 'The file could not be read.', 240, ''),
                'attempts' => $row->attempts + 1,
            ]);

            return $row;
        }

        // Anything the PDF reader prints comes before our JSON; take the last line.
        $lines = preg_split('/\r?\n/', trim($result->output())) ?: [];
        $read = json_decode((string) end($lines), true);

        if (! is_array($read) || ! array_key_exists('text', $read)) {
            $row->update([
                'status' => 'failed',
                'failure' => 'The reader returned nothing.',
                'attempts' => $row->attempts + 1,
            ]);

            return $row;
        }

        $row->update([
            'status' => 'done',
            'method' => (string) ($read['method'] ?? 'text layer'),
            'pages' => (int) ($read['pages'] ?? 0),
            'ocr_pages' => (int) ($read['ocrPages'] ?? 0),
            'characters' => (int) ($read['characters'] ?? 0),
            'text' => (string) $read['text'],
            'sha256' => $source->sha256 ?? hash('sha256', $contents),
            'failure' => null,
            'attempts' => $row->attempts + 1,
            'read_at' => now(),
        ]);

        return $row;
    }

    /** Queue every file that has never been read. */
    public function queueEverything(): int
    {
        $queued = 0;

        Attachment::query()->select(['id', 'mime_type', 'sha256'])->chunkById(200, function ($attachments) use (&$queued) {
            foreach ($attachments as $attachment) {
                if ($this->readable((string) $attachment->mime_type)) {
                    DocumentText::queue('attachment', $attachment->id, $attachment->sha256);
                    $queued++;
                }
            }
        });

        Document::query()->select(['id', 'mime_type', 'sha256'])->chunkById(200, function ($documents) use (&$queued) {
            foreach ($documents as $document) {
                if ($this->readable((string) $document->mime_type)) {
                    DocumentText::queue('document', $document->id, $document->sha256);
                    $queued++;
                }
            }
        });

        return $queued;
    }

    public function readable(string $mime): bool
    {
        return $mime === 'application/pdf' || str_starts_with($mime, 'image/');
    }

    /**
     * Where tesseract keeps its language data. It is fetched once, the first
     * time a scan is read, and reused from then on.
     */
    private function cachePath(): string
    {
        $path = storage_path('app/ocr');

        if (! is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        return $path;
    }

    /**
     * Web servers often start PHP with a stripped environment (php-fpm clears
     * it by default, and the Windows dev server drops SystemRoot). Node needs
     * PATH to be found and, on Windows, SystemRoot to start at all.
     *
     * @return array<string, string>
     */
    private function nodeEnvironment(): array
    {
        $environment = [
            'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            $environment['SystemRoot'] = getenv('SystemRoot') ?: (getenv('SYSTEMROOT') ?: 'C:\\Windows');
            $environment['TEMP'] = getenv('TEMP') ?: sys_get_temp_dir();
            $environment['TMP'] = getenv('TMP') ?: sys_get_temp_dir();
        }

        return $environment;
    }
}
