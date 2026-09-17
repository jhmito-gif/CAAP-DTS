<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Audit row for one applied e-signature, pointing at the signed version of
 * the document it produced.
 */
class Signature extends Model
{
    /** Verification codes avoid look-alike characters (0/O, 1/I). */
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'signature_request_id',
        'attachment_id',
        'user_id',
        'path',
        'size',
        'page',
        'x',
        'y',
        'width',
        'height',
        'placements',
        'source_sha256',
        'signed_sha256',
        'verification_code',
        'signer_name',
        'signer_office',
        'ip_address',
        'user_agent',
        'signed_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'page' => 'integer',
        'x' => 'float',
        'y' => 'float',
        'width' => 'float',
        'height' => 'float',
        // Every spot this one signing act stamped; the columns above hold the first.
        'placements' => 'array',
        'signed_at' => 'datetime',
    ];

    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The decrypted bytes of the signed version this signature produced.
     */
    public function contents(): string
    {
        return Crypt::decryptString(Storage::disk('local')->get($this->path));
    }

    /**
     * A new, unused code such as "K7QM2-X9PDR".
     */
    public static function newVerificationCode(): string
    {
        do {
            $characters = '';

            for ($i = 0; $i < 10; $i++) {
                $characters .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }

            $code = substr($characters, 0, 5) . '-' . substr($characters, 5);
        } while (static::where('verification_code', $code)->exists());

        return $code;
    }

    protected static function booted(): void
    {
        static::deleting(function (Signature $signature) {
            Storage::disk('local')->delete($signature->path);
        });
    }
}
