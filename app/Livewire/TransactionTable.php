<?php

namespace App\Livewire;

use App\Models\Access;
use App\Models\Office;
use App\Models\RecordTagging;
use App\Models\Status;
use App\Models\Record;
use Livewire\Component;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TransactionTable extends Component
{
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

        $userOffice = Auth::user()->office;

        // Check if user’s office is part of any transaction (destination or office)
        $hasAccess = Transaction::where('record_id', $recordId)
            ->where(function ($query) use ($userOffice) {
                $query->where('destination', $userOffice)
                    ->orWhere('office', $userOffice);
            })
            ->exists();

        // Someone tagged on this record can open it, otherwise the tag
        // notification would link somewhere they are bounced out of.
        $isTagged = RecordTagging::where('record_id', $recordId)
            ->where('user_id', Auth::id())
            ->exists();

        // An office can be granted explicit access to a record by an admin.
        $isGranted = Access::where('record_id', $recordId)
            ->where('office', $userOffice)
            ->exists();

        // Verify ownership, transaction involvement, tag, or granted access
        if ($this->record->owner !== $userOffice && !$hasAccess && !$isTagged && !$isGranted) {
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

        $transact = Transaction::create([
            'record_id' => $this->recordId,
            'internal_reference' => $record->reference,
            'origin_reference' => $record->origin_reference,
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

    public function markAsReceived($id)
    {
        $transaction = Transaction::find($id);

        if ($transaction && !$transaction->date_recieved) {
            $transaction->update([
                'recieved_by' => Auth::user()->name,
                'date_recieved' => Carbon::now(),
            ]);
        }
    }

    /**
     * Re-render so the tagged-personnel list reflects the tagging modal.
     */
    #[\Livewire\Attributes\On('tags-updated')]
    public function refreshTags(): void
    {
        //
    }

    public function render()
    {
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

        return view('livewire.transaction-table', [
            'transactions' => $transactions,            
            'record' => $record,
            'rasTransactions' => $rasTransactions,
            'showSendButton' => $showSendButton,
            'receivableTransaction' => $receivableTransaction,
        ]);
    }
}
