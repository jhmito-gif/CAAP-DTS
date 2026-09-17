<?php

namespace App\Console\Commands;

use App\Models\DocumentText;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;

/**
 * Checks that this machine can read documents: Node, the packages that do the
 * reading, somewhere to keep the OCR language data, and a scheduler to work
 * through the queue.
 *
 * Meant for the server after a deploy -- one command that says what is missing
 * rather than a silent queue that never moves.
 */
class CheckDocumentReading extends Command
{
    protected $signature = 'documents:check';

    protected $description = 'Check that documents can be read for searching on this machine';

    public function handle(): int
    {
        $problems = 0;

        $this->line('');
        $this->line('  <options=bold>Reading documents: what this machine has</>');
        $this->line('');

        // Node itself.
        $binary = (string) config('services.node.binary', 'node');
        $node = Process::env($this->environment())->run([$binary, '-v']);

        $problems += $this->report(
            'Node',
            $node->successful(),
            $node->successful() ? trim($node->output()) . " ({$binary})" : "cannot run {$binary}",
            'Set NODE_BINARY in .env to a node the web user can run.',
        );

        // The packages that do the reading.
        foreach (['pdfjs-dist', 'tesseract.js', '@hyzyla/pdfium', '@napi-rs/canvas'] as $package) {
            $installed = is_dir(base_path("node_modules/{$package}"));
            $problems += $this->report(
                $package,
                $installed,
                $installed ? 'installed' : 'missing',
                'Run "npm ci" where the application lives.',
            );
        }

        // Somewhere to keep the OCR language data.
        $cache = storage_path('app/ocr');

        if (! is_dir($cache)) {
            @mkdir($cache, 0775, true);
        }

        $writable = is_dir($cache) && is_writable($cache);
        $problems += $this->report(
            'OCR language data',
            $writable,
            $writable ? (count(glob($cache . '/*')) > 0 ? 'cached' : 'empty, will be fetched on the first scan') : 'cannot write to ' . $cache,
            'Give the web user ownership: chown -R www-data:www-data storage/app/ocr',
        );

        // The table, and whether the queue is moving.
        $hasTable = Schema::hasTable('document_texts');
        $problems += $this->report('Database', $hasTable, $hasTable ? 'ready' : 'document_texts is missing', 'Run "php artisan migrate --force".');

        if ($hasTable) {
            $pending = DocumentText::where('status', 'pending')->count();
            $done = DocumentText::where('status', 'done')->count();
            $failed = DocumentText::where('status', 'failed')->count();

            $this->line(sprintf('    %-22s %d read, %d waiting, %d could not be read', 'Queue', $done, $pending, $failed));
        }

        // A real read, end to end.
        $this->line('');
        $this->line('  <options=bold>Trying to read something</>');
        $this->line('');

        $sample = base64_encode($this->onePagePdf());

        $trial = Process::timeout(120)
            ->env($this->environment())
            ->input(json_encode(['file' => $sample, 'mime' => 'application/pdf', 'maxPages' => 1, 'cachePath' => $cache]))
            ->run([$binary, resource_path('node/read-document.mjs')]);

        $lines = preg_split('/\r?\n/', trim($trial->output())) ?: [];
        $read = json_decode((string) end($lines), true);
        $worked = $trial->successful() && is_array($read) && ($read['characters'] ?? 0) > 0;

        $problems += $this->report(
            'Text layer',
            $worked,
            $worked ? "read {$read['characters']} characters" : (trim($trial->errorOutput()) ?: 'no text came back'),
            'Check that node_modules is installed and the web user can run node.',
        );

        $this->line('');

        if ($problems > 0) {
            $this->error("  {$problems} thing(s) need attention before documents can be read.");

            return self::FAILURE;
        }

        $this->info('  Everything needed is in place. Scans are read by OCR the first time one arrives.');
        $this->line('  Queue the backlog with: php artisan documents:read --queue-all');
        $this->line('');

        return self::SUCCESS;
    }

    private function report(string $what, bool $ok, string $detail, string $fix): int
    {
        $this->line(sprintf('    %-22s %s %s', $what, $ok ? '<fg=green>ok</>' : '<fg=red>no</>', $detail));

        if (! $ok) {
            $this->line("      <fg=yellow>{$fix}</>");
        }

        return $ok ? 0 : 1;
    }

    /** A tiny PDF with a text layer, for the trial read. */
    private function onePagePdf(): string
    {
        return \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<p>The document reader is working.</p>')->output();
    }

    /**
     * @return array<string, string>
     */
    private function environment(): array
    {
        $environment = ['PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin'];

        if (PHP_OS_FAMILY === 'Windows') {
            $environment['SystemRoot'] = getenv('SystemRoot') ?: 'C:\\Windows';
            $environment['TEMP'] = getenv('TEMP') ?: sys_get_temp_dir();
        }

        return $environment;
    }
}
