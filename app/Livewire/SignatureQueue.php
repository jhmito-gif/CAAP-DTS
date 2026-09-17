<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\SignatureRequest;
use App\Support\DocumentSigner;
use App\Support\PdfSigningException;
use App\Support\SignatureSpotFinder;
use App\Support\SigningSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Everything waiting for this person's signature, with the signature spot
 * worked out for each document. Open a signing session once and each document
 * is then signed with a single click.
 */
class SignatureQueue extends Component
{
    use WithPagination;

    /** Cookie holding the remembered-device token. */
    public const DEVICE_COOKIE = 'esign_device';

    public string $pin = '';

    public string $code = '';

    public bool $remember = false;

    public function openSession(DocumentSigner $signer): void
    {
        $user = Auth::user();

        $this->validate([
            'pin' => 'required|string',
            'code' => 'nullable|string',
        ], [], ['pin' => 'signing PIN', 'code' => 'authentication code']);

        try {
            $token = $signer->openSession(
                $user,
                $this->pin,
                $this->code !== '' ? $this->code : null,
                $this->remember,
                request()->cookie(self::DEVICE_COOKIE),
                request()->ip(),
                request()->userAgent(),
            );
        } finally {
            $this->pin = '';
            $this->code = '';
        }

        if ($token) {
            Cookie::queue(cookie(
                self::DEVICE_COOKIE,
                $token,
                60 * 24 * \App\Models\SigningDevice::DAYS,
                null,
                null,
                request()->isSecure(),
                true,
                false,
                'lax'
            ));
        }

        $this->dispatch('close-signing-session-modal');
        $this->banner('Signing session open. Each document now takes one click.');
    }

    public function endSession(DocumentSigner $signer): void
    {
        $signer->closeSession(Auth::user());

        $this->banner('Signing session ended.');
    }

    /**
     * Sign one document where the finder placed it. Only possible while a
     * signing session is open; otherwise the signer places it themselves.
     */
    public function sign(int $requestId, DocumentSigner $signer, SigningSession $session): void
    {
        $user = Auth::user();

        if (! $session->isOpen($user)) {
            $this->banner('Open a signing session first.', 'danger');

            return;
        }

        $signatureRequest = SignatureRequest::with('attachment')
            ->whereKey($requestId)
            ->where('signer_id', $user->id)
            ->whereNull('signed_at')
            ->first();

        if (! $signatureRequest || ! $signatureRequest->attachment) {
            $this->banner('That document is no longer waiting for you.', 'danger');

            return;
        }

        // Where the sending office marked it -- every page they marked --
        // otherwise the one spot found in the document.
        $marked = $signatureRequest->placements();
        $spot = $marked ?: ($this->spotFor($signatureRequest->attachment) ? [$this->spotFor($signatureRequest->attachment)] : null);

        if (! $spot) {
            $this->banner('Where to sign this one could not be worked out — open it and place the signature yourself.', 'danger');

            return;
        }

        try {
            $signature = $signer->sign(
                $signatureRequest,
                $user,
                $spot,
                null,
                null,
                request()->ip(),
                request()->userAgent(),
            );
        } catch (ValidationException $exception) {
            $this->banner(collect($exception->errors())->flatten()->first() ?: 'This document could not be signed.', 'danger');

            return;
        }

        $this->banner('Signed ' . $signatureRequest->attachment->displayNameFor($user) . " — {$signature->verification_code}");
        $this->dispatch('record-updated');
    }

    /**
     * Where the signature belongs in this document. Cached against the file's
     * fingerprint, so each document is read once however often it is listed.
     *
     * @return array{page: int, x: float, y: float, width: float, height: float, reason: string, anchor: ?string}|null
     */
    private function spotFor(Attachment $attachment): ?array
    {
        $key = "esign:spot:{$attachment->id}:" . ($attachment->sha256 ?? 'none');

        try {
            return Cache::remember(
                $key,
                now()->addDay(),
                fn () => app(SignatureSpotFinder::class)->find($attachment->contents(), Auth::user()->name)
            );
        } catch (PdfSigningException|\Throwable) {
            return null;
        }
    }

    private function banner(string $message, string $style = 'success'): void
    {
        $this->dispatch('banner-message', style: $style, message: $message);
    }

    public function render(DocumentSigner $signer, SigningSession $session)
    {
        $user = Auth::user();

        $requests = SignatureRequest::with(['attachment', 'record'])
            ->where('signer_id', $user->id)
            ->whereNull('signed_at')
            ->orderBy('id')
            ->paginate(10);

        $documents = $requests->through(function (SignatureRequest $request) use ($user) {
            $attachment = $request->attachment;

            $marked = $request->placements();

            $spot = $marked
                ? $marked[0] + ['reason' => 'marked by the sender', 'anchor' => $request->placed_by]
                : ($attachment ? $this->spotFor($attachment) : null);

            // A confidential record keeps its subject and file name from anyone
            // not cleared for it, even while they are asked to sign.
            $masked = (bool) $request->record?->isMaskedFor($user);

            return (object) [
                'id' => $request->id,
                'reference' => $request->record?->reference,
                'subject' => $masked ? 'Confidential — hidden from you' : $request->record?->subject,
                'name' => $attachment?->displayNameFor($user),
                'size' => $attachment?->human_size,
                'url' => $attachment ? route('attachments.view', $attachment->id) : null,
                'record_url' => $request->record ? route('show-transactions', $request->record_id) : null,
                'sign_url' => route('esign.sign', $request->id),
                'spot' => $spot,
                'pages' => $marked
                    ? collect($marked)->pluck('page')->unique()->sort()->values()->all()
                    : ($spot ? [$spot['page']] : []),
                'locked' => (bool) $request->record?->requiresToken(),
            ];
        });

        return view('livewire.signature-queue', [
            'documents' => $documents,
            'sessionOpen' => $session->isOpen($user),
            'remaining' => $session->remaining($user),
            'expiresAt' => $session->expiresAt($user),
            'codeNeeded' => $signer->requiresCode($user)
                && \App\Models\SigningDevice::find($user, request()->cookie(self::DEVICE_COOKIE)) === null,
            'deviceRemembered' => \App\Models\SigningDevice::find($user, request()->cookie(self::DEVICE_COOKIE)) !== null,
            'blocker' => $signer->signingBlocker($user),
            'maxDocuments' => SigningSession::MAX_DOCUMENTS,
        ]);
    }
}
