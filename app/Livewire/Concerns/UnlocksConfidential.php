<?php

namespace App\Livewire\Concerns;

use App\Models\Record;
use App\Support\Audit;
use Illuminate\Support\Facades\Auth;

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

            Audit::log('record.unlocked', $record);
        } else {
            $this->unlockToken = '';

            Audit::log('record.unlock_failed', $record);

            $this->addError('unlockToken', 'Incorrect access token.');
        }
    }

    /**
     * Who opened a confidential record's page goes in the audit log -- also
     * those who only see that it exists ("masked"). Ordinary records are not
     * logged per view; that would bury the log.
     */
    protected function auditConfidentialView(): void
    {
        $record = $this->unlockableRecord();

        if ($record?->is_confidential) {
            Audit::log('record.viewed', $record, [], ['masked' => $record->isMaskedFor(Auth::user()) ?: null]);
        }
    }
}
