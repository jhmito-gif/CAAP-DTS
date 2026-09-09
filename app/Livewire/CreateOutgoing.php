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

class CreateOutgoing extends Component
{
    public $reference;

    #[Rule('required|string|max:255')]
    public $subject;
    public $office;    
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
        'subject' => 'required|string',
        'remarks' => 'required|string',
        'status' => 'required',
        ]);

        $year = Carbon::now()->year;
        $office = $this->office;

        // // Get latest record with matching office and year
        // $latest = Record::where('origin', Auth::user()->office)
        //     ->latest('id')
        //     ->first();


        // if ($latest && preg_match('/\d+/', $latest->reference, $matches)) {
        //     $lastNumber = intval($matches[0]);
        //     $nextNumber = $lastNumber + 1;
        // } else {
        //     $nextNumber = 1;
        // }

        // // Format number with leading zeroes
        // $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // // Create full reference
        // $reference = Auth::user()->office."-{$formattedNumber}-{$year}";


        $year = Carbon::now()->year;

        // Get the latest record with matching office and year
        $latest = Record::where(function ($query) {
                $query->where('origin', Auth::user()->office)
                      ->orWhere('owner', Auth::user()->office);
                    })
                    ->whereYear('created_at', $year)
                    ->latest('id')
                    ->first();


        if ($latest && preg_match('/\d+$/', $latest->reference, $matches)) {
            // Get the last numeric sequence (the counter part)
            $lastNumber = intval($matches[0]);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Format number with leading zeroes (minimum 4 digits)
        $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Create full reference in the format: Office-year-count_number
        $reference = Auth::user()->office . "-{$year}-{$formattedNumber}";

        // Save record
        $record = Record::create([
            'reference' => $reference,
            'subject' => $this->subject,
            'created_by' => Auth::user()->name,
            'owner' => Auth::user()->office,
            'origin' => Auth::user()->office,
        ]);

        Transaction::create([
            'record_id' => $record->id,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'destination' => $this->office,
            'office' => Auth::user()->office,
            'forwarded_by' => Auth::user()->name,
        ]);



        session()->flash('message', 'Record created successfully.');
        $this->reset(['office', 'subject', 'remarks', 'status']);

        $this->dispatch('recordAdded');
        $this->dispatch('close-send-modal');
    }

    public function render()
    {
        return view('livewire.create-outgoing');
    }
}
