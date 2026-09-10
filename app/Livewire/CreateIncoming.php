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
use Carbon\Carbon;

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

        $reference = $this->generateReferenceForOffice(Auth::user()->office);

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
            'remarks' => $this->remarks,
            'status' => $this->status,
            'destination' => Auth::user()->office,
            'office' => $this->office,
            'forwarded_by' => Auth::user()->name,
        ]);

        $this->advanceReferenceSequence(Auth::user()->office, $reference);



        session()->flash('message', 'Record received successfully.');
        $this->reset(['office', 'originReference', 'subject', 'status', 'remarks']);

        $this->dispatch('recordAdded');
    }

    public function getInternalReferencePreviewProperty(): string
    {
        return $this->generateReferenceForOffice(Auth::user()->office);
    }

    private function generateReferenceForOffice(string $office): string
    {
        $year = Carbon::now()->year;
        $prefix = "{$office}-{$year}-";
        $sequence = ReferenceSequence::where('office', $office)
            ->where('year', $year)
            ->first();

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

        if ($sequence) {
            $nextNumber = max($nextNumber, $sequence->next_number);
        }

        do {
            $reference = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Record::where('reference', $reference)->exists());

        return $reference;
    }

    private function advanceReferenceSequence(string $office, string $reference): void
    {
        $year = Carbon::now()->year;
        $prefix = "{$office}-{$year}-";

        if (! preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $reference, $matches)) {
            return;
        }

        ReferenceSequence::updateOrCreate(
            ['office' => $office, 'year' => $year],
            ['next_number' => intval($matches[1]) + 1]
        );
    }

    public function render()
    {
        return view('livewire.create-incoming', [
            'internalReferencePreview' => $this->internalReferencePreview,
        ]);
    }
}
