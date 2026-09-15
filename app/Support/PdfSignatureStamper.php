<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;

/**
 * Stamps a visual signature onto a PDF with pdf-lib under Node
 * (resources/node/stamp-signature.mjs). The document and the signature
 * travel over stdin/stdout, so the decrypted PDF is never written to disk.
 */
class PdfSignatureStamper
{
    /**
     * @param  array{page: int, x: float, y: float, width: float, height: float}  $placement  PDF points, origin bottom-left
     * @param  array<int, string>  $lines  Text printed under the signature; the first line is bold
     *
     * @throws PdfSigningException when the PDF or the placement cannot be signed
     */
    public function stamp(string $pdf, string $signaturePng, array $placement, array $lines): string
    {
        $result = Process::timeout(60)
            ->env($this->nodeEnvironment())
            ->input(json_encode([
                'pdf' => base64_encode($pdf),
                'signature' => base64_encode($signaturePng),
                'page' => (int) $placement['page'],
                'x' => (float) $placement['x'],
                'y' => (float) $placement['y'],
                'width' => (float) $placement['width'],
                'height' => (float) $placement['height'],
                'lines' => array_values($lines),
            ], JSON_THROW_ON_ERROR))
            ->run([config('services.node.binary', 'node'), resource_path('node/stamp-signature.mjs')]);

        if ($result->exitCode() === 2) {
            throw new PdfSigningException(trim($result->errorOutput()) ?: 'This document cannot be signed.');
        }

        if ($result->failed()) {
            report(new \RuntimeException('PDF signature stamping failed: ' . trim($result->errorOutput())));

            throw new PdfSigningException('The document could not be signed. Please try again.');
        }

        $signed = base64_decode(trim($result->output()), true);

        if ($signed === false || ! str_starts_with($signed, '%PDF')) {
            throw new PdfSigningException('The document could not be signed. Please try again.');
        }

        return $signed;
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
