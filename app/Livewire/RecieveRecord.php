<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\Record;
use App\Models\Office;
use Carbon\Carbon;

class RecieveRecord extends Component
{
    public $reference;

    #[Rule('required|string|max:255')]
    public $subject;
    public $office;
    public $officeOptions = [];

    public function mount()
    {
        // Pluck all offices (id => name)
        $this->officeOptions = Office::pluck('name', 'name')->toArray();
    }

    public function createRecord(){
        
        $this->validate([
        'office' => 'required|string',
        'subject' => 'required|string',
        'reference' => 'required|string',
        ]);

        $year = Carbon::now()->year;
        $office = $this->office;

        // Get latest record with matching office and year
        $latest = Record::where('origin', $office)
            ->whereYear('created_at', $year)
            ->latest('id')
            ->first();

        if ($latest && preg_match('/\d{4}/', $latest->reference, $matches)) {
            $lastNumber = intval($matches[0]);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Format number with leading zeroes
        $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Save record
        Record::create([
            'reference' => $this->reference,
            'subject' => $this->subject,
            'created_by' => Auth::user()->name,
            'owner' => Auth::user()->office,
            'origin' => $this->office,
        ]);

        session()->flash('message', 'Record created successfully.');
        $this->reset(['office', 'subject']);
    }

    public function render()
    {
        return view('livewire.recieve-record');
    }
}
