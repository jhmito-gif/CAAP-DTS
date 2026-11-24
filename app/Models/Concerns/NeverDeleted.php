<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Kept for good: the document trail must stay whole, so these rows are never
 * deleted -- not from a screen, the admin panel, or code. A mistake is
 * corrected by editing, and the audit log keeps what was there before.
 *
 * Guards model deletes (every screen deletes through the model). A raw
 * query-builder delete is not caught; do not write one for these tables.
 */
trait NeverDeleted
{
    protected static function bootNeverDeleted(): void
    {
        static::deleting(function ($model): void {
            throw new LogicException(class_basename($model) . ' rows are kept permanently and cannot be deleted.');
        });
    }
}
