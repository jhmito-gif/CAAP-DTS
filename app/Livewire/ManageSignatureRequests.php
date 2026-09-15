<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\EsignLog;
use App\Models\Office;
use App\Models\User;
use App\Support\EsignLogger;
use App\Support\SignatureRequester;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Signatures" dialog for a PDF on the record page: the owning office (or an
 * admin) assigns signatories and sees who has signed.
 */
class ManageSignatureRequests extends Component
{
    public ?int $attachmentId = null;

    public string $search = '';

    public string $office = '';

    public $officeOptions = [];

    public function mount(): void
    {
        $this->officeOptions = Office::pluck('name', 'name')->toArray();
    }

    #[On('manage-signatures')]
    public function open(int $attachmentId): void
    {
        $attachment = Attachment::with('record')->find($attachmentId);

        if (! $this->manageable($attachment)) {
            return;
        }

        $this->attachmentId = $attachment->id;
        $this->reset(['search', 'office']);

        $this->dispatch('open-signature-requests-modal');
    }

    public function requestSignature(int $userId): void
    {
        $attachment = Attachment::with('record')->find($this->attachmentId);

        if (! $this->manageable($attachment)) {
            return;
        }

        if ($attachment->isSigningComplete()) {
            $this->banner('This document is already fully signed.', 'danger');

            return;
        }

        $signer = User::find($userId);

        if (! $signer) {
            return;
        }

        if (! app(SignatureRequester::class)->request($attachment, $signer, Auth::user(), 'record page')) {
            return;
        }

        $this->banner("Signature requested from {$signer->name}.");
        $this->dispatch('tags-updated', recordId: $attachment->record_id);
        $this->dispatch('record-updated');
    }

    public function cancelRequest(int $requestId): void
    {
        $attachment = Attachment::with('record')->find($this->attachmentId);

        if (! $this->manageable($attachment)) {
            return;
        }

        $signatureRequest = $attachment->signatureRequests()->with('signer')->whereKey($requestId)->first();

        if (! $signatureRequest?->isPending()) {
            $this->banner('A completed signature cannot be removed.', 'danger');

            return;
        }

        // Logged before deleting, while the request can still be linked.
        EsignLogger::log('request.cancelled', EsignLog::SUCCESS, ['request' => $signatureRequest], [
            'signer_id' => $signatureRequest->signer_id,
            'signer_name' => $signatureRequest->signer?->name,
        ]);

        $signatureRequest->delete();

        // Everyone still assigned has signed: the document is now complete.
        if ($attachment->signatures()->exists() && ! $attachment->signatureRequests()->whereNull('signed_at')->exists()) {
            $attachment->update(['signing_completed_at' => now()]);
        }

        $this->dispatch('record-updated');
    }

    private function manageable(?Attachment $attachment): bool
    {
        if (! $attachment || ! $attachment->record || ! $attachment->is_pdf) {
            $this->banner('Only PDF files can be sent for signature.', 'danger');

            return false;
        }

        if (! $attachment->record->canManageAttachments(Auth::user())) {
            EsignLogger::log('request.denied', EsignLog::FAILURE, ['attachment' => $attachment]);

            $this->banner('Only the originating office or an admin can request signatures.', 'danger');

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
        $attachment = $this->attachmentId
            ? Attachment::with(['record', 'signatureRequests.signer'])->find($this->attachmentId)
            : null;

        $candidates = ($attachment && ($this->search !== '' || $this->office !== ''))
            ? User::query()
                ->when($this->office !== '', fn ($query) => $query->where('office', $this->office))
                ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->whereNotIn('id', $attachment->signatureRequests->pluck('signer_id'))
                ->orderBy('name')
                ->limit(20)
                ->get()
            : collect();

        return view('livewire.manage-signature-requests', [
            'attachment' => $attachment,
            'candidates' => $candidates,
        ]);
    }
}
