<?php

namespace App\Livewire;

use App\Models\Office;
use App\Models\Status;
use App\Models\Record;
use App\Livewire\Concerns\ReceivesTransactions;
use App\Livewire\Concerns\WatchesRecordActivity;
use App\Livewire\Concerns\UnlocksConfidential;
use App\Support\DocumentHandling;
use App\Support\OfficeReference;
use Livewire\Component;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TransactionTable extends Component
{
    use ReceivesTransactions;
    use WatchesRecordActivity;
    use UnlocksConfidential;

    public $recordId;

    public $remarks, $status, $office; // Inputs
    public $officeOptions = [];
    public $statusOptions = [];

    public function mount( $recordId)
    {
	$this->recordId = $recordId;
        $this->record = Record::find($recordId);

        // If record not found
        if (!$this->record) {
            session()->flash('error', 'Record not found.');
            return $this->redirectRoute('dashboard'); // Livewire-safe redirect
        }

        // Owner or origin office, routing chain, tagged personnel or granted access.
        // The origin office must get in: its outgoing list shows records another
        // office logged as incoming, and those open here.
        if (! $this->record->isAccessibleBy(Auth::user())) {
            session()->flash('error', 'Unauthorized access to record.');
            return $this->redirectRoute('dashboard'); // Livewire-safe redirect
        }

        // Load dropdowns
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
        session()->flash('message', 'Record successfully sent to ' . $transact->destination . '.' );
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
     * Re-render after the record was edited in the manage-record modal.
     */
    #[\Livewire\Attributes\On('record-updated')]
    public function refreshRecord(): void
    {
        //
    }

    public function render()
    {
        $this->noteActivitySeen();

        $transactions = Transaction::where('record_id', $this->recordId)
            ->where(function ($query) {
                    $query->where('office', Auth::user()->office)
                        ->orWhere('destination', Auth::user()->office);
                })
            ->orderBy('created_at', 'desc')
            ->get();
        
        $record = Record::with('taggedUsers')->find($this->recordId);

        $rasTransactions = Transaction::where('record_id', $this->recordId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $lastTransaction = $transactions->first(); // already ordered DESC
        $receivableTransaction = $transactions->first(function ($transaction) {
            return $transaction->date_recieved === null;
        });

        $showSendButton = false;

        if (
            $lastTransaction &&
            $lastTransaction->date_recieved !== null
        ) {
            $showSendButton = true;
        }

        // This office's own reference ID for the document, set from the header
        // button whether or not the movement has been received yet.
        $officeMovements = Transaction::where('record_id', $this->recordId)
            ->where('destination', Auth::user()->office);

        // Every office's reference ID, newest first, stacked above the record's
        // own number exactly as the RAS prints them.
        // None while office reference IDs are switched off: the record's own
        // number is then the only one (OfficeReference::centralised).
        $stackedReferences = OfficeReference::centralised() ? collect() : $rasTransactions
            ->filter(fn ($transaction) => filled($transaction->received_reference))
            ->reverse()
            ->unique('received_reference')
            ->reject(fn ($transaction) => $transaction->received_reference === $record?->reference)
            ->map(fn ($transaction) => ['office' => $transaction->destination, 'reference' => $transaction->received_reference])
            ->values();

        return view('livewire.transaction-table', [
            'transactions' => $transactions,
            'record' => $record,
            'rasTransactions' => $rasTransactions,
            'showSendButton' => $showSendButton,
            'receivableTransaction' => $receivableTransaction,
            // Once a colleague holds it, only they release it to another office.
            'sendBlocker' => $showSendButton && $record ? DocumentHandling::sendBlocker($record, Auth::user()) : null,
            'canAssignReference' => ! OfficeReference::centralised() && (clone $officeMovements)->exists(),
            'officeReference' => OfficeReference::centralised()
                ? null
                : (clone $officeMovements)->whereNotNull('received_reference')->orderByDesc('id')->value('received_reference'),
            'stackedReferences' => $stackedReferences,
        ]);
    }
}
