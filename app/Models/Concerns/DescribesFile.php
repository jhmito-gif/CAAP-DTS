<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Display helpers for models that store an uploaded file
 * (original_name, mime_type, size).
 */
trait DescribesFile
{
    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1024;
        $i = 0;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $value >= 10 ? 0 : 1) . ' ' . $units[$i];
    }

    public function getHumanSizeAttribute(): string
    {
        return static::formatBytes((int) $this->size);
    }

    public function getIsImageAttribute(): bool
    {
        return Str::startsWith((string) $this->mime_type, 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION) ?: 'FILE');
    }
}
