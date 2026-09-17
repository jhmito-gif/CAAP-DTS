<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\EsignLog;
use App\Models\Signature;
use App\Models\SignatureRequest;
use App\Models\SigningDevice;
use App\Models\User;
use App\Notifications\DocumentSigned;
use App\Notifications\SigningSessionOpened;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Applies a signatory's e-signature to the current version of a document:
 * re-verifies the signer (signing PIN every time, authenticator code once per
 * session), checks the stored file has not been altered since it was uploaded
 * or last signed, stamps the signature server-side, stores the new version
 * encrypted and writes the audit row. Every attempt, successful or not, goes
 * to the e-sign log.
 */
class DocumentSigner
{
    /** Failed verification attempts allowed before the signer is locked out. */
    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_SECONDS = 600;

    public function __construct(
        protected PdfSignatureStamper $stamper,
        protected TwoFactorAuthenticationProvider $twoFactor,
        protected SigningSession $signingSession,
    ) {
    }

    /**
     * Why this user cannot sign this request right now, or null when they can.
     */
    public function blocker(SignatureRequest $request, User $user): ?string
    {
        $attachment = $request->attachment;

        return match (true) {
            (int) $request->signer_id !== (int) $user->id => 'This signature request is not assigned to you.',
            ! $request->isPending() => 'You have already signed this document.',
            ! $attachment || ! $attachment->is_pdf => 'Only PDF documents can be signed.',
            $attachment->isSigningComplete() => 'This document is already fully signed.',
            ! $request->record?->isAccessibleBy($user) => 'You no longer have access to this record.',
            ! $user->hasEnabledTwoFactorAuthentication() => 'Turn on two-factor authentication in your profile before signing.',
            ! $user->signature => 'Save your signature in your profile before signing.',
            ! $user->signingPin => 'Set a signing PIN in your profile before signing.',
            default => null,
        };
    }

    /**
     * Whether the next signature needs the authenticator code (not yet
     * confirmed in this session).
     */
    public function requiresCode(User $user): bool
    {
        return ! $this->signingSession->isConfirmed($user);
    }

    /** Why this signer cannot sign anything at all right now, or null. */
    public function signingBlocker(User $user): ?string
    {
        return match (true) {
            ! $user->hasEnabledTwoFactorAuthentication() => 'Turn on two-factor authentication in your profile before signing.',
            ! $user->signature => 'Save your signature in your profile before signing.',
            ! $user->signingPin => 'Set a signing PIN in your profile before signing.',
            default => null,
        };
    }

    /**
     * Open a signing session: authorise once, then sign each document with a
     * single click until the session closes. On a remembered device the
     * authenticator code is not asked for -- the device stands in for it.
     *
     * @return string|null  a new remembered-device token, when one was issued
     *
     * @throws ValidationException when verification fails
     */
    public function openSession(User $user, string $pin, ?string $code, bool $remember, ?string $deviceToken, ?string $ip, ?string $userAgent): ?string
    {
        $subjects = ['user' => $user];

        if ($reason = $this->signingBlocker($user)) {
            EsignLogger::log('session.blocked', EsignLog::FAILURE, $subjects, ['reason' => $reason]);

            throw ValidationException::withMessages(['pin' => $reason]);
        }

        $device = SigningDevice::find($user, $deviceToken);

        $this->verifySigner($user, $pin, $code, $subjects, $device === null);

        $this->signingSession->open($user);
        $this->signingSession->confirm($user);

        $device?->update(['last_used_at' => now(), 'ip_address' => $ip]);

        $token = null;

        if ($remember && ! $device) {
            $token = SigningDevice::remember($user, $userAgent, $ip);

            EsignLogger::log('session.device_remembered', EsignLog::SUCCESS, $subjects, ['days' => SigningDevice::DAYS]);
        }

        EsignLogger::log('session.opened', EsignLog::SUCCESS, $subjects, [
            'remembered_device' => $device !== null,
            'documents_allowed' => SigningSession::MAX_DOCUMENTS,
        ]);

        $user->notify(new SigningSessionOpened($userAgent, $ip, $device !== null || $token !== null));

        return $token;
    }

    public function closeSession(User $user): void
    {
        if ($this->signingSession->isOpen($user)) {
            EsignLogger::log('session.closed', EsignLog::INFO, ['user' => $user]);
        }

        $this->signingSession->close();
    }

    /**
     * @param  array{page: int, x: float, y: float, width: float, height: float}  $placement  PDF points, origin bottom-left
     * @param  string|null  $code  authenticator code; only needed when requiresCode() is true
     *
     * @throws ValidationException when verification fails or the document cannot be signed
     */
    public function sign(SignatureRequest $request, User $user, array $placement, ?string $pin, ?string $code, ?string $ip, ?string $userAgent): Signature
    {
        $subjects = ['user' => $user, 'request' => $request];

        // One box or several: a signatory may have to sign more than one page.
        $boxes = array_is_list($placement) ? array_values($placement) : [$placement];
        $first = $boxes[0] ?? null;

        if (! $first) {
            throw ValidationException::withMessages(['signature' => 'Place your signature on the document first.']);
        }

        if ($blocker = $this->blocker($request, $user)) {
            EsignLogger::log('signature.blocked', EsignLog::FAILURE, $subjects, ['reason' => $blocker]);

            throw ValidationException::withMessages(['signature' => $blocker]);
        }

        // An open signing session stands in for the PIN and the code; each
        // signature is still recorded, counted and audited on its own.
        if ($this->signingSession->isOpen($user)) {
            $twoFactor = 'signing session';
            $this->signingSession->recordSignature($user);
        } else {
            $twoFactor = $this->verifySigner($user, (string) $pin, $code, $subjects);
        }

        // Set when the transaction refuses to sign, and logged after it rolls back
        // (a log row written inside the transaction would be rolled back too).
        $failure = null;

        try {
            $signature = DB::transaction(function () use ($request, $user, $boxes, $first, $ip, $userAgent, &$failure) {
                // Lock the document so concurrent signers each sign the latest version.
                $attachment = Attachment::whereKey($request->attachment_id)->lockForUpdate()->firstOrFail();
                $request = SignatureRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

                if (! $request->isPending() || $attachment->isSigningComplete()) {
                    $failure = ['signature.already_signed', []];

                    throw ValidationException::withMessages(['signature' => 'This document has already been signed.']);
                }

                $source = $attachment->contents();
                $sourceHash = hash('sha256', $source);
                $expectedHash = $attachment->signatures()->latest('id')->value('signed_sha256') ?? $attachment->sha256;

                // Tamper check: the stored file must match what was uploaded or last signed.
                if ($expectedHash !== null && ! hash_equals($expectedHash, $sourceHash)) {
                    report(new \RuntimeException("E-sign integrity check failed for attachment {$attachment->id}."));

                    $failure = ['signature.integrity_failed', ['expected_sha256' => $expectedHash, 'actual_sha256' => $sourceHash]];

                    throw ValidationException::withMessages([
                        'signature' => 'This document failed its integrity check and cannot be signed. Contact an administrator.',
                    ]);
                }

                $verificationCode = Signature::newVerificationCode();
                $signedAt = now();

                try {
                    $signed = $this->stamper->stamp($source, $user->signature->png(), $boxes, array_values(array_filter([
                        $user->name,
                        $user->service ?: $user->office,
                        'Signed ' . $signedAt->copy()->setTimezone('Asia/Manila')->format('j M Y g:i A'),
                        'Verify: ' . $verificationCode,
                    ])));
                } catch (PdfSigningException $exception) {
                    $failure = ['signature.stamp_failed', ['error' => $exception->getMessage(), 'placements' => $boxes]];

                    throw ValidationException::withMessages(['signature' => $exception->getMessage()]);
                }

                $path = "attachments/{$attachment->record_id}/signed/" . Str::random(40) . '.pdf';

                $signature = Signature::create([
                    'signature_request_id' => $request->id,
                    'attachment_id' => $attachment->id,
                    'user_id' => $user->id,
                    'path' => $path,
                    'size' => strlen($signed),
                    // The first box on its own columns, every box in the list.
                    'page' => (int) $first['page'],
                    'x' => (float) $first['x'],
                    'y' => (float) $first['y'],
                    'width' => (float) $first['width'],
                    'height' => (float) $first['height'],
                    'placements' => $boxes,
                    'source_sha256' => $sourceHash,
                    'signed_sha256' => hash('sha256', $signed),
                    'verification_code' => $verificationCode,
                    'signer_name' => $user->name,
                    'signer_office' => $user->office,
                    'ip_address' => $ip,
                    'user_agent' => Str::limit((string) $userAgent, 500, ''),
                    'signed_at' => $signedAt,
                ]);

                // Written inside the transaction: if storing fails, no audit row is kept.
                Storage::disk('local')->put($path, Crypt::encryptString($signed));

                $request->update(['signed_at' => $signedAt]);

                if (! $attachment->signatureRequests()->whereNull('signed_at')->exists()) {
                    $attachment->update(['signing_completed_at' => $signedAt]);
                }

                return $signature;
            });
        } catch (ValidationException $exception) {
            if ($failure !== null) {
                EsignLogger::log($failure[0], EsignLog::FAILURE, $subjects, $failure[1]);
            }

            throw $exception;
        }

        EsignLogger::log('signature.signed', EsignLog::SUCCESS, $subjects + ['signature' => $signature], [
            'verification_code' => $signature->verification_code,
            'page' => $signature->page,
            'pages_stamped' => collect($boxes)->pluck('page')->unique()->sort()->values()->all(),
            'source_sha256' => $signature->source_sha256,
            'signed_sha256' => $signature->signed_sha256,
            'completed' => (bool) $signature->attachment?->fresh()?->isSigningComplete(),
            'two_factor' => $twoFactor,
        ]);

        $requester = $request->requester;

        if ($requester && (int) $requester->id !== (int) $user->id) {
            $requester->notify(new DocumentSigned($signature->load('attachment.record')));
        }

        return $signature;
    }

    /**
     * Signing PIN every time; authenticator code unless already confirmed in
     * this session. Lockout after repeated failures. Recovery codes are
     * deliberately not accepted for signing.
     *
     * @param  array<string, mixed>  $subjects  for the e-sign log
     * @return string how two-factor was satisfied: 'code' or 'session'
     */
    protected function verifySigner(User $user, string $pin, ?string $code, array $subjects, bool $codeRequired = true): string
    {
        $key = 'esign:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->signingSession->forget();

            EsignLogger::log('signature.locked_out', EsignLog::FAILURE, $subjects, [
                'retry_in_seconds' => RateLimiter::availableIn($key),
            ]);

            throw ValidationException::withMessages([
                'pin' => 'Too many failed attempts. Try again in ' . max(1, (int) ceil(RateLimiter::availableIn($key) / 60)) . ' minute(s).',
            ]);
        }

        if (! $user->signingPin?->matches($pin)) {
            $this->failVerification($key, 'signature.pin_failed', $subjects, ['pin' => 'The signing PIN is incorrect.']);
        }

        // A remembered device has already proved the second factor.
        if (! $codeRequired) {
            RateLimiter::clear($key);

            return 'remembered device';
        }

        if ($this->signingSession->isConfirmed($user)) {
            RateLimiter::clear($key);

            return 'session';
        }

        $code = preg_replace('/\s+/', '', (string) $code);

        if ($code === '') {
            throw ValidationException::withMessages(['code' => 'Enter the code from your authenticator app.']);
        }

        if (! $this->twoFactor->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code)) {
            $this->failVerification($key, 'signature.code_failed', $subjects, ['code' => 'The authentication code is invalid.']);
        }

        RateLimiter::clear($key);
        $this->signingSession->confirm($user);

        return 'code';
    }

    /**
     * @param  array<string, mixed>  $subjects
     * @param  array<string, string>  $messages
     */
    private function failVerification(string $key, string $event, array $subjects, array $messages): never
    {
        RateLimiter::hit($key, self::LOCKOUT_SECONDS);

        $attempts = RateLimiter::attempts($key);

        // Reaching the lockout also ends this session's authenticator confirmation.
        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->signingSession->forget();
        }

        EsignLogger::log($event, EsignLog::FAILURE, $subjects, ['attempts' => $attempts]);

        throw ValidationException::withMessages($messages);
    }
}
