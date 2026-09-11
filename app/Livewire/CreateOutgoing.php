<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use App\Models\Attachment;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\Office;
use App\Models\Status;
use Carbon\Carbon;

class CreateOutgoing extends Component
{
    use WithFileUploads;

    public $reference;

    #[Rule('required|string|max:255')]
    public $subject;
    public $office;
    public $remarks;
    public $status;
    public $destination;
    public $officeOptions = [];
    public $statusOptions = [];

    /** Files attached to the outgoing document. */
    public $attachments = [];

    /** Lock the record and its files as confidential on creation. */
    public $isConfidential = false;

    /** Confidential viewers: office being browsed + selected personnel ids. */
    public $viewerOffice = '';
    public $viewers = [];

    /** Allowed uploads: office documents and images, up to 10 MB each. */
    protected array $attachmentRules = [
        'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
    ];

    public function removeAttachment($index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function mount()
    {
        // Pluck all offices (id => name)
        $this->officeOptions = Office::pluck('name', 'name')->toArray();
        $this->statusOptions = Status::pluck('name', 'name')->toArray();
        $this->viewerOffice = Auth::user()->office ?? '';
    }

    public function createRecord(){
        
        $this->validate(array_merge([
        'office' => 'required',
        'subject' => 'required|string',
        'remarks' => 'required|string',
        'status' => 'required',
        ], $this->attachmentRules));

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
            'is_confidential' => (bool) $this->isConfidential,
        ]);

        $transaction = Transaction::create([
            'record_id' => $record->id,
            'internal_reference' => $record->reference,
            'origin_reference' => $record->origin_reference,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'destination' => $this->office,
            'office' => Auth::user()->office,
            'forwarded_by' => Auth::user()->name,
        ]);

        // Store each uploaded file privately and record it against the record.
        foreach ($this->attachments as $file) {
            $path = $file->store("attachments/{$record->id}", 'local');

            Attachment::create([
                'record_id' => $record->id,
                'transaction_id' => $transaction->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'disk' => 'local',
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => Auth::user()->name,
                'is_confidential' => (bool) $this->isConfidential,
            ]);
        }

        // Confidential viewers: tag the chosen personnel so they are cleared
        // to see the locked details and files. (Ignored when not confidential.)
        $taggedCount = 0;

        if ($this->isConfidential && ! empty($this->viewers)) {
            $viewerUsers = \App\Models\User::whereIn('id', collect($this->viewers)->map(fn ($id) => (int) $id))->get();

            foreach ($viewerUsers as $viewer) {
                \App\Models\RecordTagging::firstOrCreate(
                    ['record_id' => $record->id, 'user_id' => $viewer->id],
                    ['office' => $viewer->office, 'tagged_by' => Auth::user()->name]
                );
                $taggedCount++;
            }
        }

        $fileCount = count($this->attachments);
        $message = ($this->isConfidential ? 'Confidential record created.' : 'Record created successfully.')
            . ($fileCount > 0 ? " {$fileCount} " . \Illuminate\Support\Str::plural('file', $fileCount) . ' attached.' : '')
            . ($taggedCount > 0 ? " {$taggedCount} " . \Illuminate\Support\Str::plural('viewer', $taggedCount) . ' authorised.' : '');

        session()->flash('message', $message);
        $this->reset(['office', 'subject', 'remarks', 'status', 'attachments', 'isConfidential', 'viewers']);

        $this->dispatch('recordAdded');
        $this->dispatch('close-send-modal');
    }

    public function render()
    {
        $viewerPersonnel = ($this->isConfidential && filled($this->viewerOffice))
            ? \App\Models\User::where('office', $this->viewerOffice)->orderBy('name')->get()
            : collect();

        return view('livewire.create-outgoing', [
            'viewerPersonnel' => $viewerPersonnel,
        ]);
    }
}
