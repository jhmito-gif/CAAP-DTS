<?php

namespace App\Livewire\Concerns;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * "Mark as Received" on the record pages: one click that stamps who received
 * the movement and when. The office's reference ID is a separate action
 * (AssignReference), so a number can be given before the document arrives.
 */
trait ReceivesTransactions
{
    public function markAsReceived(int $transactionId): void
    {
        $transaction = Transaction::with('record')->find($transactionId);

        if (! $transaction || ! $transaction->record) {
            $this->banner('That routing entry no longer exists.', 'danger');

            return;
        }

        if ($transaction->date_recieved !== null) {
            $this->banner('This movement has already been received.', 'danger');

            return;
        }

        if (! $transaction->record->isAccessibleBy(Auth::user())) {
            $this->banner('You do not have access to this record.', 'danger');

            return;
        }

        $transaction->update([
            'recieved_by' => Auth::user()->name,
            'date_recieved' => Carbon::now(),
        ]);

        $this->banner('Marked as received.');
        $this->dispatch('record-updated');
    }

    private function banner(string $message, string $style = 'success'): void
    {
        $this->dispatch('banner-message', style: $style, message: $message);
    }
}
