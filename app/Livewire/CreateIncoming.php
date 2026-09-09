<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\Office;
use App\Models\Status;
use Carbon\Carbon;

class CreateIncoming extends Component
{    
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
        'office' => 'required|exists:offices,name',
        'subject' => 'required|string',
        'remarks' => 'required|string',
        'status' => 'required',
        ]);

        $reference = $this->generateReferenceForOffice(Auth::user()->office);

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
        $this->reset(['office', 'subject', 'status', 'remarks']);

        $this->dispatch('recordAdded');
    }

    private function generateReferenceForOffice(string $office): string
    {
        $year = Carbon::now()->year;
        $prefix = "{$office}-{$year}-";

        $latest = Record::where(function ($query) use ($office) {
                $query->where('origin', $office)
                    ->orWhere('owner', $office);
            })
            ->where('reference', 'like', "{$prefix}%")
            ->whereYear('created_at', $year)
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($latest && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $latest->reference, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        do {
            $reference = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Record::where('reference', $reference)->exists());

        return $reference;
    }

    public function render()
    {
        return view('livewire.create-incoming');
    }
}
