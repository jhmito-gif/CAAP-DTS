<?php

namespace App\Livewire;

use App\Models\ReferenceSequence;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Mark as Received" dialog on the record pages. The receiving office records
 * its own reference ID for the movement -- suggested from its reference
 * sequence, editable to the office's own format -- and the RAS prints every
 * office's number stacked in the Reference Number box.
 */
class ReceiveTransaction extends Component
{
    public ?int $transactionId = null;

    public string $receivedReference = '';

    #[On('receive-transaction')]
    public function open(int $transactionId): void
    {
        $transaction = Transaction::with('record')->find($transactionId);

        if (! $this->receivable($transaction)) {
            return;
        }

        $this->resetValidation();

        $this->transactionId = $transaction->id;
        $this->receivedReference = $this->suggestReference($transaction);

        $this->dispatch('open-receive-transaction-modal');
    }

    public function receive(): void
    {
        $transaction = Transaction::with('record')->find($this->transactionId);

        if (! $this->receivable($transaction)) {
            $this->dispatch('close-receive-transaction-modal');
            return;
        }

        $this->receivedReference = trim($this->receivedReference);

        $this->validate([
            'receivedReference' => [
                'required',
                'string',
                'max:255',
                // An office never gives two movements, or two records, the same number.
                Rule::unique('transactions', 'received_reference')
                    ->where(fn ($query) => $query->where('destination', $transaction->destination)),
                Rule::unique('records', 'reference')->ignore($transaction->record_id),
            ],
        ], [
            'receivedReference.unique' => 'This reference ID is already in use.',
        ], [
            'receivedReference' => 'reference ID',
        ]);

        $transaction->update([
            'recieved_by' => Auth::user()->name,
            'date_recieved' => Carbon::now(),
            'received_reference' => $this->receivedReference,
        ]);

        ReferenceSequence::advance($transaction->destination, $this->receivedReference);

        $this->banner("Received as {$this->receivedReference}.");
        $this->dispatch('close-receive-transaction-modal');
        $this->dispatch('record-updated');

        $this->reset(['transactionId', 'receivedReference']);
    }

    /**
     * The owning office receiving the incoming entry it logged already has a
     * number for the document (the record's reference); everyone else gets
     * their office's next number.
     */
    private function suggestReference(Transaction $transaction): string
    {
        $record = $transaction->record;

        if ($record->isIncoming()
            && $transaction->destination === $record->owner
            && $record->firstTransaction()?->is($transaction)) {
            return (string) $record->reference;
        }

        return ReferenceSequence::nextReferenceFor($transaction->destination);
    }

    private function receivable(?Transaction $transaction): bool
    {
        if (! $transaction || ! $transaction->record) {
            $this->banner('That routing entry no longer exists.', 'danger');
            return false;
        }

        if ($transaction->date_recieved !== null) {
            $this->banner('This movement has already been received.', 'danger');
            return false;
        }

        if (! $transaction->record->isAccessibleBy(Auth::user())) {
            $this->banner('You do not have access to this record.', 'danger');
            return false;
        }

        return true;
    }

    private function banner(string $message, string $style = 'success'): void
    {
        $this->dispatch('banner-message', style: $style, message: $message);
    }

    public function render()
    {
        return view('livewire.receive-transaction', [
            'transaction' => $this->transactionId
                ? Transaction::with('record')->find($this->transactionId)
                : null,
        ]);
    }
}
