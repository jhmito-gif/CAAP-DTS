<?php

namespace App\Livewire;

use App\Models\Record;
use App\Models\Transaction;
use App\Models\Office;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Concerns\ReceivesTransactions;
use App\Livewire\Concerns\UnlocksConfidential;
use App\Support\DocumentHandling;
use App\Support\OfficeReference;
use Livewire\Component;

class OutgoingTransaction extends Component
{
    use ReceivesTransactions;
    use UnlocksConfidential;

    public $recordId;

    public $remarks, $status, $office; // Inputs
    public $officeOptions = [];
    public $statusOptions = [];
    public $record;
    public $transactions;
    public $showSendButton = false;

    public function mount($recordId)
    {
        $this->recordId = $recordId;
        $this->record = Record::findOrFail($recordId);

        // If record does not exist or user not authorized
        if (!$this->record || Auth::user()->office !== $this->record->owner) {
            session()->flash('error', 'Unauthorized access to record.');
            return redirect()->route('dashboard');
        }

        $this->officeOptions = Office::pluck('name', 'name')->toArray();
        $this->statusOptions = Status::pluck('name', 'name')->toArray();
    }

    public function sendTransaction()
    {
        $this->validate([
            'remarks' => 'required|string',
            'status' => 'required|string',
            'office' => 'required|string',
        ]);

        $record = Record::findOrFail($this->recordId);

        // The person holding it releases it, once they have done what was asked.
        if ($blocker = DocumentHandling::sendBlocker($record, Auth::user())) {
            session()->flash('error', $blocker);

            return;
        }

        $transact = Transaction::create([
            'record_id' => $this->recordId,
            'internal_reference' => $record->reference,
            'origin_reference' => $record->originNumber(),
            // The receiving office's own number, ready on the RAS before it arrives.
            'received_reference' => OfficeReference::allocate($record, $this->office),
            'remarks' => $this->remarks,
            'status' => $this->status,
            'destination' => $this->office,
            'office' => Auth::user()->office,
            'forwarded_by' => Auth::user()->name,
        ]);

        $this->reset(['remarks', 'status', 'office']);
        session()->flash('message', 'Record successfully sent to ' . $transact->destination . '.');
        $this->dispatch('$refresh');
        $this->dispatch('closeModal');
    }

    /**
     * Re-render so the tagged-personnel list reflects the tagging modal.
     */
    #[\Livewire\Attributes\On('tags-updated')]
    public function refreshTags(): void
    {
        //
    }

    /**
     * Re-read the record after it was edited in the manage-record modal.
     */
    #[\Livewire\Attributes\On('record-updated')]
    public function refreshRecord(): void
    {
        $this->record->refresh();
    }

    /**
     * Permanently remove an attachment (file + record). Owning office or admin
     * only -- used to purge a confidential file.
     */
    public function deleteAttachment($attachmentId): void
    {
        $attachment = \App\Models\Attachment::where('record_id', $this->recordId)
            ->whereKey($attachmentId)
            ->first();

        if (! $attachment) {
            return;
        }

        $user = Auth::user();
        $isOwner = in_array($user->office, [$this->record->owner, $this->record->origin], true);

        if (! $isOwner && ! $user->isAdmin()) {
            session()->flash('error', 'Only the originating office or an admin can remove files.');
            return;
        }

        // A locked confidential file is not named back to anyone, not even here.
        $name = $attachment->displayNameFor($user, $this->confidentialUnlocked);

        // Documents sent for signature keep their audit trail.
        if ($attachment->signatureRequests()->exists()) {
            session()->flash('error', "\"{$name}\" was sent for signature and cannot be removed.");
            return;
        }

        $attachment->delete(); // model hook deletes the underlying file

        session()->flash('message', "\"{$name}\" was permanently removed.");
    }

    public function render()
    {
        $this->record->load('taggedUsers', 'attachments');

        $this->transactions = Transaction::where('record_id', $this->recordId)
            ->orderBy('created_at', 'desc')
            ->get();

        $lastTransaction = $this->transactions->first();
        $receivableTransaction = $this->transactions->first(function ($transaction) {
            return $transaction->date_recieved === null;
        });

        $rasTransactions = Transaction::where('record_id', $this->recordId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $this->showSendButton = $lastTransaction && $lastTransaction->date_recieved !== null;

        // This office's own reference ID for the document, set from the header
        // button whether or not the movement has been received yet.
        $officeMovements = Transaction::where('record_id', $this->recordId)
            ->where('destination', Auth::user()->office);

        // Every office's reference ID, newest first, stacked above the record's
        // own number exactly as the RAS prints them.
        $stackedReferences = $rasTransactions
            ->filter(fn ($transaction) => filled($transaction->received_reference))
            ->reverse()
            ->unique('received_reference')
            ->reject(fn ($transaction) => $transaction->received_reference === $this->record->reference)
            ->map(fn ($transaction) => ['office' => $transaction->destination, 'reference' => $transaction->received_reference])
            ->values();

        return view('livewire.outgoing-transaction', [
            'transactions' => $this->transactions,
            'record' => $this->record,
            'rasTransactions' => $rasTransactions,
            'showSendButton' => $this->showSendButton,
            'receivableTransaction' => $receivableTransaction,
            // Once a colleague holds it, only they release it to another office.
            'sendBlocker' => $this->showSendButton ? DocumentHandling::sendBlocker($this->record, Auth::user()) : null,
            'canAssignReference' => (clone $officeMovements)->exists(),
            'officeReference' => (clone $officeMovements)->whereNotNull('received_reference')->orderByDesc('id')->value('received_reference'),
            'stackedReferences' => $stackedReferences,
        ]);
    }
}
