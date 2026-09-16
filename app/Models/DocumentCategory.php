<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A document type (Memorandum, Letter, Report…) used to organise the
 * document library. Managed by admins.
 */
class DocumentCategory extends Model
{
    protected $fillable = [
        'name',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    protected static function booted(): void
    {
        // Attachments reference categories without a database constraint.
        static::deleting(function (DocumentCategory $category) {
            Attachment::where('document_category_id', $category->id)->update(['document_category_id' => null]);
            Document::where('document_category_id', $category->id)->update(['document_category_id' => null]);
        });
    }
}
