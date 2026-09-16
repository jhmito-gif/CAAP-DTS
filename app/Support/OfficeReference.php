<?php

namespace App\Support;

use App\Models\Record;
use App\Models\ReferenceSequence;
use App\Models\Transaction;

/**
 * An office's reference ID for a document. One number per office per document:
 * allocated when the document is sent to that office, reused whenever it comes
 * back, and editable afterwards from the Reference ID button.
 */
class OfficeReference
{
    /**
     * The number this office already holds for the document -- its own record
     * reference, or what it recorded on an earlier movement -- or null.
     */
    public static function existing(Record $record, string $office): ?string
    {
        $earlier = Transaction::where('record_id', $record->id)
            ->where('destination', $office)
            ->whereNotNull('received_reference')
            ->orderByDesc('id')
            ->value('received_reference');

        if (filled($earlier)) {
            return (string) $earlier;
        }

        // The office that logged the record already numbered it ("ITD-2026-0001").
        if (filled($record->reference) && str_starts_with((string) $record->reference, "{$office}-")) {
            return (string) $record->reference;
        }

        return null;
    }

    /**
     * That number, or the next one from the office's own sequence.
     */
    public static function resolve(Record $record, string $office): string
    {
        return static::existing($record, $office) ?? ReferenceSequence::nextReferenceFor($office);
    }

    /**
     * The number to stamp on a movement being sent to $office, with the office's
     * sequence moved past it so the next document gets a fresh number.
     */
    public static function allocate(Record $record, string $office): string
    {
        $reference = static::resolve($record, $office);

        ReferenceSequence::advance($office, $reference);

        return $reference;
    }
}
