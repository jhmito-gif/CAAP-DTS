<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Record extends Model
{
    protected $fillable = [
        'reference',
        'subject',
        'created_by',
        'origin',
        'owner',
        'is_urgent',
    ];


    protected $casts = [
        'is_urgent' => 'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | Transactions Relationship
    |--------------------------------------------------------------------------
    */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'record_id');
    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */
    public function scopeSearch($query, $value)
    {
        if (blank($value)) {
            return $query;
        }

        return $query->where(function ($q) use ($value) {

            $q->where('reference', 'like', "%{$value}%")
                ->orWhere('subject', 'like', "%{$value}%");

        });
    }


    /*
    |--------------------------------------------------------------------------
    | Urgent
    |--------------------------------------------------------------------------
    */
    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }


    /*
    |--------------------------------------------------------------------------
    | Records Associated With An Office
    |--------------------------------------------------------------------------
    */
    public function scopeForOffice($query, $office)
    {
        return $query->where(function ($q) use ($office) {

            $q->where('owner', $office)
                ->orWhere('origin', $office)
                ->orWhereHas('transactions', function ($transactionQuery) use ($office) {

                    $transactionQuery->where(function ($transaction) use ($office) {

                        $transaction->where('office', $office)
                            ->orWhere('destination', $office);

                    });

                });

        });
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Related Transactions
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        static::deleting(function ($record) {
            $record->transactions()->delete();
        });
    }
}