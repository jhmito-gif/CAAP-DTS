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
     * Only the office a movement was sent to can say it arrived -- admins
     * included. Letting the sender (or an admin from any office) stamp it
     * received would put a name on the trail from the wrong office, hide that
     * the document never got there, and let the receiving office send it on
     * before it had it in hand. An admin who belongs to the receiving office
     * receives it like any of its members.
     */
    public function isReceivableBy(?User $user): bool
    {
        if (! $user || $this->date_recieved !== null) {
            return false;
        }

        return filled($user->office) && $this->destination === $user->office;
    }

    /**
     * When it was received, as the slip and the record pages show it.
     * Receipts made before the time of day was kept (the column used to hold
     * only the date) read as the day alone, not a made-up 12:00 AM.
     */
    public function receivedAtLabel(string $format = 'F j, Y g:i A', string $dayFormat = 'F j, Y'): string
    {
        if ($this->date_recieved === null) {
            return '';
        }

        // A handful of legacy rows carry a zero date ('0000-00-00').
        if (str_starts_with((string) $this->date_recieved, '0000-00-00')) {
            return 'date not recorded';
        }

        $at = \Carbon\Carbon::parse($this->date_recieved);

        return $at->format('H:i:s') === '00:00:00' ? $at->format($dayFormat) : $at->format($format);
    }

    /** A movement is created by sending; it is received, or given a number, later. */
    protected function auditAction(string $event): string
    {
        $changed = array_values(array_diff(array_keys($this->getChanges()), ['updated_at']));

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
