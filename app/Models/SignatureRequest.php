<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A signatory assigned to sign an uploaded PDF.
 */
class SignatureRequest extends Model
{
    protected $fillable = [
        'attachment_id',
        'record_id',
        'signer_id',
        'requested_by',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function signature(): HasOne
    {
        return $this->hasOne(Signature::class);
    }

    public function isPending(): bool
    {
        return $this->signed_at === null;
    }
}
