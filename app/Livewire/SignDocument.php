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
 * signature on the PDF (pdf.js, resources/js/esign.js) and confirms with their
 * signing PIN, plus their authenticator code the first time in a session. The
 * stamp is applied server-side by DocumentSigner; the browser only sends where
 * it goes.
 */
class SignDocument extends Component
{
    #[Locked]
    public int $signatureRequestId;

    // Placement picked in the viewer, in PDF points (origin bottom-left).
    public int $page = 0;

    public float $x = 0;

    public float $y = 0;

    public float $width = 0;

    public float $height = 0;

    public string $pin = '';

    public string $code = '';

    // A token-gated (confidential) record must be unlocked before the PDF loads.
    public bool $unlocked = false;

    public string $unlockToken = '';

    public function mount(int $signatureRequestId): void
    {
        $this->signatureRequestId = $signatureRequestId;
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
                'page' => 'required|integer|min:1',
                'x' => 'required|numeric',
                'y' => 'required|numeric',
                'width' => 'required|numeric|min:40',
                'height' => 'required|numeric|min:24',
                'pin' => 'required|string',
                'code' => 'nullable|string',
            ], [
                'page.min' => 'Place your signature on the document first.',
                'width.min' => 'Place your signature on the document first.',
                'height.min' => 'Place your signature on the document first.',
            ], [
                'pin' => 'signing PIN',
                'code' => 'authentication code',
            ]);

            $signature = $signer->sign(
                $signatureRequest,
                Auth::user(),
                ['page' => $this->page, 'x' => $this->x, 'y' => $this->y, 'width' => $this->width, 'height' => $this->height],
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

        session()->flash('flash.banner', "Document signed. Verification code: {$signature->verification_code}");
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
            'codeRequired' => $signer->requiresCode($user),
            'tokenRequired' => $tokenRequired,
            'documentUrl' => $documentUrl,
            'signaturePreview' => $user->signature?->dataUri(),
        ]);
    }
}
