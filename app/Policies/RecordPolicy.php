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

    /**
     * A confidential record's access token can be reset by whoever set it
     * (the creator) and by admins -- also after the record was received,
     * since forgetting a token is what makes a reset necessary. Nobody else
     * in the office may: the token is what keeps them out.
     */
    public function resetAccessToken(User $user, Record $record): bool
    {
        return (bool) $record->is_confidential
            && ($user->isAdmin() || $record->isCreatedBy($user));
    }

    /**
     * Nobody deletes a record, admins included: the trail of what was sent
     * and received must stay whole. A mistake is corrected by editing, and
     * the audit log keeps what it said before.
     */
    public function delete(User $user, Record $record): bool
    {
        return false;
    }
}
