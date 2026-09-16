<?php

namespace App\Models;

use App\Models\Concerns\DescribesFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A file uploaded straight into an office's document library, outside the
 * routing flow. Stored encrypted at rest; visible to its office and admins.
 */
class Document extends Model
{
    use DescribesFile;

    protected $fillable = [
        'office',
        'document_category_id',
        'title',
        'description',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'sha256',
        'is_encrypted',
        'uploaded_by_id',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_encrypted' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function isAccessibleBy(?User $user): bool
    {
        return $user !== null
            && ($user->isAdmin() || (filled($user->office) && $user->office === $this->office));
    }

    /** The owning office (or an admin) may edit and delete it. */
    public function canBeManagedBy(?User $user): bool
    {
        return $this->isAccessibleBy($user);
    }

    /**
     * The file's decrypted bytes.
     */
    public function contents(): string
    {
        $raw = Storage::disk($this->disk)->get($this->path);

        return $this->is_encrypted ? Crypt::decryptString($raw) : $raw;
    }

    /**
     * Store an uploaded file ENCRYPTED at rest in the user's office library.
     *
     * @param  array{title: string, document_category_id?: int|null, description?: string|null}  $details
     */
    public static function storeEncrypted(UploadedFile $file, User $user, array $details): self
    {
        $extension = preg_replace('/[^a-z0-9]/', '', strtolower($file->getClientOriginalExtension()));
        $path = 'documents/' . Str::slug((string) $user->office) . '/' . Str::random(40) . ($extension ? ".{$extension}" : '');
        $bytes = $file->get();

        Storage::disk('local')->put($path, Crypt::encryptString($bytes));

        return static::create(array_merge($details, [
            'office' => $user->office,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'local',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sha256' => hash('sha256', $bytes),
            'is_encrypted' => true,
            'uploaded_by_id' => $user->id,
            'uploaded_by' => $user->name,
        ]));
    }

    protected static function booted(): void
    {
        static::deleting(function (Document $document) {
            Storage::disk($document->disk)->delete($document->path);
        });
    }
}
