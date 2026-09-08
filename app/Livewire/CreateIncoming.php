<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\Office;
use App\Models\Status;
use Carbon\Carbon;

class CreateIncoming extends Component
{    
    #[Rule('required|string|max:255')]
    public $reference = '';
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
        
        $this->validate([
        'office' => 'required',        
        'reference' => 'required|string|unique:records,reference',
        'subject' => 'required|string',
        'remarks' => 'required|string',
        'status' => 'required',
        ]);

        $office = $this->office;
        $reference = $this->reference;

        // Save record
        $record = Record::create([
            'reference' => $reference,
            'subject' => $this->subject,
            'created_by' => Auth::user()->name,
            'owner' => Auth::user()->office,
            'origin' => $this->office,
        ]);

        Transaction::create([
            'record_id' => $record->id,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'destination' => Auth::user()->office,
            'office' => $this->office,
            'forwarded_by' => Auth::user()->name,
        ]);



        session()->flash('message', 'Record received successfully.');
        $this->reset(['office', 'subject', 'reference', 'status', 'remarks']);

        $this->dispatch('recordAdded');
    }

    public function render()
    {
        return view('livewire.create-incoming');
    }
}
