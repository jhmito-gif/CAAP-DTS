<?php

namespace App\Support;

use App\Models\DocumentFolder;
use App\Models\FolderPolicy;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * What a person may do in a folder.
 *
 * Four answers, in order: none, view, edit (view and add files), manage (edit
 * and reshape the folder, and share it).
 *
 * The rules, in the order they are applied:
 *
 *   1. System admins manage everything.
 *   2. An office's document managers manage that office's tree.
 *   3. Grants on the folder or any folder above it apply -- sharing a folder
 *      shares what is inside it -- and the most generous one wins.
 *   4. A closed folder (or one below a closed folder) shows nothing without
 *      a grant.
 *   5. Otherwise the owning office may view and add files; nobody else may.
 *
 * Confidential record files are not governed here: a shortcut only points at a
 * file, and the file keeps its own rules (Attachment/Record). A folder grant
 * never widens those.
 */
class FolderAccess
{
    private const ORDER = ['none' => 0, 'view' => 1, 'edit' => 2, 'manage' => 3];

    /** Policies by folder id, for the life of one request. */
    private array $policies = [];

    /** Folders by id, likewise. */
    private array $folders = [];

    public function level(?User $user, ?DocumentFolder $folder): string
    {
        if (! $user) {
            return 'none';
        }

        // The office's root, outside any folder.
        if (! $folder) {
            return DocumentOrganiser::mayOrganise($user, (string) $user->office) ? 'manage' : 'edit';
        }

        if ($user->isAdmin() || DocumentOrganiser::mayOrganise($user, (string) $folder->office)) {
            return 'manage';
        }

        $granted = 'none';
        $closed = false;

        foreach ($this->chain($folder) as $step) {
            $closed = $closed || (bool) $step->is_restricted;

            foreach ($this->policiesFor($step) as $policy) {
                if ($policy->covers($user)) {
                    $granted = $this->best($granted, (string) $policy->level);
                }
            }
        }

        if ($granted !== 'none') {
            return $granted;
        }

        if ($closed) {
            return 'none';
        }

        // The owning office, with nothing said to the contrary.
        return filled($user->office) && $user->office === $folder->office ? 'edit' : 'none';
    }

    public function allows(?User $user, ?DocumentFolder $folder, string $needed): bool
    {
        return self::ORDER[$this->level($user, $folder)] >= self::ORDER[$needed];
    }

    /** Folders this person may not even see, by id -- for filtering listings. */
    public function hiddenFolderIds(?User $user, string $office): array
    {
        return DocumentFolder::treeFor($office)
            ->reject(fn (DocumentFolder $folder) => $this->allows($user, $folder, 'view'))
            ->pluck('id')
            ->all();
    }

    /**
     * The folder and its ancestors, nearest first.
     *
     * @return array<int, DocumentFolder>
     */
    private function chain(DocumentFolder $folder): array
    {
        $chain = [$folder];
        $walk = $folder;

        // Paths are at most a handful of levels deep; the guard is for safety.
        for ($depth = 0; $depth < 20 && $walk->parent_id; $depth++) {
            $parent = $this->folders[$walk->parent_id] ??= DocumentFolder::find($walk->parent_id);

            if (! $parent) {
                break;
            }

            $chain[] = $parent;
            $walk = $parent;
        }

        return $chain;
    }

    /**
     * @return Collection<int, FolderPolicy>
     */
    private function policiesFor(DocumentFolder $folder): Collection
    {
        return $this->policies[$folder->id] ??= FolderPolicy::where('folder_id', $folder->id)->get();
    }

    private function best(string $a, string $b): string
    {
        return self::ORDER[$a] >= self::ORDER[$b] ? $a : $b;
    }
}
