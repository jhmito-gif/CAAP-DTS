<?php

namespace App\Livewire;

use App\Models\Record;
use App\Models\Transaction;
use App\Models\Office;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OutgoingTransaction extends Component
{
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

        $transact = Transaction::create([
            'record_id' => $this->recordId,
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

    public function render()
    {
        $this->transactions = Transaction::where('record_id', $this->recordId)
            ->orderBy('created_at', 'desc')
            ->get();

        $lastTransaction = $this->transactions->first();

        $this->showSendButton = $lastTransaction && $lastTransaction->date_recieved !== null;

        return view('livewire.outgoing-transaction', [
            'transactions' => $this->transactions,
            'record' => $this->record,
            'showSendButton' => $this->showSendButton,
        ]);
    }
}
