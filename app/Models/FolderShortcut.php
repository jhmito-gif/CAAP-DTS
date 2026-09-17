<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record attachment filed into a library folder. The file stays on its
 * record; this only points at it, so deleting the shortcut loses nothing.
 */
class FolderShortcut extends Model
{
    protected $fillable = [
        'folder_id',
        'attachment_id',
        'created_by_id',
        'created_by',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }
}
