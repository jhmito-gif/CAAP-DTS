<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use Concerns\Audited;
    protected $fillable = [
        'record_id', 
        'internal_reference',
        'origin_reference',
        'remarks', 
        'status', 
        'destination', 
        'recieved_by', 
        'date_recieved', 
        'forwarded_by', 
        'office', 
        'service',
        'received_reference',
    ];

    public function scopeSearch($query, $value)
    {
        if (empty($value)) return $query;

        // Reuses the Record search scope as a one-off subquery (not a per-row EXISTS).
        return $query->whereIn('record_id', Record::query()->select('id')->search($value));
    }

    /**
     * Only the office a movement was sent to can say it arrived. Letting the
     * sender (or any office in the chain) stamp it received would put a name
     * on the trail from the wrong office and hide that the document never got
     * there. Admins may still receive on an office's behalf.
     */
    public function isReceivableBy(?User $user): bool
    {
        if (! $user || $this->date_recieved !== null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return filled($user->office) && $this->destination === $user->office;
    }

    /** A movement is created by sending; it is received, or given a number, later. */
    protected function auditAction(string $event): string
    {
        $changed = array_diff(array_keys($this->getChanges()), ['updated_at']);

        return match (true) {
            $event === 'created' => 'transaction.sent',
            $event === 'updated' && in_array('date_recieved', $changed, true) && $this->date_recieved !== null => 'transaction.received',
            $event === 'updated' && $changed === ['received_reference'] => 'transaction.reference_assigned',
            default => "transaction.{$event}",
        };
    }

    public function auditLabel(): string
    {
        return ($this->office ?: '?') . ' → ' . ($this->destination ?: '?');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class, 'record_id');
    }
}
