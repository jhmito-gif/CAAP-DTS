<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One entry in the append-only e-sign audit trail. Written by
 * App\Support\EsignLogger; the application never updates or deletes rows.
 */
class EsignLog extends Model
{
    public const UPDATED_AT = null;

    public const SUCCESS = 'success';

    public const FAILURE = 'failure';

    public const INFO = 'info';

    /** Event keys and their labels, grouped as in the admin viewer. */
    public const EVENTS = [
        // Signing attempts
        'signature.signed' => 'Document signed',
        'signature.blocked' => 'Signing blocked',
        'signature.password_failed' => 'Wrong password',
        'signature.code_failed' => 'Wrong authenticator code',
        'signature.locked_out' => 'Signing locked out',
        'signature.integrity_failed' => 'Integrity check failed',
        'signature.stamp_failed' => 'Signature could not be applied',
        'signature.already_signed' => 'Already signed',

        // Requests
        'request.created' => 'Signature requested',
        'request.cancelled' => 'Signature request removed',
        'request.denied' => 'Request not allowed',

        // Document access
        'sign_page.opened' => 'Signing page opened',
        'sign_page.denied' => 'Signing page refused',
        'document.viewed' => 'Document viewed',
        'document.downloaded' => 'Document downloaded',
        'record.unlocked' => 'Confidential record unlocked',
        'record.unlock_failed' => 'Wrong access token',
        'verify.code_lookup' => 'Verification code checked',
        'verify.file_check' => 'File checked',
        'verify.throttled' => 'Verification throttled',

        // Signature profile
        'profile.signature_saved' => 'Signature saved',
        'profile.signature_rejected' => 'Signature image rejected',
        'profile.signature_removed' => 'Signature removed',
    ];

    protected $fillable = [
        'event',
        'outcome',
        'user_id',
        'user_name',
        'user_office',
        'record_id',
        'record_reference',
        'attachment_id',
        'signature_request_id',
        'signature_id',
        'ip_address',
        'user_agent',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }

    public function label(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('E-sign logs are append-only.'));
        static::deleting(fn () => throw new LogicException('E-sign logs are append-only.'));
    }
}
