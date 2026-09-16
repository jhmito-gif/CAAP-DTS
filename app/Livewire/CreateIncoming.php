<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Record;
use App\Models\ReferenceSequence;
use App\Models\Transaction;
use App\Models\Office;
use App\Models\Status;

class CreateIncoming extends Component
{
    public $originReference = '';
    public $subject;
    public $office = '';
    public $remarks;
    public $status;
    public $destination;
    public $officeOptions = [];
    public $statusOptions = [];

    public function mount()
    {
        // Pluck all offices (id => name)
        $this->officeOptions = Office::pluck('name', 'name')->toArray();
        $this->statusOptions = Status::pluck('name', 'name')->toArray();
    }


    public function createRecord(){
        $this->originReference = trim((string) $this->originReference);

        $this->validate([
        'office' => 'required|exists:offices,name',
        'originReference' => [
            'required',
            'string',
            'max:255',
            Rule::unique('records', 'origin_reference')
                ->where(fn ($query) => $query->where('origin', $this->office)),
        ],
        'subject' => 'required|string',
        'remarks' => 'required|string',
        'status' => 'required',
        ]);

        $reference = ReferenceSequence::nextReferenceFor(Auth::user()->office);

        // Save record
        $record = Record::create([
            'reference' => $reference,
            'origin_reference' => trim($this->originReference),
            'subject' => $this->subject,
            'created_by' => Auth::user()->name,
            'owner' => Auth::user()->office,
            'origin' => $this->office,
        ]);

        Transaction::create([
            'record_id' => $record->id,
            'internal_reference' => $record->reference,
            'origin_reference' => $record->origin_reference,
            // The logging office's own number for the document it just booked in.
            'received_reference' => \App\Support\OfficeReference::resolve($record, Auth::user()->office),
            'remarks' => $this->remarks,
            'status' => $this->status,
            'destination' => Auth::user()->office,
            'office' => $this->office,
            'forwarded_by' => Auth::user()->name,
        ]);

        ReferenceSequence::advance(Auth::user()->office, $reference);



        session()->flash('message', 'Record received successfully.');
        $this->reset(['office', 'originReference', 'subject', 'status', 'remarks']);

        $this->dispatch('recordAdded');
    }

    public function getInternalReferencePreviewProperty(): string
    {
        return ReferenceSequence::nextReferenceFor(Auth::user()->office);
    }

    public function render()
    {
        return view('livewire.create-incoming', [
            'internalReferencePreview' => $this->internalReferencePreview,
        ]);
    }
}
