<?php

namespace App\Livewire;

use App\Models\EsignLog;
use App\Models\Signature;
use App\Support\EsignLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Verification page: look a signature up by its verification code, or check
 * whether a PDF is byte-for-byte a version produced by signing.
 */
class VerifySignature extends Component
{
    use WithFileUploads;

    public string $code = '';

    public $file;

    public ?int $signatureId = null;

    /** 'match' or 'none' once a file has been checked. */
    public ?string $fileResult = null;

    public ?int $fileSignatureId = null;

    public function lookup(): void
    {
        $this->validate(['code' => 'required|string|max:20'], [], ['code' => 'verification code']);

        if (! $this->throttle('code')) {
            return;
        }

        $code = strtoupper(trim($this->code));
        $signature = Signature::where('verification_code', $code)->first();

        $this->signatureId = $signature?->id;

        EsignLogger::log(
            'verify.code_lookup',
            $signature ? EsignLog::SUCCESS : EsignLog::FAILURE,
            $signature ? ['signature' => $signature] : [],
            ['code' => $code, 'found' => (bool) $signature]
        );

        if (! $signature) {
            $this->addError('code', 'No signature has this verification code.');
        }
    }

    public function checkFile(): void
    {
        $this->validate(['file' => 'required|file|mimes:pdf|max:20480'], [], ['file' => 'PDF']);

        if (! $this->throttle('file')) {
            return;
        }

        $hash = hash('sha256', $this->file->get());
        $match = Signature::where('signed_sha256', $hash)->latest('id')->first();

        $this->fileSignatureId = $match?->id;
        $this->fileResult = $match ? 'match' : 'none';

        EsignLogger::log(
            'verify.file_check',
            $match ? EsignLog::SUCCESS : EsignLog::FAILURE,
            $match ? ['signature' => $match] : [],
            ['sha256' => $hash, 'file_name' => $this->file->getClientOriginalName(), 'matched' => (bool) $match]
        );

        $this->reset('file');
    }

    private function throttle(string $field): bool
    {
        $key = 'esign-verify:' . Auth::id();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            EsignLogger::log('verify.throttled', EsignLog::FAILURE, [], ['check' => $field]);

            $this->addError($field, 'Too many checks. Please wait a minute.');

            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    public function render()
    {
        $user = Auth::user();

        $describe = function (?int $id) use ($user): ?array {
            $signature = $id ? Signature::with('attachment.record')->find($id) : null;

            if (! $signature) {
                return null;
            }

            $attachment = $signature->attachment;
            $record = $attachment?->record;

            return [
                'signature' => $signature,
                'canSeeDocument' => $record && $record->isAccessibleBy($user),
                'isLatest' => (bool) $attachment?->signatures()->latest('id')->first()?->is($signature),
                'complete' => (bool) $attachment?->isSigningComplete(),
            ];
        };

        return view('livewire.verify-signature', [
            'found' => $describe($this->signatureId),
            'fileMatch' => $describe($this->fileSignatureId),
        ]);
    }
}
