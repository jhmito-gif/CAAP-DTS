<?php

namespace App\Livewire;

use App\Models\EsignLog;
use App\Models\UserSignature;
use App\Support\EsignLogger;
use App\Support\SignatureImage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Profile section where a user draws or uploads the signature they sign with.
 */
class SignatureSettings extends Component
{
    use WithFileUploads;

    public $upload;

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
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
        ]);
    }
}
