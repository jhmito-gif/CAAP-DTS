<?php

namespace App\Livewire\Concerns;

use App\Models\Record;

/**
 * Shared confidential-unlock behaviour for record detail components.
 *
 * The unlock is transient (a public property that resets on every fresh mount),
 * so a cleared viewer must re-enter the record's access token each time they
 * open it. The security itself is enforced on the routes via short-lived
 * signed URLs generated only after this check passes.
 */
trait UnlocksConfidential
{
    public bool $confidentialUnlocked = false;

    public string $unlockToken = '';

    protected function unlockableRecord(): ?Record
    {
        if (property_exists($this, 'record') && $this->record) {
            return $this->record;
        }

        return isset($this->recordId) ? Record::find($this->recordId) : null;
    }

    public function unlockConfidential(): void
    {
        $record = $this->unlockableRecord();

        if (! $record) {
            return;
        }

        if ($record->checkConfidentialToken($this->unlockToken)) {
            $this->confidentialUnlocked = true;
            $this->unlockToken = '';
        } else {
            $this->addError('unlockToken', 'Incorrect access token.');
        }
    }
}
