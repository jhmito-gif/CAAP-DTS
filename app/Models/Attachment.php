<?php

namespace App\Models;

use App\Models\Concerns\DescribesFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attachment extends Model
{
    use DescribesFile;

    protected $fillable = [
        'record_id',
        'transaction_id',
        'document_category_id',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'sha256',
        'uploaded_by',
        'is_confidential',
        'is_encrypted',
        'signing_completed_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_confidential' => 'boolean',
        'is_encrypted' => 'boolean',
        'signing_completed_at' => 'datetime',
    ];

    /**
     * The file's current decrypted bytes: the latest signed version once the
     * document has been e-signed, otherwise the file as uploaded.
     */
    public function contents(): string
    {
        $latest = $this->signatures()->latest('id')->first();

        return $latest ? $latest->contents() : $this->originalContents();
    }

    /**
     * The file as uploaded, decrypted (or raw bytes for legacy plaintext files).
     */
    public function originalContents(): string
    {
        $raw = \Illuminate\Support\Facades\Storage::disk($this->disk)->get($this->path);

        if (! $this->is_encrypted) {
            return $raw;
        }

        return \Illuminate\Support\Facades\Crypt::decryptString($raw);
    }

    /**
     * A file is confidential when flagged itself or its record is confidential.
     */
    public function isConfidential(): bool
    {
        return $this->is_confidential || (bool) ($this->record?->is_confidential);
    }

    /**
     * A file name gives away what the file holds ("Complaint vs Capt Cruz.pdf"),
     * so a confidential one is named only for viewers cleared for the record,
     * and only once they have entered its access token.
     */
    public function nameIsHiddenFrom(?User $user, bool $unlocked = true): bool
    {
        if (! $this->isConfidential()) {
            return false;
        }

        $record = $this->record;

        if (! $record || ! $record->canViewConfidentialDetails($user)) {
            return true;
        }

        return $record->requiresToken() && ! $unlocked;
    }

    /**
     * The file's name as this viewer may see it.
     */
    public function displayNameFor(?User $user, bool $unlocked = true): string
    {
        return $this->nameIsHiddenFrom($user, $unlocked)
            ? 'Confidential file'
            : (string) $this->original_name;
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class, 'record_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /** Document library category, if one was set. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function signatureRequests(): HasMany
    {
        return $this->hasMany(SignatureRequest::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class)->orderBy('id');
    }

    /**
     * Every assigned signatory has signed; the document can no longer change.
     */
    public function isSigningComplete(): bool
    {
        return $this->signing_completed_at !== null;
    }

    /**
     * Store an uploaded file ENCRYPTED at rest on the private disk and record it.
     */
    public static function storeEncrypted(Record $record, ?Transaction $transaction, \Illuminate\Http\UploadedFile $file, string $uploadedBy): self
    {
        $ext = $file->getClientOriginalExtension();
        $path = "attachments/{$record->id}/" . Str::random(40) . ($ext ? ".{$ext}" : '');
        $bytes = $file->get();

        Storage::disk('local')->put(
            $path,
            \Illuminate\Support\Facades\Crypt::encryptString($bytes)
        );

        return static::create([
            'record_id' => $record->id,
            'transaction_id' => $transaction?->id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'local',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sha256' => hash('sha256', $bytes),
            'uploaded_by' => $uploadedBy,
            'is_confidential' => (bool) $record->is_confidential,
            'is_encrypted' => true,
        ]);
    }

    /**
     * Delete the underlying file (and any signed versions) when the row is removed.
     */
    protected static function booted(): void
    {
        // Queue it to be read, so it can be found by what it says. The reading
        // itself happens on a schedule -- a scan takes seconds a page. Only
        // the kinds that can be read: a spreadsheet has nothing to offer here.
        static::created(function (Attachment $attachment) {
            if (app(\App\Support\DocumentReader::class)->readable((string) $attachment->mime_type)) {
                DocumentText::queue('attachment', $attachment->id, $attachment->sha256);
            }
        });

        static::deleting(function (Attachment $attachment) {
            // Via the model so each signed version's file is removed too.
            $attachment->signatures()->get()->each->delete();

            Storage::disk($attachment->disk)->delete($attachment->path);

            DocumentText::where('source_type', 'attachment')->where('source_id', $attachment->id)->delete();
        });
    }
}
