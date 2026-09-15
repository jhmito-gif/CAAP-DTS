<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
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

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class, 'record_id');
    }
}
