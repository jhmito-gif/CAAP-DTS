<?php

namespace App\Notifications;

use App\Models\InternalRouting;
use Illuminate\Notifications\Notification;

/**
 * Sent to the person a document was handed to inside their office. Not queued,
 * for the same reason as RecordTagged.
 */
class DocumentRoutedInternally extends Notification
{
    public function __construct(protected InternalRouting $routing)
    {
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
        $record = $this->routing->record;
        $from = $this->routing->from_name ?: 'Someone';

        return [
            'type' => 'internal_routing',
            'record_id' => $record?->id,
            'internal_routing_id' => $this->routing->id,
            'url' => $record ? route('show-transactions', $record->id, false) : null,
            'reference' => $record?->reference,
            'subject' => $record?->subject,
            'is_urgent' => (bool) $record?->is_urgent,
            'title' => 'A document was passed to you',
            'message' => "{$from} passed " . ($record?->reference ?? 'a document') . ' to you'
                . ($this->routing->action ? " for {$this->routing->action}" : ''),
        ];
    }
}
