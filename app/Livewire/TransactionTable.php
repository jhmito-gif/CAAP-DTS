<?php

namespace App\Livewire;

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
        $hasAccess = \App\Models\Transaction::where('record_id', $recordId)
            ->where(function ($query) use ($userOffice) {
                $query->where('destination', $userOffice)
                    ->orWhere('office', $userOffice);
            })
            ->exists();

        // Verify ownership or transaction involvement
        if ($this->record->owner !== $userOffice && !$hasAccess) {
            session()->flash('error', 'Unauthorized access to record.');
            return $this->redirectRoute('dashboard'); // Livewire-safe redirect
        }

        // Load dropdowns
        $this->officeOptions = \App\Models\Office::pluck('name', 'name')->toArray();
        $this->statusOptions = \App\Models\Status::pluck('name', 'name')->toArray();
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
        session()->flash('message', 'Record successfully sent to ' . $transact->destination . '.' );
        $this->dispatch('$refresh');
        $this->dispatch('closeModal'); 
    }

    public function markAsReceived($id)
    {
        $transaction = \App\Models\Transaction::find($id);

        if ($transaction && !$transaction->date_recieved) {
            $transaction->update([
                'recieved_by' => Auth::user()->name, 
                'date_recieved' => Carbon::now(),
            ]);
        }
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
        
        $record = Record::find($this->recordId);

        $lastTransaction = $transactions->first(); // already ordered DESC

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
            'showSendButton' => $showSendButton
        ]);
    }
}
