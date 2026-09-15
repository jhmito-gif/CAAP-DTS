<?php

namespace App\Livewire;

use App\Models\EsignLog;
use App\Models\SigningPin;
use App\Models\UserSignature;
use App\Support\EsignLogger;
use App\Support\SignatureImage;
use App\Support\SigningSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Profile section where a user draws or uploads the signature they sign with
 * and sets the signing PIN that confirms each signature.
 */
class SignatureSettings extends Component
{
    use WithFileUploads;

    /** Wrong current-password attempts allowed when setting a PIN. */
    private const PIN_MAX_ATTEMPTS = 5;

    public $upload;

    public string $currentPassword = '';

    public string $newPin = '';

    public string $newPin_confirmation = '';

    public function saveDrawn(string $dataUri): void
    {
        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUri, $matches)) {
            EsignLogger::log('profile.signature_rejected', EsignLog::FAILURE, [], ['method' => 'drawn', 'reason' => 'unreadable drawing']);

            $this->addError('signature', 'The drawing could not be read. Please try again.');

            return;
        }

        $this->store(base64_decode($matches[1]), 'drawn');
    }

    public function saveUpload(): void
    {
        $this->validate(
            ['upload' => 'required|image|mimes:png,jpg,jpeg|max:2048'],
            [],
            ['upload' => 'signature image']
        );

        $this->store($this->upload->get(), 'uploaded');
        $this->reset('upload');
    }

    public function remove(): void
    {
        if (Auth::user()->signature()->delete()) {
            EsignLogger::log('profile.signature_removed', EsignLog::SUCCESS);
        }

        $this->dispatch('banner-message', style: 'success', message: 'Your signature was removed.');
    }

    public function savePin(SigningSession $signingSession): void
    {
        $user = Auth::user();
        $key = 'esign-pin:' . $user->id;

        try {
            $this->validate([
                'currentPassword' => 'required|string',
                'newPin' => [
                    'required',
                    'digits_between:' . SigningPin::MIN_LENGTH . ',' . SigningPin::MAX_LENGTH,
                    'confirmed',
                    function (string $attribute, mixed $value, \Closure $fail) {
                        if (SigningPin::isGuessable((string) $value)) {
                            $fail('Choose a PIN that is not a repeated or sequential number.');
                        }
                    },
                ],
            ], [], [
                'currentPassword' => 'current password',
                'newPin' => 'signing PIN',
            ]);

            if (RateLimiter::tooManyAttempts($key, self::PIN_MAX_ATTEMPTS)) {
                $this->addError('currentPassword', 'Too many attempts. Try again in ' . max(1, (int) ceil(RateLimiter::availableIn($key) / 60)) . ' minute(s).');

                return;
            }

            if (! Hash::check($this->currentPassword, $user->password)) {
                RateLimiter::hit($key, 600);

                EsignLogger::log('profile.pin_rejected', EsignLog::FAILURE, [], ['reason' => 'wrong password']);

                $this->addError('currentPassword', 'The password is incorrect.');

                return;
            }

            RateLimiter::clear($key);

            $saved = SigningPin::updateOrCreate(['user_id' => $user->id], ['pin' => $this->newPin]);

            // A new PIN also asks for the authenticator code again on the next signature.
            $signingSession->forget();

            EsignLogger::log('profile.pin_set', EsignLog::SUCCESS, [], ['replaced' => ! $saved->wasRecentlyCreated]);

            $this->resetErrorBag();
            $this->dispatch('banner-message', style: 'success', message: 'Your signing PIN was saved.');
        } finally {
            // Never keep secrets in the component state.
            $this->reset(['currentPassword', 'newPin', 'newPin_confirmation']);
        }
    }

    private function store(string $bytes, string $method): void
    {
        $png = SignatureImage::normalize($bytes);

        if ($png === null) {
            EsignLogger::log('profile.signature_rejected', EsignLog::FAILURE, [], ['method' => $method, 'reason' => 'no usable signature in image']);

            $this->addError('signature', 'No signature was found in that image. Draw or upload a clear signature.');

            return;
        }

        $saved = UserSignature::updateOrCreate(
            ['user_id' => Auth::id()],
            ['image' => base64_encode($png)]
        );

        EsignLogger::log('profile.signature_saved', EsignLog::SUCCESS, [], [
            'method' => $method,
            'replaced' => ! $saved->wasRecentlyCreated,
            'sha256' => hash('sha256', $png),
        ]);

        $this->resetErrorBag();
        $this->dispatch('banner-message', style: 'success', message: 'Your signature was saved.');
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.signature-settings', [
            'signature' => $user->signature()->first(),
            'signingPin' => $user->signingPin()->first(),
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
        ]);
    }
}
