<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Office;
use App\Models\Record;
use App\Models\Status;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The edit modal for a record, opened from the incoming and outgoing
 * lists and the record pages, and the access-token reset for confidential
 * records. Who may act is decided by RecordPolicy.
 */
class ManageRecord extends Component
{
    use WithFileUploads;
    use Concerns\PicksDocumentTypes;

    public ?int $recordId = null;

    // Edit form
    public $subject = '';
    public $originReference = '';
    public $office = '';
    public $status = '';
    public $remarks = '';

    /** New files to attach, applied on save. Files already on a record are never removed. */
    public $newAttachments = [];

    public $officeOptions = [];
    public $statusOptions = [];

    // Access token reset (confidential records)
    public ?int $tokenRecordId = null;
    public string $newToken = '';
    public string $newToken_confirmation = '';
    public string $accountPassword = '';

    public function mount(): void
    {
        $this->officeOptions = Office::pluck('name', 'name')->toArray();
        $this->statusOptions = Status::pluck('name', 'name')->toArray();
    }

    #[On('edit-record')]
    public function edit(int $recordId): void
    {
        $record = Record::find($recordId);

        if (! $this->allowed($record, 'update')) {
            return;
        }

        $first = $record->firstTransaction();

        $this->resetValidation();
        $this->reset(['newAttachments', 'uploadTypes']);

        $this->recordId = $record->id;
        $this->subject = (string) $record->subject;
        $this->originReference = (string) $record->origin_reference;
        $this->office = (string) $first?->destination;
        $this->status = (string) $first?->status;
        $this->remarks = (string) $first?->remarks;

        $this->dispatch('open-edit-record-modal');
    }

    public function save(): void
    {
        $record = Record::find($this->recordId);

        if (! $this->allowed($record, 'update')) {
            $this->dispatch('close-edit-record-modal');
            return;
        }

        $first = $record->firstTransaction();
        $isIncoming = $record->isIncoming();
        $canEditRouting = $this->canEditRouting($first);

        $rules = [
            'subject' => 'required|string|max:'.Record::SUBJECT_MAX,
            'newAttachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
        ] + $this->uploadTypeRules((array) $this->newAttachments);

        if ($isIncoming) {
            $rules['originReference'] = [
                'nullable',
                'string',
                'max:255',
                Rule::unique('records', 'origin_reference')
                    ->where(fn ($query) => $query->where('origin', $record->origin))
                    ->ignore($record->id),
            ];
        }

        if ($canEditRouting) {
            $rules['status'] = 'required|string';
            $rules['remarks'] = 'required|string';

            if (! $isIncoming) {
                $rules['office'] = 'required|exists:offices,name';
            }
        }

        $this->validate($rules, [], [
            'originReference' => 'origin reference',
            'office' => 'destination office',
            'newAttachments.*' => 'attachment',
        ] + $this->uploadTypeAttributes((array) $this->newAttachments));

        DB::transaction(function () use ($record, $first, $isIncoming, $canEditRouting) {
            $record->subject = $this->subject;

            if ($isIncoming) {
                $originReference = trim((string) $this->originReference);
                $record->origin_reference = $originReference !== '' ? $originReference : null;

                // A correction carries into this record's routing snapshots.
                if ($record->isDirty('origin_reference')) {
                    Transaction::where('record_id', $record->id)
                        ->update(['origin_reference' => $record->origin_reference]);
                }
            }

            $record->save();

            if ($canEditRouting) {
                $first->status = $this->status;
                $first->remarks = $this->remarks;

                if (! $isIncoming) {
                    $first->destination = $this->office;
                }

                $first->save();
            }

            foreach ($this->newAttachments as $index => $file) {
                Attachment::storeEncrypted($record, $first, $file, Auth::user()->name, $this->uploadTypeFor($index));
            }
        });

        $this->reset(['newAttachments', 'uploadTypes']);

        $this->banner("Record {$record->reference} was updated.");
        $this->dispatch('close-edit-record-modal');
        $this->dispatch('record-updated');
        $this->dispatch('recordAdded');
    }

    #[On('reset-access-token')]
    public function startTokenReset(int $recordId): void
    {
        $record = Record::find($recordId);

        if (! $this->allowedTokenReset($record)) {
            return;
        }

        $this->resetValidation();
        $this->reset(['newToken', 'newToken_confirmation', 'accountPassword']);
        $this->tokenRecordId = $record->id;

        $this->dispatch('open-reset-token-modal');
    }

    /**
     * Replaces a confidential record's access token without needing the old
     * one -- that is the point when it was forgotten. The person confirms
     * with their own account password, so an unattended session cannot be
     * used to take over a record, and the reset lands in the audit log.
     */
    public function resetToken(): void
    {
        $record = Record::find($this->tokenRecordId);

        if (! $this->allowedTokenReset($record)) {
            $this->dispatch('close-reset-token-modal');
            return;
        }

        $throttle = 'reset-access-token:' . Auth::id();

        if (RateLimiter::tooManyAttempts($throttle, 5)) {
            $this->addError('accountPassword', 'Too many tries. Wait ' . RateLimiter::availableIn($throttle) . ' seconds and try again.');
            return;
        }

        try {
            $this->validate([
                'newToken' => ['required', 'string', 'min:4', 'max:100', 'confirmed'],
                'accountPassword' => ['required', 'string', 'current_password'],
            ], [
                'accountPassword.current_password' => 'That is not your account password.',
            ], [
                'newToken' => 'new access token',
                'accountPassword' => 'your account password',
            ]);
        } catch (ValidationException $e) {
            if (isset($e->validator->failed()['accountPassword']['CurrentPassword'])) {
                RateLimiter::hit($throttle, 60);
            }

            throw $e;
        }

        RateLimiter::clear($throttle);

        $record->setConfidentialToken($this->newToken);
        $record->save();

        $this->reset(['tokenRecordId', 'newToken', 'newToken_confirmation', 'accountPassword']);

        $this->banner("The access token for {$record->reference} was reset. The old one no longer opens it; share the new one only with cleared viewers.");
        $this->dispatch('close-reset-token-modal');
        $this->dispatch('record-updated');
    }

    private function allowedTokenReset(?Record $record): bool
    {
        if (! $record) {
            $this->banner('That record no longer exists.', 'danger');
            return false;
        }

        if (Gate::denies('resetAccessToken', $record)) {
            $this->banner($record->is_confidential
                ? "Only the record's creator or an admin can reset the access token for {$record->reference}."
                : "{$record->reference} is not confidential, so it has no access token.", 'danger');

            return false;
        }

        return true;
    }

    public function removeNewAttachment(int $index): void
    {
        unset($this->newAttachments[$index]);
        $this->newAttachments = array_values($this->newAttachments);
        $this->forgetUploadType($index);
    }

    /**
     * Re-checked on every action: the record may have been received elsewhere
     * (and locked) since the buttons were rendered.
     */
    private function allowed(?Record $record, string $ability): bool
    {
        if (! $record) {
            $this->banner('That record no longer exists.', 'danger');
            return false;
        }

        if (Gate::denies($ability, $record)) {
            $this->banner($record->isCreatedBy(Auth::user())
                ? "Record {$record->reference} is locked: it has already been received by another office."
                : "Only the record's creator or an admin can change {$record->reference}.", 'danger');

            return false;
        }

        return true;
    }

    /**
     * The first routing entry stays editable until it is received (admins always).
     */
    private function canEditRouting(?Transaction $first): bool
    {
        return $first !== null && ($first->date_recieved === null || Auth::user()->isAdmin());
    }

    private function banner(string $message, string $style = 'success'): void
    {
        $this->dispatch('banner-message', style: $style, message: $message);
    }

    public function render()
    {
        $record = $this->recordId
            ? Record::with('attachments')->withCount('transactions')->find($this->recordId)
            : null;

        $first = $record?->firstTransaction();

        return view('livewire.manage-record', [
            'record' => $record,
            'firstTransaction' => $first,
            'isIncoming' => (bool) $record?->isIncoming(),
            'canEditRouting' => $record !== null && $this->canEditRouting($first),
            'documentTypes' => \App\Models\DocumentCategory::orderBy('name')->pluck('name', 'id'),
            'tokenRecord' => $this->tokenRecordId ? Record::find($this->tokenRecordId) : null,
        ]);
    }
}
