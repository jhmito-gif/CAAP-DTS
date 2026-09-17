<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;

/**
 * Works out where a signature belongs in a PDF -- the page and the box -- by
 * reading the document's text with pdf.js under Node
 * (resources/node/find-signature-spot.mjs). The PDF travels over stdin, so the
 * decrypted file is never written to disk.
 */
class SignatureSpotFinder
{
    public const WIDTH = 180.0;

    public const HEIGHT = 80.0;

    /**
     * @return array{page: int, x: float, y: float, width: float, height: float, reason: string, anchor: ?string}
     *
     * @throws PdfSigningException when the PDF cannot be read
     */
    public function find(string $pdf, ?string $signerName = null): array
    {
        $result = Process::timeout(60)
            ->env($this->nodeEnvironment())
            ->input(json_encode([
                'pdf' => base64_encode($pdf),
                'name' => $signerName,
                'width' => self::WIDTH,
                'height' => self::HEIGHT,
            ], JSON_THROW_ON_ERROR))
            ->run([config('services.node.binary', 'node'), resource_path('node/find-signature-spot.mjs')]);

        if ($result->exitCode() === 2) {
            throw new PdfSigningException(trim($result->errorOutput()) ?: 'This document cannot be signed.');
        }

        if ($result->failed()) {
            report(new \RuntimeException('Signature placement lookup failed: ' . trim($result->errorOutput())));

            throw new PdfSigningException('Where to sign this document could not be worked out. Place the signature yourself.');
        }

        // Anything the PDF reader prints comes before our JSON; take the last line.
        $lines = preg_split('/\r?\n/', trim($result->output())) ?: [];
        $spot = json_decode((string) end($lines), true);

        if (! is_array($spot) || ! isset($spot['page'], $spot['x'], $spot['y'])) {
            throw new PdfSigningException('Where to sign this document could not be worked out. Place the signature yourself.');
        }

        return [
            'page' => (int) $spot['page'],
            'x' => (float) $spot['x'],
            'y' => (float) $spot['y'],
            'width' => (float) ($spot['width'] ?? self::WIDTH),
            'height' => (float) ($spot['height'] ?? self::HEIGHT),
            'reason' => (string) ($spot['reason'] ?? 'fallback'),
            'anchor' => $spot['anchor'] ?? null,
        ];
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
