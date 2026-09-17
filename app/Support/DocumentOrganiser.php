<?php

namespace App\Support;

use App\Models\DocumentFolder;
use App\Models\User;

/**
 * Who may reshape an office's document tree.
 *
 * Reading is settled elsewhere (DocumentLibraryQuery, and the confidential
 * rules on each file). This is only about organising: creating, renaming,
 * moving and deleting folders, and filing documents into them. An office's
 * designated document managers may do it for their own office; system admins
 * may do it anywhere.
 */
class DocumentOrganiser
{
    public static function mayOrganise(?User $user, string $office): bool
    {
        if (! $user || blank($office)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return (bool) $user->manages_documents && $user->office === $office;
    }

    public static function mayOrganiseFolder(?User $user, ?DocumentFolder $folder): bool
    {
        return $folder ? self::mayOrganise($user, (string) $folder->office) : false;
    }

    /**
     * The message shown when someone tries to organise a tree that is not
     * theirs to organise -- null when they may.
     */
    public static function blocker(?User $user, string $office): ?string
    {
        if (self::mayOrganise($user, $office)) {
            return null;
        }

        return $user && $user->office === $office
            ? 'Only your office\'s document managers can organise this library.'
            : 'This library belongs to ' . ($office ?: 'another office') . '.';
    }
}
