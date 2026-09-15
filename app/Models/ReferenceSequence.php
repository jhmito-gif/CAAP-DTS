<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceSequence extends Model
{
    protected $fillable = [
        'office',
        'year',
        'next_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'next_number' => 'integer',
    ];

    public function getNextReferenceAttribute(): string
    {
        return "{$this->office}-{$this->year}-" . str_pad($this->next_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Next internal reference for an office ("{office}-{year}-0001"), shared by
     * incoming and outgoing records.
     *
     * Only references carrying the office's own prefix count, so a record that
     * merely lists the office as origin/owner (another office's incoming copy,
     * or a legacy hand-typed reference) never feeds its number into this
     * office's sequence.
     */
    public static function nextReferenceFor(string $office): string
    {
        $year = now()->year;
        $prefix = "{$office}-{$year}-";

        $latest = Record::where('reference', 'like', "{$prefix}%")
            ->whereYear('created_at', $year)
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($latest && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $latest->reference, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        $sequence = static::where('office', $office)
            ->where('year', $year)
            ->value('next_number');

        if ($sequence) {
            $nextNumber = max($nextNumber, $sequence);
        }

        do {
            $reference = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Record::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Move the office's sequence past a reference that was just used.
     */
    public static function advance(string $office, string $reference): void
    {
        $year = now()->year;
        $prefix = "{$office}-{$year}-";

        if (! preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $reference, $matches)) {
            return;
        }

        static::updateOrCreate(
            ['office' => $office, 'year' => $year],
            ['next_number' => intval($matches[1]) + 1]
        );
    }
}
