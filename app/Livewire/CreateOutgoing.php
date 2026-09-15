<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use App\Models\Attachment;
use App\Models\Record;
use App\Models\ReferenceSequence;
use App\Models\Transaction;
use App\Models\Office;
use App\Models\Status;
use App\Models\User;
use App\Support\SignatureRequester;

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

    /** Access token that cleared viewers must enter to open a confidential record. */
    public $confidentialToken = '';

    /** Signatories asked to sign every attached PDF: office being browsed + selected personnel ids. */
    public $signerOffice = '';
    public $signers = [];

    /** Allowed uploads: office documents and images, up to 10 MB each. */
    protected array $attachmentRules = [
        'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
    ];

    public function removeAttachment($index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);

        $this->forgetSignersWithoutPdf();
    }

    public function updatedAttachments(): void
    {
        $this->forgetSignersWithoutPdf();
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
        'confidentialToken' => [$this->isConfidential ? 'required' : 'nullable', 'string', 'min:4', 'max:100'],
        'signers' => 'array',
        'signers.*' => 'integer|exists:users,id',
        ], $this->attachmentRules), [], [
            'confidentialToken' => 'access token',
            'signers.*' => 'signatory',
        ]);

        // Next number in this office's own sequence (shared with incoming), in
        // the format: Office-year-count_number
        $reference = ReferenceSequence::nextReferenceFor(Auth::user()->office);

        // Save record
        $record = Record::create([
            'reference' => $reference,
            'subject' => $this->subject,
            'created_by' => Auth::user()->name,
            'owner' => Auth::user()->office,
            'origin' => Auth::user()->office,
            'is_confidential' => (bool) $this->isConfidential,
        ]);

        // Store the access token (hashed) that gates the confidential record.
        if ($this->isConfidential) {
            $record->setConfidentialToken($this->confidentialToken);
            $record->save();
        }

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

        ReferenceSequence::advance(Auth::user()->office, $reference);

        // Store each uploaded file ENCRYPTED at rest on the private disk.
        $storedPdfs = collect();

        foreach ($this->attachments as $file) {
            $stored = Attachment::storeEncrypted($record, $transaction, $file, Auth::user()->name);

            if ($stored->is_pdf) {
                $storedPdfs->push($stored);
            }
        }

        // Confidential viewers: tag the chosen personnel so they are cleared
        // to see the locked details and files. (Ignored when not confidential.)
        $taggedCount = 0;

        if ($this->isConfidential && ! empty($this->viewers)) {
            $viewerUsers = User::whereIn('id', collect($this->viewers)->map(fn ($id) => (int) $id))->get();

            foreach ($viewerUsers as $viewer) {
                \App\Models\RecordTagging::firstOrCreate(
                    ['record_id' => $record->id, 'user_id' => $viewer->id],
                    ['office' => $viewer->office, 'tagged_by' => Auth::user()->name]
                );
                $taggedCount++;
            }
        }

        // Signatories: each is asked to sign every PDF attached to the new record.
        $signerCount = 0;

        if (! empty($this->signers) && $storedPdfs->isNotEmpty()) {
            $requester = app(SignatureRequester::class);
            $signerUsers = User::whereIn('id', collect($this->signers)->map(fn ($id) => (int) $id))->get();

            foreach ($storedPdfs as $pdf) {
                foreach ($signerUsers as $signer) {
                    $requester->request($pdf, $signer, Auth::user(), 'outgoing creation');
                }
            }

            $signerCount = $signerUsers->count();
        }

        $fileCount = count($this->attachments);
        $message = ($this->isConfidential ? 'Confidential record created.' : 'Record created successfully.')
            . ($fileCount > 0 ? " {$fileCount} " . \Illuminate\Support\Str::plural('file', $fileCount) . ' attached.' : '')
            . ($taggedCount > 0 ? " {$taggedCount} " . \Illuminate\Support\Str::plural('viewer', $taggedCount) . ' authorised.' : '')
            . ($signerCount > 0 ? " Signature requested from {$signerCount} " . \Illuminate\Support\Str::plural('person', $signerCount) . '.' : '');

        session()->flash('message', $message);
        $this->reset(['office', 'subject', 'remarks', 'status', 'attachments', 'isConfidential', 'viewers', 'confidentialToken', 'signers']);

        $this->dispatch('recordAdded');
        $this->dispatch('close-send-modal');
    }

    /**
     * Signatories only apply to PDFs; drop the selection once none is attached.
     */
    private function forgetSignersWithoutPdf(): void
    {
        if (! $this->hasPdfAttachment()) {
            $this->signers = [];
        }
    }

    private function hasPdfAttachment(): bool
    {
        return collect($this->attachments)->contains(
            fn ($file) => is_object($file) && method_exists($file, 'getMimeType') && $file->getMimeType() === 'application/pdf'
        );
    }

    public function render()
    {
        $viewerPersonnel = ($this->isConfidential && filled($this->viewerOffice))
            ? User::where('office', $this->viewerOffice)->orderBy('name')->get()
            : collect();

        $hasPdfAttachment = $this->hasPdfAttachment();

        $signerPersonnel = ($hasPdfAttachment && filled($this->signerOffice))
            ? User::where('office', $this->signerOffice)->orderBy('name')->get()
            : collect();

        return view('livewire.create-outgoing', [
            'viewerPersonnel' => $viewerPersonnel,
            'hasPdfAttachment' => $hasPdfAttachment,
            'signerPersonnel' => $signerPersonnel,
        ]);
    }
}
