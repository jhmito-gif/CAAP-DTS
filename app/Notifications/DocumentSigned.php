<?php

namespace App\Notifications;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Sent to whoever requested a signature when a signatory signs.
 */
class DocumentSigned extends Notification
{
    public function __construct(
        protected Signature $signature,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $attachment = $this->signature->attachment;
        $record = $attachment?->record;
        $complete = (bool) $attachment?->isSigningComplete();

        // Same rule as the request: a confidential file is never named to a
        // recipient who is not cleared for its record.
        $recipient = $notifiable instanceof User ? $notifiable : null;
        $masked = (bool) $record?->isMaskedFor($recipient);
        $document = $masked ? 'a confidential document' : ($attachment?->original_name ?? 'a document');

        return [
            'type' => 'document_signed',
            'record_id' => $record?->id,
            'reference' => $record?->reference,
            'subject' => $masked ? 'Confidential — hidden from you' : $record?->subject,
            'is_urgent' => (bool) $record?->is_urgent,
            'title' => $complete ? 'Document fully signed' : 'Document signed',
            'message' => "{$this->signature->signer_name} signed " . $document
                . ($record ? " on {$record->reference}" : '')
                . ($complete ? '. All signatures are complete.' : '.'),
        ];
    }
}
