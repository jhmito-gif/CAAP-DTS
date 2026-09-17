<?php

namespace App\Livewire;

use App\Models\EsignLog;
use App\Models\SignatureRequest;
use App\Support\DocumentSigner;
use App\Support\EsignLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Signing screen for one signature request. The signer places their saved
 * signature on the PDF (pdf.js, resources/js/esign.js) -- one spot per page,
 * on as many pages as the document needs -- and confirms with their signing
 * PIN, plus their authenticator code the first time in a session. The stamp is
 * applied server-side by DocumentSigner; the browser only sends where it goes.
 */
class SignDocument extends Component
{
    #[Locked]
    public int $signatureRequestId;

    /**
     * Where the signature goes, in PDF points (origin bottom-left). One entry
     * per page; pre-filled from what the sending office marked, and adjustable.
     *
     * @var array<int, array{page: int, x: float, y: float, width: float, height: float}>
     */
    public array $placements = [];

    public string $pin = '';

    public string $code = '';

    // A token-gated (confidential) record must be unlocked before the PDF loads.
    public bool $unlocked = false;

    public string $unlockToken = '';

    public function mount(int $signatureRequestId): void
    {
        $this->signatureRequestId = $signatureRequestId;

        // Start from where the sending office marked it, if they did.
        $this->placements = SignatureRequest::find($signatureRequestId)?->placements() ?? [];
    }

    public function unlock(): void
    {
        $signatureRequest = $this->signatureRequest();

        if ($signatureRequest->record?->checkConfidentialToken($this->unlockToken)) {
            $this->unlocked = true;
            $this->unlockToken = '';

            EsignLogger::log('record.unlocked', EsignLog::SUCCESS, ['request' => $signatureRequest]);

            return;
        }

        $this->unlockToken = '';

        EsignLogger::log('record.unlock_failed', EsignLog::FAILURE, ['request' => $signatureRequest]);

        $this->addError('unlockToken', 'Incorrect access token.');
    }

    public function sign(DocumentSigner $signer)
    {
        $signatureRequest = $this->signatureRequest();

        if ($signatureRequest->record?->requiresToken() && ! $this->unlocked) {
            $this->addError('signature', 'Unlock the record with its access token first.');

            return;
        }

        try {
            $this->validate([
                'placements' => 'required|array|min:1',
                'placements.*.page' => 'required|integer|min:1',
                'placements.*.x' => 'required|numeric',
                'placements.*.y' => 'required|numeric',
                'placements.*.width' => 'required|numeric|min:40',
                'placements.*.height' => 'required|numeric|min:24',
                'pin' => 'required|string',
                'code' => 'nullable|string',
            ], [
                'placements.required' => 'Place your signature on the document first.',
                'placements.*.width.min' => 'Place your signature on the document first.',
                'placements.*.height.min' => 'Place your signature on the document first.',
            ], [
                'pin' => 'signing PIN',
                'code' => 'authentication code',
            ]);

            $signature = $signer->sign(
                $signatureRequest,
                Auth::user(),
                array_values($this->placements),
                $this->pin,
                $this->code !== '' ? $this->code : null,
                request()->ip(),
                request()->userAgent(),
            );
        } finally {
            // Never keep secrets in the component state.
            $this->pin = '';
            $this->code = '';
        }

        $pages = collect($this->placements)->pluck('page')->unique()->count();

        session()->flash('flash.banner', ($pages > 1 ? "Document signed on {$pages} pages. " : 'Document signed. ')
            . "Verification code: {$signature->verification_code}");
        session()->flash('flash.bannerStyle', 'success');

        return $this->redirectRoute('show-transactions', $signatureRequest->record_id);
    }

    private function signatureRequest(): SignatureRequest
    {
        $signatureRequest = SignatureRequest::with(['attachment', 'record'])->findOrFail($this->signatureRequestId);

        abort_unless((int) $signatureRequest->signer_id === (int) Auth::id(), 403);

        return $signatureRequest;
    }

    public function render(DocumentSigner $signer)
    {
        $signatureRequest = $this->signatureRequest();
        $signatureRequest->load('attachment.signatureRequests.signer');

        $record = $signatureRequest->record;
        $user = Auth::user();
        $tokenRequired = (bool) $record?->requiresToken();
        $canView = $record?->isAccessibleBy($user) && $signatureRequest->attachment && (! $tokenRequired || $this->unlocked);

        $documentUrl = null;

        if ($canView) {
            $documentUrl = $tokenRequired
                ? URL::temporarySignedRoute('attachments.view', now()->addMinutes(10), ['attachment' => $signatureRequest->attachment_id])
                : route('attachments.view', $signatureRequest->attachment_id);
        }

        return view('livewire.sign-document', [
            'signatureRequest' => $signatureRequest,
            'attachment' => $signatureRequest->attachment,
            'record' => $record,
            'blocker' => $signer->blocker($signatureRequest, $user),
            'markedBy' => $signatureRequest->placements() ? $signatureRequest->placed_by : null,
            'markedPages' => $signatureRequest->markedPages(),
            'codeRequired' => $signer->requiresCode($user),
            'tokenRequired' => $tokenRequired,
            'documentUrl' => $documentUrl,
            'signaturePreview' => $user->signature?->dataUri(),
        ]);
    }
}
