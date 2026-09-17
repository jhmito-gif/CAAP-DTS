<?php

namespace App\Livewire;

use App\Models\InternalRouting;
use App\Models\Record;
use App\Models\SignatureRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\DocumentRoutedInternally;
use App\Support\DocumentHandling;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Internal routing panel on the record page: who inside an office passed the
 * document to whom, and when each person accepted it. The office-to-office
 * chain stays as it is; this traces the document inside each office.
 *
 * Only the person currently holding it (or an admin) hands it on; anyone in the
 * office may start the chain. The trail is readable by everyone who can open
 * the record.
 */
class InternalTrail extends Component
{
    #[Locked]
    public int $recordId;

    public string $toUserId = '';

    public string $action = '';

    public string $remarks = '';

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
    }

    #[On('record-updated')]
    public function refreshTrail(): void
    {
        //
    }

    public function forward(): void
    {
        $record = $this->record();
        $user = Auth::user();

        if (! $record || ! $this->officeHandlesRecord($record)) {
            $this->banner('This document has not been routed to your office.', 'danger');

            return;
        }

        if ($blocker = DocumentHandling::passBlocker($record, $user)) {
            $this->banner($blocker, 'danger');

            return;
        }

        $this->validate([
            'toUserId' => [
                'required',
                'integer',
                Rule::notIn([(string) $user->id]),
                Rule::exists('users', 'id')->where('office', $user->office),
            ],
            'action' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:2000',
        ], [
            'toUserId.required' => 'Choose who receives the document.',
            'toUserId.not_in' => 'Choose someone other than yourself.',
            'toUserId.exists' => 'Choose someone from your office.',
        ], [
            'toUserId' => 'recipient',
        ]);

        $recipient = User::find((int) $this->toUserId);

        $routing = InternalRouting::create([
            'record_id' => $record->id,
            'office' => $user->office,
            'from_user_id' => $user->id,
            'from_name' => $user->name,
            'to_user_id' => $recipient->id,
            'to_name' => $recipient->name,
            'action' => filled($this->action) ? trim($this->action) : null,
            'remarks' => filled($this->remarks) ? trim($this->remarks) : null,
        ]);

        $recipient->notify(new DocumentRoutedInternally($routing));

        $this->reset(['toUserId', 'action', 'remarks']);

        $this->banner("Passed to {$recipient->name}.");
        $this->dispatch('close-internal-forward-modal');
        $this->dispatch('record-updated');
    }

    public function acknowledge(int $routingId)
    {
        $user = Auth::user();

        $routing = InternalRouting::where('record_id', $this->recordId)->whereKey($routingId)->first();

        if (! $routing || ! $routing->isPending()) {
            return null;
        }

        if ((int) $routing->to_user_id !== (int) $user->id && ! $user->isAdmin()) {
            $this->banner('Only the person it was passed to can accept it.', 'danger');

            return null;
        }

        $routing->update(['received_at' => now()]);

        // Handed over to sign: take them straight to the signing page.
        if ($signatureRequest = $this->pendingSignatureFor($user)) {
            return $this->redirectRoute('esign.sign', $signatureRequest);
        }

        $this->banner('Marked as accepted.');
        $this->dispatch('record-updated');

        return null;
    }

    /** A document on this record still waiting for this person's signature. */
    private function pendingSignatureFor(?User $user): ?SignatureRequest
    {
        if (! $user) {
            return null;
        }

        return SignatureRequest::where('record_id', $this->recordId)
            ->where('signer_id', $user->id)
            ->whereNull('signed_at')
            ->orderBy('id')
            ->first();
    }

    private function record(): ?Record
    {
        $record = Record::find($this->recordId);

        return $record?->isAccessibleBy(Auth::user()) ? $record : null;
    }

    /** The office is part of this document's routing, so it can hold it. */
    private function officeHandlesRecord(Record $record): bool
    {
        $office = Auth::user()->office;

        if (blank($office)) {
            return false;
        }

        if (in_array($office, [$record->owner, $record->origin], true)) {
            return true;
        }

        return Transaction::where('record_id', $record->id)
            ->where(fn ($query) => $query->where('destination', $office)->orWhere('office', $office))
            ->exists();
    }

    /**
     * The reason this person cannot pass it on right now, or null when they can.
     * Null is also returned when the office has nothing to do with the record,
     * in which case the panel shows no controls at all.
     */
    private function forwardBlocker(): ?string
    {
        $record = $this->record();

        if (! $record || ! $this->officeHandlesRecord($record)) {
            return null;
        }

        return DocumentHandling::passBlocker($record, Auth::user());
    }

    private function canForward(): bool
    {
        $record = $this->record();

        return $record !== null
            && $this->officeHandlesRecord($record)
            && DocumentHandling::passBlocker($record, Auth::user()) === null;
    }

    private function banner(string $message, string $style = 'success'): void
    {
        $this->dispatch('banner-message', style: $style, message: $message);
    }

    public function render()
    {
        $record = $this->record();
        $user = Auth::user();

        $entries = $record
            ? InternalRouting::where('record_id', $record->id)->orderByDesc('id')->get()
            : collect();

        $holder = $record ? InternalRouting::holderFor($record->id, $user->office) : null;

        $canForward = $this->canForward();

        return view('livewire.internal-trail', [
            'record' => $record,
            'entries' => $entries,
            'holder' => $holder,
            'canForward' => $canForward,
            'forwardBlocker' => $canForward ? null : $this->forwardBlocker(),
            // Accepting takes them to the signing page when one is waiting.
            'awaitingSignature' => $record && $this->pendingSignatureFor($user) !== null,
            'people' => $canForward
                ? User::where('office', $user->office)->whereKeyNot($user->id)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
