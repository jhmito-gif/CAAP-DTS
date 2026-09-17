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
        'placements',
        'placed_by',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'placements' => 'array',
    ];

    /**
     * Every spot the sending office marked -- a signatory may have to sign
     * several pages of the same document.
     *
     * @return array<int, array{page: int, x: float, y: float, width: float, height: float}>
     */
    public function placements(): array
    {
        return collect($this->placements ?? [])
            ->filter(fn ($box) => isset($box['page'], $box['x'], $box['y'], $box['width'], $box['height']))
            ->map(fn ($box) => [
                'page' => (int) $box['page'],
                'x' => (float) $box['x'],
                'y' => (float) $box['y'],
                'width' => (float) $box['width'],
                'height' => (float) $box['height'],
            ])
            ->values()
            ->all();
    }

    /** The first marked spot, for screens that show only one. */
    public function placement(): ?array
    {
        return $this->placements()[0] ?? null;
    }

    /** The pages this signatory was asked to sign. */
    public function markedPages(): array
    {
        return collect($this->placements())->pluck('page')->unique()->sort()->values()->all();
    }

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
