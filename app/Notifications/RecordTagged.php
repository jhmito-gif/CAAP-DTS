<?php

namespace App\Notifications;

use App\Models\Record;
use Illuminate\Notifications\Notification;

/**
 * Sent when someone tags a person as being the subject of a record.
 *
 * Deliberately NOT ShouldQueue: this project runs QUEUE_CONNECTION=database,
 * so queueing would leave notifications stuck until a worker runs. Writing a
 * database notification is a single insert, so sending inline is cheap.
 */
class RecordTagged extends Notification
{
    public function __construct(
        protected Record $record,
        protected ?string $taggedBy = null,
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
        return [
            'type' => 'record_tagged',
            'record_id' => $this->record->id,
            'reference' => $this->record->reference,
            'subject' => $this->record->subject,
            'origin' => $this->record->origin,
            'is_urgent' => (bool) $this->record->is_urgent,
            'tagged_by' => $this->taggedBy,
            'title' => 'You were tagged on a document',
            'message' => trim(($this->taggedBy ? $this->taggedBy . ' tagged you on ' : 'You were tagged on ')
                . ($this->record->reference ?? 'a record')),
        ];
    }
}
