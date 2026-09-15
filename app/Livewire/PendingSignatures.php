<?php

namespace App\Livewire;

use App\Models\SignatureRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Notice on a record page listing the documents the current user has been
 * asked to sign.
 */
class PendingSignatures extends Component
{
    public $recordId;

    #[On('record-updated')]
    public function refreshRequests(): void
    {
        //
    }

    public function render()
    {
        return view('livewire.pending-signatures', [
            'signatureRequests' => SignatureRequest::with('attachment')
                ->where('record_id', $this->recordId)
                ->where('signer_id', Auth::id())
                ->whereNull('signed_at')
                ->get(),
        ]);
    }
}
