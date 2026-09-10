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
}
