<?php

namespace App\Livewire;

use App\Models\Record;
use App\Models\ReferenceSequence;
use App\Models\Transaction;
use App\Support\OfficeReference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Reference ID" on the record pages: the office records its own number for a
 * document, suggested from its sequence and editable to the office's own
 * format. Independent of receiving, so a number exists before the document
 * arrives or is signed. One number per office per document -- it is stored on
 * every movement addressed to that office.
 */
class AssignReference extends Component
{
    #[Locked]
    public ?int $recordId = null;

    public string $reference = '';

    /** True when the office already had a number for this document. */
    public bool $reused = false;

    #[On('assign-reference')]
    public function open(int $recordId): void
    {
        if (OfficeReference::centralised()) {
            $this->banner('Office reference IDs are switched off: every record keeps its one central reference.', 'danger');

            return;
        }

        $record = Record::find($recordId);

        if (! $record || ! $record->isAccessibleBy(Auth::user())) {
            $this->banner('You do not have access to this record.', 'danger');

            return;
        }

        if (! $this->movements($record)->exists()) {
            $this->banner('This document has not been routed to your office.', 'danger');

            return;
        }

        $this->resetValidation();

        $this->recordId = $record->id;
        $this->reference = $this->suggestReference($record);

        $this->dispatch('open-assign-reference-modal');
    }

    public function save(): void
    {
        if (OfficeReference::centralised()) {
            $this->banner('Office reference IDs are switched off: every record keeps its one central reference.', 'danger');

            return;
        }

        $record = Record::find($this->recordId);

        if (! $record || ! $record->isAccessibleBy(Auth::user())) {
            $this->banner('You do not have access to this record.', 'danger');
            $this->dispatch('close-assign-reference-modal');

            return;
        }

        $office = (string) Auth::user()->office;
        $this->reference = trim($this->reference);

        $this->validate([
            'reference' => [
                'required',
                'string',
                'max:255',
                // The office never gives two different documents the same number.
                Rule::unique('transactions', 'received_reference')
                    ->where(fn ($query) => $query
                        ->where('destination', $office)
                        ->where('record_id', '!=', $record->id)),
                Rule::unique('records', 'reference')->ignore($record->id),
            ],
        ], [
            'reference.unique' => 'This reference ID is already in use.',
        ], [
            'reference' => 'reference ID',
        ]);

        // One number per office per document: every movement to this office carries it.
        $this->movements($record)->update(['received_reference' => $this->reference]);

        ReferenceSequence::advance($office, $this->reference);

        $this->banner("Reference ID saved as {$this->reference}.");
        $this->dispatch('close-assign-reference-modal');
        $this->dispatch('record-updated');

        $this->reset(['recordId', 'reference', 'reused']);
    }

    /**
     * The number this office already has for the document -- its own record
     * reference, or the one it recorded on an earlier movement -- otherwise the
     * next number in its sequence.
     */
    private function suggestReference(Record $record): string
    {
        $office = (string) Auth::user()->office;
        $existing = OfficeReference::existing($record, $office);

        $this->reused = $existing !== null;

        return $existing ?? ReferenceSequence::nextReferenceFor($office);
    }

    /** Movements of this record addressed to the current user's office. */
    private function movements(Record $record)
    {
        return Transaction::where('record_id', $record->id)
            ->where('destination', (string) Auth::user()->office);
    }

    private function banner(string $message, string $style = 'success'): void
    {
        $this->dispatch('banner-message', style: $style, message: $message);
    }

    public function render()
    {
        return view('livewire.assign-reference', [
            'record' => $this->recordId ? Record::find($this->recordId) : null,
        ]);
    }
}
