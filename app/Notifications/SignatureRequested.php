<?php

namespace App\Notifications;

use App\Models\SignatureRequest;
use Illuminate\Notifications\Notification;

/**
 * Sent to a signatory when they are asked to sign a document. Not queued, for
 * the same reason as RecordTagged.
 */
class SignatureRequested extends Notification
{
    public function __construct(
        protected SignatureRequest $signatureRequest,
        protected ?string $requestedBy = null,
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
        $record = $this->signatureRequest->record;
        $document = $this->signatureRequest->attachment?->original_name ?? 'a document';

        return [
            'type' => 'signature_requested',
            'record_id' => $record?->id,
            'signature_request_id' => $this->signatureRequest->id,
            'url' => route('esign.sign', $this->signatureRequest),
            'reference' => $record?->reference,
            'subject' => $record?->subject,
            'is_urgent' => (bool) $record?->is_urgent,
            'title' => 'Your signature is requested',
            'message' => ($this->requestedBy ? "{$this->requestedBy} asked you to sign " : 'You were asked to sign ')
                . $document . ($record ? " on {$record->reference}" : ''),
        ];
    }
}
