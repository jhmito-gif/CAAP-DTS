<?php

namespace App\Notifications;

use App\Models\Signature;
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

        return [
            'type' => 'document_signed',
            'record_id' => $record?->id,
            'reference' => $record?->reference,
            'subject' => $record?->subject,
            'is_urgent' => (bool) $record?->is_urgent,
            'title' => $complete ? 'Document fully signed' : 'Document signed',
            'message' => "{$this->signature->signer_name} signed " . ($attachment?->original_name ?? 'a document')
                . ($record ? " on {$record->reference}" : '')
                . ($complete ? '. All signatures are complete.' : '.'),
        ];
    }
}
