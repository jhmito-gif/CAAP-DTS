<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * One folder in an office's document tree.
 *
 * Each row carries its full path ("/Memoranda/2026") so breadcrumbs and
 * descendant lookups are plain string work rather than recursive queries. The
 * path is rebuilt whenever a folder is renamed or moved.
 */
class DocumentFolder extends Model
{
    protected $fillable = [
        'office',
        'parent_id',
        'name',
        'path',
        'is_restricted',
        'created_by_id',
        'created_by',
    ];

    protected $casts = [
        'is_restricted' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'folder_id');
    }

    public function shortcuts(): HasMany
    {
        return $this->hasMany(FolderShortcut::class, 'folder_id');
    }

    /** Who this folder is shared with, beyond the office that owns it. */
    public function policies(): HasMany
    {
        return $this->hasMany(FolderPolicy::class, 'folder_id');
    }

    /**
     * Every folder of one office, ordered so parents come before their children.
     *
     * @return Collection<int, self>
     */
    public static function treeFor(string $office): Collection
    {
        return static::query()->where('office', $office)->orderBy('path')->get();
    }

    /**
     * This folder and its ancestors, root first -- the breadcrumb trail.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function breadcrumb(): array
    {
        $names = array_values(array_filter(explode('/', $this->path)));
        $trail = [];
        $walk = $this;

        // Walk up by id so each crumb can be clicked, using the path only for
        // its depth (a folder is at most a handful of levels deep).
        for ($i = count($names) - 1; $i >= 0 && $walk; $i--) {
            array_unshift($trail, ['id' => $walk->id, 'name' => $walk->name]);
            $walk = $walk->parent;
        }

        return $trail;
    }

    /** Folders directly or indirectly inside this one. */
    public function descendants(): Builder
    {
        return static::query()
            ->where('office', $this->office)
            ->where('path', 'like', $this->path . '/%');
    }

    /**
     * Would moving this folder into $target put it inside itself?
     */
    public function wouldContain(?self $target): bool
    {
        if (! $target) {
            return false;
        }

        return $target->id === $this->id || str_starts_with($target->path, $this->path . '/');
    }

    /**
     * Give this folder a new parent (null for the root) and keep every path
     * underneath it correct.
     */
    public function moveTo(?self $parent): void
    {
        $this->parent_id = $parent?->id;
        $this->rebuildPath();
    }

    public function rename(string $name): void
    {
        $this->name = $name;
        $this->rebuildPath();
    }

    /**
     * True when the office already has a folder of this name beside this one.
     */
    public function nameIsTaken(string $name, ?int $parentId): bool
    {
        return static::query()
            ->where('office', $this->office)
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->whereKeyNot($this->id ?: 0)
            ->exists();
    }

    /**
     * Write this folder's path, then shift every descendant onto it. One update
     * statement per subtree, so moving a large branch stays cheap.
     */
    public function rebuildPath(): void
    {
        $was = $this->getOriginal('path');
        $parent = $this->parent_id ? static::find($this->parent_id) : null;

        $this->path = ($parent?->path ?? '') . '/' . $this->name;
        $this->save();

        if (! $was || $was === $this->path) {
            return;
        }

        $connection = static::query()->getConnection();
        $prefix = $connection->getPdo()->quote($this->path);
        $rest = 'SUBSTR(path, ' . (strlen($was) + 1) . ')'; // 1-based: keep what follows the old prefix

        // SQLite joins strings with ||, MySQL and MariaDB with CONCAT().
        $newPath = $connection->getDriverName() === 'sqlite'
            ? "{$prefix} || {$rest}"
            : "CONCAT({$prefix}, {$rest})";

        static::query()
            ->where('office', $this->office)
            ->where('path', 'like', $was . '/%')
            ->update(['path' => DB::raw($newPath)]);
    }

    protected static function booted(): void
    {
        static::creating(function (self $folder) {
            if (blank($folder->path)) {
                $parent = $folder->parent_id ? static::find($folder->parent_id) : null;
                $folder->path = ($parent?->path ?? '') . '/' . $folder->name;
            }
        });
    }
}
