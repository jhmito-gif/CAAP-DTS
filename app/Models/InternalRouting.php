<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * One person-to-person handoff inside an office. The office-to-office chain
 * says which office holds a document; this says who inside it.
 */
class InternalRouting extends Model
{
    protected $fillable = [
        'record_id',
        'office',
        'from_user_id',
        'from_name',
        'to_user_id',
        'to_name',
        'action',
        'remarks',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /** Forwarded but not yet acknowledged by the recipient. */
    public function isPending(): bool
    {
        return $this->received_at === null;
    }

    /**
     * The latest handoff in an office -- whoever holds the document there now.
     */
    public static function holderFor(int $recordId, string $office): ?self
    {
        return static::where('record_id', $recordId)
            ->where('office', $office)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Current holder names keyed by record, for a page of the incoming and
     * outgoing lists (one query, not one per row).
     *
     * @param  iterable<int>  $recordIds
     * @return Collection<int, string>
     */
    public static function holderNames(iterable $recordIds, string $office): Collection
    {
        $ids = collect($recordIds)->filter()->unique()->values();

        if ($ids->isEmpty() || blank($office)) {
            return collect();
        }

        return static::whereIn('record_id', $ids)
            ->where('office', $office)
            ->orderByDesc('id')
            ->get(['record_id', 'to_name'])
            ->unique('record_id')
            ->pluck('to_name', 'record_id');
    }
}
