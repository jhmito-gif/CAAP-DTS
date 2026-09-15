<?php

namespace App\Policies;

use App\Models\Record;
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
        return $this->update($user, $record);
    }
}
