<?php

use App\Livewire\SignatureQueue;
use App\Livewire\SignatureSettings;
use App\Models\Attachment;
use App\Models\EsignLog;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\Signature;
use App\Models\SignatureRequest;
use App\Models\SigningDevice;
use App\Models\SigningPin;
use App\Models\User;
use App\Models\UserSignature;
use App\Notifications\SigningSessionOpened;
use App\Support\DocumentSigner;
use App\Support\SignatureImage;
use App\Support\SignatureSpotFinder;
use App\Support\SigningSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
});

function sessionPin(): string
{
    return '482916';
}

function sessionSignaturePng(): string
{
    $image = imagecreatetruecolor(300, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagesetthickness($image, 4);
    imageline($image, 20, 70, 280, 30, imagecolorallocate($image, 10, 10, 10));

    ob_start();
    imagepng($image);

    return ob_get_clean();
}

/** A signer ready to sign: two-factor on, a saved signature and a PIN. */
function sessionSigner(string $name = 'Director General'): User
{
    $user = User::factory()->create(['name' => $name, 'office' => 'ODG']);

    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt(app(Google2FA::class)->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ])->save();

    UserSignature::create(['user_id' => $user->id, 'image' => base64_encode(SignatureImage::normalize(sessionSignaturePng()))]);
    SigningPin::create(['user_id' => $user->id, 'pin' => sessionPin()]);

    return $user->fresh();
}

function sessionCode(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));
}

function sessionDocument(User $signer, string $reference, string $body = '<p style="margin-top:500px">Approved by:</p>'): SignatureRequest
{
    $record = Record::create([
        'reference' => $reference,
        'subject' => 'Memorandum for signature',
        'created_by' => 'ITD Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    $pdf = Pdf::loadHTML("<h1>Memorandum</h1><p>For approval.</p>{$body}")->setPaper('a4')->output();

    $attachment = Attachment::storeEncrypted($record, null, UploadedFile::fake()->createWithContent('memo.pdf', $pdf), 'ITD Person');

    RecordTagging::create(['record_id' => $record->id, 'user_id' => $signer->id, 'office' => $signer->office, 'tagged_by' => 'ITD Person']);

    return SignatureRequest::create([
        'attachment_id' => $attachment->id,
        'record_id' => $record->id,
        'signer_id' => $signer->id,
    ]);
}

it('finds where to sign from the signature block', function () {
    $labelled = Pdf::loadHTML('<p>Body of the memorandum.</p><p style="margin-top:520px">Approved by:</p>')->setPaper('a4')->output();

    $spot = app(SignatureSpotFinder::class)->find($labelled, 'Director General');

    // A4 is 595pt wide; the stamp belongs on the right-hand side of it even
    // though the label it was found by sits at the left margin.
    expect($spot['page'])->toBe(1)
        ->and($spot['reason'])->toBe('signature label')
        ->and($spot['width'])->toBe(SignatureSpotFinder::WIDTH)
        ->and($spot['y'])->toBeGreaterThan(0)
        ->and($spot['x'])->toBeGreaterThan(595 / 2)
        ->and($spot['x'] + $spot['width'])->toBeLessThan(595.0);

    // The signer's printed name wins over a label.
    $named = Pdf::loadHTML('<p>Body.</p><p style="margin-top:520px">Approved by:</p><p>Director General</p>')->setPaper('a4')->output();

    expect(app(SignatureSpotFinder::class)->find($named, 'Director General')['reason'])->toBe('printed name');

    // Nothing to anchor on: the foot of the last page.
    $plain = Pdf::loadHTML('<p>Nothing to anchor on.</p>')->setPaper('a4')->output();

    expect(app(SignatureSpotFinder::class)->find($plain, 'Director General')['reason'])->toBe('fallback');
});

it('opens a signing session, then signs without a PIN or code', function () {
    $signer = sessionSigner();
    $first = sessionDocument($signer, 'ITD-2026-0101');
    $second = sessionDocument($signer, 'ITD-2026-0102');
    $service = app(DocumentSigner::class);
    $session = app(SigningSession::class);

    $token = $service->openSession($signer, sessionPin(), sessionCode($signer), false, null, '10.0.0.5', 'Test Browser');

    expect($token)->toBeNull()
        ->and($session->isOpen($signer))->toBeTrue()
        ->and($session->remaining($signer))->toBe(SigningSession::MAX_DOCUMENTS);

    Notification::assertSentTo($signer, SigningSessionOpened::class);

    $placement = ['page' => 1, 'x' => 380, 'y' => 60, 'width' => 180, 'height' => 80];

    $service->sign($first, $signer, $placement, null, null, null, null);
    $service->sign($second, $signer, $placement, null, null, null, null);

    expect(Signature::count())->toBe(2)
        ->and($session->remaining($signer))->toBe(SigningSession::MAX_DOCUMENTS - 2)
        ->and(EsignLog::where('event', 'signature.signed')->get()->pluck('context.two_factor')->unique()->values()->all())
            ->toBe(['signing session'])
        ->and(EsignLog::where('event', 'session.opened')->count())->toBe(1);
});

it('remembers a device so the next session needs only the PIN', function () {
    $signer = sessionSigner();
    $service = app(DocumentSigner::class);
    $session = app(SigningSession::class);

    $token = $service->openSession($signer, sessionPin(), sessionCode($signer), true, null, '10.0.0.5', 'Chrome on Windows');

    expect($token)->toBeString()
        ->and(SigningDevice::where('user_id', $signer->id)->count())->toBe(1)
        ->and(EsignLog::where('event', 'session.device_remembered')->exists())->toBeTrue();

    $service->closeSession($signer);
    expect($session->isOpen($signer))->toBeFalse();

    // No code this time: the device stands in for it.
    $service->openSession($signer, sessionPin(), null, false, $token, '10.0.0.5', 'Chrome on Windows');
    expect($session->isOpen($signer))->toBeTrue();

    // The PIN is still required.
    $service->closeSession($signer);

    expect(fn () => $service->openSession($signer, '000000', null, false, $token, null, null))
        ->toThrow(ValidationException::class);

    expect($session->isOpen($signer))->toBeFalse();
});

it('refuses a session without the code on an unknown device', function () {
    $signer = sessionSigner();
    $service = app(DocumentSigner::class);

    expect(fn () => $service->openSession($signer, sessionPin(), null, false, null, null, null))
        ->toThrow(ValidationException::class);

    expect(app(SigningSession::class)->isOpen($signer))->toBeFalse()
        ->and(EsignLog::where('event', 'session.opened')->exists())->toBeFalse();
});

it('closes the session at the document cap, and when asked', function () {
    $signer = sessionSigner();
    $session = app(SigningSession::class);

    $session->open($signer);

    foreach (range(1, SigningSession::MAX_DOCUMENTS - 1) as $ignored) {
        $session->recordSignature($signer);
    }

    expect($session->isOpen($signer))->toBeTrue()
        ->and($session->remaining($signer))->toBe(1);

    $session->recordSignature($signer);

    expect($session->isOpen($signer))->toBeFalse();

    // And on request.
    $session->open($signer);
    app(DocumentSigner::class)->closeSession($signer);

    expect($session->isOpen($signer))->toBeFalse()
        ->and(EsignLog::where('event', 'session.closed')->exists())->toBeTrue();
});

it('lists what is waiting and signs it in one click', function () {
    $signer = sessionSigner();
    $request = sessionDocument($signer, 'ITD-2026-0110');

    $this->actingAs($signer);

    // Without a session, signing from the queue is refused.
    Livewire::test(SignatureQueue::class)
        ->assertSee('memo.pdf')
        ->assertSee('ITD-2026-0110')
        ->call('sign', $request->id);

    expect(Signature::count())->toBe(0);

    app(DocumentSigner::class)->openSession($signer, sessionPin(), sessionCode($signer), false, null, null, null);

    Livewire::test(SignatureQueue::class)
        ->assertSee('Signing session open')
        ->call('sign', $request->id);

    expect(Signature::count())->toBe(1)
        ->and($request->fresh()->signed_at)->not->toBeNull();
});

it('starts the signing screen where the sender marked it, and lets it be moved', function () {
    $signer = sessionSigner();
    $request = sessionDocument($signer, 'ITD-2026-0130');

    $request->update([
        'placements' => [['page' => 1, 'x' => 210.0, 'y' => 140.0, 'width' => 180.0, 'height' => 80.0]],
        'placed_by' => 'ITD Person',
    ]);

    $this->actingAs($signer);

    Livewire::test(App\Livewire\SignDocument::class, ['signatureRequestId' => $request->id])
        ->assertSet('placements', [['page' => 1, 'x' => 210.0, 'y' => 140.0, 'width' => 180.0, 'height' => 80.0]])
        ->assertSee('Position marked by ITD Person')
        // The signer may still move it before signing.
        ->set('placements', [['page' => 1, 'x' => 260.0, 'y' => 140.0, 'width' => 180.0, 'height' => 80.0]])
        ->set('pin', sessionPin())
        ->set('code', sessionCode($signer))
        ->call('sign')
        ->assertHasNoErrors();

    expect(Signature::sole()->x)->toBe(260.0);
});

it('signs a marked document from the queue at the marked spot', function () {
    $signer = sessionSigner();
    $request = sessionDocument($signer, 'ITD-2026-0131');

    $request->update([
        'placements' => [['page' => 1, 'x' => 305.5, 'y' => 96.0, 'width' => 180.0, 'height' => 80.0]],
        'placed_by' => 'ITD Person',
    ]);

    $this->actingAs($signer);

    app(DocumentSigner::class)->openSession($signer, sessionPin(), sessionCode($signer), false, null, null, null);

    Livewire::test(SignatureQueue::class)
        ->assertSee('marked by ITD Person')
        ->call('sign', $request->id);

    $signature = Signature::sole();

    expect($signature->page)->toBe(1)
        ->and($signature->x)->toBe(305.5)
        ->and($signature->y)->toBe(96.0);
});

it('stamps every page the sender marked, in one signing', function () {
    $signer = sessionSigner();

    $request = sessionDocument(
        $signer,
        'ITD-2026-0132',
        '<p style="margin-top:420px">Approved by:</p>'
        . '<div style="page-break-before: always"><p>Annex</p><p style="margin-top:420px">Approved by:</p></div>'
    );

    $request->update([
        'placements' => [
            ['page' => 1, 'x' => 100.0, 'y' => 120.0, 'width' => 180.0, 'height' => 80.0],
            ['page' => 2, 'x' => 130.0, 'y' => 140.0, 'width' => 180.0, 'height' => 80.0],
        ],
        'placed_by' => 'ITD Person',
    ]);

    $this->actingAs($signer);

    app(DocumentSigner::class)->openSession($signer, sessionPin(), sessionCode($signer), false, null, null, null);

    Livewire::test(SignatureQueue::class)
        ->assertSee('Pages 1, 2')
        ->call('sign', $request->id);

    $signature = Signature::sole();

    // One signing act, one verification code, both pages stamped.
    expect($signature->placements)->toHaveCount(2)
        ->and($signature->page)->toBe(1)
        ->and(EsignLog::where('event', 'signature.signed')->sole()->context['pages_stamped'])->toBe([1, 2])
        ->and($request->fresh()->signed_at)->not->toBeNull();
});

it('stores notification links as paths, so they follow the address in use', function () {
    $signer = sessionSigner();
    $request = sessionDocument($signer, 'ITD-2026-0120');

    $links = [
        (new App\Notifications\SignatureRequested($request, 'ITD Person'))->toArray($signer)['url'],
        (new App\Notifications\SigningSessionOpened('Test Browser', '10.0.0.5', false))->toArray($signer)['url'],
    ];

    foreach ($links as $link) {
        expect($link)->toStartWith('/')
            ->and($link)->not->toContain('http://');
    }
});

it('rewrites older absolute notification links to the address in use', function () {
    $signer = sessionSigner();

    $signer->notifications()->create([
        'id' => (string) Illuminate\Support\Str::uuid(),
        'type' => App\Notifications\SignatureRequested::class,
        'data' => [
            'type' => 'signature_requested',
            'title' => 'Your signature is requested',
            'message' => 'A document is waiting',
            'url' => 'http://localhost/sign/5',
        ],
        'read_at' => null,
    ]);

    $this->actingAs($signer);

    Livewire::test(App\Livewire\NotificationCenter::class)
        ->assertSee('href="/sign/5"', false)
        ->assertDontSee('http://localhost/sign/5');
});

it('forgets remembered devices when the signing PIN changes', function () {
    $signer = sessionSigner();
    $service = app(DocumentSigner::class);

    $service->openSession($signer, sessionPin(), sessionCode($signer), true, null, null, null);

    expect(SigningDevice::where('user_id', $signer->id)->count())->toBe(1);

    $this->actingAs($signer);

    Livewire::test(SignatureSettings::class)
        ->set('currentPassword', 'password')
        ->set('newPin', '735291')
        ->set('newPin_confirmation', '735291')
        ->call('savePin')
        ->assertHasNoErrors();

    expect(SigningDevice::where('user_id', $signer->id)->count())->toBe(0)
        ->and(app(SigningSession::class)->isOpen($signer->fresh()))->toBeFalse();
});
