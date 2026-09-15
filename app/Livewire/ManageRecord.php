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
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Edit / delete modals for a record, opened from the incoming and outgoing
 * lists and the record pages. Who may act is decided by RecordPolicy.
 */
class ManageRecord extends Component
{
    use WithFileUploads;

    /** On a record page, deleting it leaves nothing to show -- go back to its list. */
    public bool $redirectAfterDelete = false;

    public ?int $recordId = null;

    // Edit form
    public $subject = '';
    public $originReference = '';
    public $office = '';
    public $status = '';
    public $remarks = '';

    /** New files to attach, and ids of existing attachments to remove -- applied on save. */
    public $newAttachments = [];
    public array $removeAttachmentIds = [];

    public $officeOptions = [];
    public $statusOptions = [];

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
        $this->reset(['newAttachments', 'removeAttachmentIds']);

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
            'subject' => 'required|string',
            'newAttachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
        ];

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
        ]);

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

            // Delete via the model (not a bulk query) so each file is removed.
            // Files sent for signature keep their audit trail and are never removed here.
            $record->attachments()
                ->whereIn('id', $this->removeAttachmentIds)
                ->whereDoesntHave('signatureRequests')
                ->get()
                ->each->delete();

            foreach ($this->newAttachments as $file) {
                Attachment::storeEncrypted($record, $first, $file, Auth::user()->name);
            }
        });

        $this->reset(['newAttachments', 'removeAttachmentIds']);

        $this->banner("Record {$record->reference} was updated.");
        $this->dispatch('close-edit-record-modal');
        $this->dispatch('record-updated');
        $this->dispatch('recordAdded');
    }

    public function toggleRemoveAttachment(int $attachmentId): void
    {
        $this->removeAttachmentIds = in_array($attachmentId, $this->removeAttachmentIds, true)
            ? array_values(array_diff($this->removeAttachmentIds, [$attachmentId]))
            : [...$this->removeAttachmentIds, $attachmentId];
    }

    public function removeNewAttachment(int $index): void
    {
        unset($this->newAttachments[$index]);
        $this->newAttachments = array_values($this->newAttachments);
    }

    #[On('delete-record')]
    public function confirmDelete(int $recordId): void
    {
        $record = Record::find($recordId);

        if (! $this->allowed($record, 'delete')) {
            return;
        }

        $this->recordId = $record->id;

        $this->dispatch('open-delete-record-modal');
    }

    public function delete()
    {
        $record = Record::find($this->recordId);

        if (! $this->allowed($record, 'delete')) {
            $this->dispatch('close-delete-record-modal');
            return;
        }

        $reference = $record->reference;
        $list = $record->isIncoming() ? 'incoming-record' : 'outgoing-record';

        $record->delete();
        $this->recordId = null;

        if ($this->redirectAfterDelete) {
            session()->flash('flash.banner', "Record {$reference} was deleted.");
            session()->flash('flash.bannerStyle', 'success');

            return $this->redirectRoute($list);
        }

        $this->banner("Record {$reference} was deleted.");
        $this->dispatch('close-delete-record-modal');
        $this->dispatch('recordAdded');
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
        ]);
    }
}
