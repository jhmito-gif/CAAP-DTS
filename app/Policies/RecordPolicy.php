<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\Signature;
use App\Models\User;

class RecordPolicy
{
    /**
     * Admins can always edit; the creator only until another office has
     * received the record, after which it is locked.
     */
    public function update(User $user, Record $record): bool
    {
        return $user->isAdmin()
            || ($record->isCreatedBy($user) && ! $record->isReceivedElsewhere());
    }

    public function delete(User $user, Record $record): bool
    {
        if (! $this->update($user, $record)) {
            return false;
        }

        // Signed documents keep their audit trail; only admins can remove them.
        return $user->isAdmin()
            || ! Signature::whereIn('attachment_id', $record->attachments()->select('id'))->exists();
    }
}
