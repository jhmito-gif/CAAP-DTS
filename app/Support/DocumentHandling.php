<?php

namespace App\Support;

use App\Models\InternalRouting;
use App\Models\Record;
use App\Models\SignatureRequest;
use App\Models\User;

/**
 * Who may move a document next, and why not.
 *
 * Inside an office a document sits with one person: they finish what was asked
 * of them -- accepting the handoff, signing if their signature was requested --
 * before passing it to a colleague, and they are the only one who can release
 * it to another office. Admins are not held to this.
 */
class DocumentHandling
{
    /** The person this office's copy currently sits with, if anyone. */
    public static function holder(Record $record, string $office): ?InternalRouting
    {
        return blank($office) ? null : InternalRouting::holderFor($record->id, $office);
    }

    /** A document on this record still waiting for this person's signature. */
    public static function pendingSignature(Record $record, User $user): ?SignatureRequest
    {
        return SignatureRequest::where('record_id', $record->id)
            ->where('signer_id', $user->id)
            ->whereNull('signed_at')
            ->orderBy('id')
            ->first();
    }

    /**
     * Why this person cannot pass the document to a colleague, or null.
     */
    public static function passBlocker(Record $record, ?User $user): ?string
    {
        if (! $user || blank($user->office)) {
            return 'Your account is not assigned to an office.';
        }

        if ($user->isAdmin()) {
            return null;
        }

        $holder = static::holder($record, $user->office);

        if ($holder && (int) $holder->to_user_id !== (int) $user->id) {
            return "This document is with {$holder->to_name}.";
        }

        if ($holder && $holder->isPending()) {
            return 'Accept the document first.';
        }

        if (static::pendingSignature($record, $user)) {
            return 'Sign the document before passing it on.';
        }

        return null;
    }

    /**
     * Why this person cannot send the document to another office, or null.
     */
    public static function sendBlocker(Record $record, ?User $user): ?string
    {
        if (! $user || blank($user->office)) {
            return 'Your account is not assigned to an office.';
        }

        if ($user->isAdmin()) {
            return null;
        }

        $holder = static::holder($record, $user->office);

        if ($holder && (int) $holder->to_user_id !== (int) $user->id) {
            return "This document is with {$holder->to_name} — only they can send it out.";
        }

        if (static::pendingSignature($record, $user)) {
            return 'Sign the document before sending it to another office.';
        }

        return null;
    }
}
