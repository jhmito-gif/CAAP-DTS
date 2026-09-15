<?php

use App\Livewire\ManageSignatureRequests;
use App\Livewire\OutgoingTransaction;
use App\Livewire\SignatureSettings;
use App\Livewire\SignDocument;
use App\Livewire\VerifySignature;
use App\Models\Attachment;
use App\Models\EsignLog;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\SignatureRequest;
use App\Models\SigningPin;
use App\Models\User;
use App\Models\UserSignature;
use App\Notifications\SignatureRequested;
use App\Support\DocumentSigner;
use App\Support\PdfSignatureStamper;
use App\Support\PdfSigningException;
use App\Support\SignatureImage;
use App\Support\SigningSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

function esignPdf(string $body = 'For signature.'): string
{
    return Pdf::loadHTML("<h1>Memorandum</h1><p>{$body}</p>")->setPaper('a4')->output();
}

function esignSignaturePng(): string
{
    $image = imagecreatetruecolor(300, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagesetthickness($image, 4);
    imageline($image, 20, 70, 280, 30, imagecolorallocate($image, 10, 10, 10));

    ob_start();
    imagepng($image);

    return ob_get_clean();
}

function esignPin(): string
{
    return '482916';
}

/** A signer with a confirmed authenticator, a saved signature and a signing PIN. */
function esignSigner(array $attributes = []): User
{
    $user = User::factory()->create($attributes + ['name' => 'Signer Person', 'office' => 'CPO']);

    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt(app(Google2FA::class)->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ])->save();

    UserSignature::create([
        'user_id' => $user->id,
        'image' => base64_encode(SignatureImage::normalize(esignSignaturePng())),
    ]);

    SigningPin::create(['user_id' => $user->id, 'pin' => esignPin()]);

    return $user->fresh();
}

function esignCode(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));
}

/** An ITD record with one uploaded PDF and a signature request for $signer. */
function esignRequest(User $signer, string $reference = 'ITD-2026-0100'): SignatureRequest
{
    $record = Record::create([
        'reference' => $reference,
        'subject' => 'Memorandum for signature',
        'created_by' => 'Owner Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    $attachment = Attachment::storeEncrypted($record, null, UploadedFile::fake()->createWithContent('memo.pdf', esignPdf()), 'Owner Person');

    RecordTagging::create([
        'record_id' => $record->id,
        'user_id' => $signer->id,
        'office' => $signer->office,
        'tagged_by' => 'Owner Person',
    ]);

    return SignatureRequest::create([
        'attachment_id' => $attachment->id,
        'record_id' => $record->id,
        'signer_id' => $signer->id,
    ]);
}

function esignPlacement(array $overrides = []): array
{
    return $overrides + ['page' => 1, 'x' => 380, 'y' => 60, 'width' => 180, 'height' => 80];
}

function esignErrors(callable $attempt): array
{
    try {
        $attempt();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

it('stamps a signature onto a PDF page and rejects placements outside it', function () {
    $stamper = app(PdfSignatureStamper::class);
    $png = SignatureImage::normalize(esignSignaturePng());

    expect($stamper->stamp(esignPdf(), $png, esignPlacement(), ['Signer Person', 'Verify: ABCDE-FGHJK']))
        ->toStartWith('%PDF');

    expect(fn () => $stamper->stamp(esignPdf(), $png, esignPlacement(['x' => 500, 'y' => 800]), ['Signer']))
        ->toThrow(PdfSigningException::class, 'inside the page');

    expect(fn () => $stamper->stamp(esignPdf(), $png, esignPlacement(['page' => 3]), ['Signer']))
        ->toThrow(PdfSigningException::class, 'does not exist');
});

it('signs with signing PIN and authenticator code, saving a new version and an audit trail', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);
    $owner = User::factory()->create(['name' => 'Owner Person', 'office' => 'ITD']);
    $original = $signatureRequest->attachment->originalContents();

    $signature = app(DocumentSigner::class)->sign(
        $signatureRequest, $signer, esignPlacement(), esignPin(), esignCode($signer), '10.0.0.8', 'Test Browser'
    );

    $attachment = $signatureRequest->attachment->fresh();
    $signed = $attachment->contents();

    expect($signature->verification_code)->toMatch('/^[A-Z2-9]{5}-[A-Z2-9]{5}$/')
        ->and($signature->source_sha256)->toBe(hash('sha256', $original))
        ->and($signature->signed_sha256)->toBe(hash('sha256', $signed))
        ->and($signed)->toStartWith('%PDF')
        ->and($signed)->not->toBe($original)
        ->and($attachment->originalContents())->toBe($original)
        ->and($signature->signer_name)->toBe('Signer Person')
        ->and($signature->ip_address)->toBe('10.0.0.8')
        ->and($signatureRequest->fresh()->signed_at)->not->toBeNull()
        ->and($attachment->isSigningComplete())->toBeTrue()
        ->and($owner->can('delete', $signatureRequest->record))->toBeFalse();

    Storage::disk('local')->assertExists($signature->path);
    expect(Storage::disk('local')->get($signature->path))->not->toContain('%PDF');
});

it('rejects a wrong PIN or code and locks the signer out after repeated failures', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);
    $service = app(DocumentSigner::class);

    expect(esignErrors(fn () => $service->sign($signatureRequest, $signer, esignPlacement(), '000000', esignCode($signer), null, null)))
        ->toHaveKey('pin');

    // Correct PIN without a code: asks for the code, not counted as a failure.
    expect(esignErrors(fn () => $service->sign($signatureRequest, $signer, esignPlacement(), esignPin(), null, null, null)))
        ->toHaveKey('code');

    foreach (range(1, DocumentSigner::MAX_ATTEMPTS - 1) as $attempt) {
        expect(esignErrors(fn () => $service->sign($signatureRequest, $signer, esignPlacement(), esignPin(), '000000', null, null)))
            ->toHaveKey('code');
    }

    $locked = esignErrors(fn () => $service->sign($signatureRequest, $signer, esignPlacement(), esignPin(), esignCode($signer), null, null));

    expect($locked['pin'][0])->toContain('Too many failed attempts')
        ->and($signatureRequest->fresh()->signed_at)->toBeNull();
});

it('asks for the authenticator code once per session, then only the signing PIN', function () {
    $signer = esignSigner();
    $first = esignRequest($signer, 'ITD-2026-0100');
    $second = esignRequest($signer, 'ITD-2026-0102');
    $service = app(DocumentSigner::class);

    $this->actingAs($signer)->get(route('esign.sign', $first))->assertSee('Authenticator code');

    $service->sign($first, $signer, esignPlacement(), esignPin(), esignCode($signer), null, null);

    expect($service->requiresCode($signer))->toBeFalse();

    $this->get(route('esign.sign', $second))
        ->assertSee('Authenticator already confirmed')
        ->assertDontSee('id="esignCode"', false);

    // The signing screen signs the next document with the PIN alone.
    Livewire::test(SignDocument::class, ['signatureRequestId' => $second->id])
        ->set('page', 1)->set('x', 380)->set('y', 60)->set('width', 180)->set('height', 80)
        ->set('pin', esignPin())
        ->call('sign')
        ->assertHasNoErrors()
        ->assertRedirect(route('show-transactions', $second->record_id));

    expect($second->fresh()->signed_at)->not->toBeNull()
        ->and(EsignLog::where('event', 'signature.signed')->orderBy('id')->get()->pluck('context.two_factor')->all())
            ->toBe(['code', 'session']);

    // A wrong PIN is still refused within a confirmed session.
    $third = esignRequest($signer, 'ITD-2026-0103');

    expect(esignErrors(fn () => $service->sign($third, $signer, esignPlacement(), '999999', null, null, null)))
        ->toHaveKey('pin');
});

it('ends the session confirmation after the time limit, on logout and when two-factor is reset', function () {
    $signer = esignSigner();
    $session = app(SigningSession::class);

    $session->confirm($signer);
    $this->travel(SigningSession::MAX_HOURS)->hours();
    $this->travel(1)->minutes();
    expect($session->isConfirmed($signer))->toBeFalse();

    $session->confirm($signer);
    $this->actingAs($signer)->post(route('logout'));
    expect($session->isConfirmed($signer))->toBeFalse();

    $session->confirm($signer);
    $signer->forceFill(['two_factor_secret' => Fortify::currentEncrypter()->encrypt(app(Google2FA::class)->generateSecretKey())])->save();
    expect($session->isConfirmed($signer->fresh()))->toBeFalse();
});

it('sets a signing PIN only with the current password and refuses guessable PINs', function () {
    $user = User::factory()->create(['office' => 'CPO']);

    $this->actingAs($user);

    Livewire::test(SignatureSettings::class)
        ->set('currentPassword', 'password')->set('newPin', '123456')->set('newPin_confirmation', '123456')
        ->call('savePin')
        ->assertHasErrors(['newPin'])
        ->set('currentPassword', 'wrong-password')->set('newPin', '482916')->set('newPin_confirmation', '482916')
        ->call('savePin')
        ->assertHasErrors(['currentPassword']);

    expect($user->fresh()->signingPin)->toBeNull();

    Livewire::test(SignatureSettings::class)
        ->set('currentPassword', 'password')->set('newPin', '482916')->set('newPin_confirmation', '482916')
        ->call('savePin')
        ->assertHasNoErrors()
        ->assertSet('currentPassword', '')
        ->assertSet('newPin', '')
        ->assertSee('Change PIN');

    $pin = $user->fresh()->signingPin;

    expect($pin->pin)->not->toBe('482916')
        ->and($pin->matches('482916'))->toBeTrue()
        ->and($pin->matches('482917'))->toBeFalse()
        ->and($pin->toArray())->not->toHaveKey('pin')
        ->and(SigningPin::isGuessable('111111'))->toBeTrue()
        ->and(SigningPin::isGuessable('98765432'))->toBeTrue()
        ->and(SigningPin::isGuessable('482916'))->toBeFalse();
});

it('will not sign for someone else, without two-factor authentication, a signing PIN or a saved signature', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);
    $service = app(DocumentSigner::class);

    $other = esignSigner(['name' => 'Other Person']);
    expect($service->blocker($signatureRequest, $other))->toBe('This signature request is not assigned to you.');

    $noTwoFactor = User::factory()->create(['office' => 'CPO']);
    RecordTagging::create(['record_id' => $signatureRequest->record_id, 'user_id' => $noTwoFactor->id, 'office' => 'CPO', 'tagged_by' => 'Owner Person']);
    $signatureRequest->update(['signer_id' => $noTwoFactor->id]);
    expect($service->blocker($signatureRequest->fresh(), $noTwoFactor))->toContain('two-factor');

    $signatureRequest->update(['signer_id' => $signer->id]);
    $signer->signingPin->delete();
    expect($service->blocker($signatureRequest->fresh(), $signer->fresh()))->toContain('signing PIN');

    $signer->signature->delete();
    expect($service->blocker($signatureRequest->fresh(), $signer->fresh()))->toContain('Save your signature');
});

it('refuses to sign a document whose stored file was altered', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);

    Storage::disk('local')->put($signatureRequest->attachment->path, Crypt::encryptString(esignPdf('Tampered text.')));

    expect(fn () => app(DocumentSigner::class)->sign($signatureRequest, $signer, esignPlacement(), esignPin(), esignCode($signer), null, null))
        ->toThrow(ValidationException::class, 'integrity check');

    expect($signatureRequest->fresh()->signed_at)->toBeNull();
});

it('lets the owning office request signatures, clearing and notifying the signer', function () {
    Notification::fake();

    $owner = User::factory()->create(['name' => 'Owner Person', 'office' => 'ITD']);
    $signer = esignSigner();

    $record = Record::create([
        'reference' => 'ITD-2026-0101',
        'subject' => 'Memorandum',
        'created_by' => 'Owner Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    $attachment = Attachment::storeEncrypted($record, null, UploadedFile::fake()->createWithContent('memo.pdf', esignPdf()), 'Owner Person');

    $this->actingAs($owner);

    Livewire::test(ManageSignatureRequests::class)
        ->call('open', $attachment->id)
        ->assertDispatched('open-signature-requests-modal')
        ->call('requestSignature', $signer->id);

    expect(SignatureRequest::where('attachment_id', $attachment->id)->where('signer_id', $signer->id)->exists())->toBeTrue()
        ->and(RecordTagging::where('record_id', $record->id)->where('user_id', $signer->id)->exists())->toBeTrue();

    Notification::assertSentTo($signer, SignatureRequested::class);

    $this->actingAs(User::factory()->create(['office' => 'HR']));

    Livewire::test(ManageSignatureRequests::class)
        ->call('open', $attachment->id)
        ->assertNotDispatched('open-signature-requests-modal');
});

it('only opens the signing page for the assigned signer', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);

    $this->actingAs(User::factory()->create(['office' => 'HR']))
        ->get(route('esign.sign', $signatureRequest))
        ->assertForbidden();

    $this->actingAs($signer)
        ->get(route('esign.sign', $signatureRequest))
        ->assertOk()
        ->assertSee('memo.pdf');
});

it('verifies a signature by code and matches a signed copy of the file', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);
    $signature = app(DocumentSigner::class)->sign($signatureRequest, $signer, esignPlacement(), esignPin(), esignCode($signer), null, null);

    $this->actingAs($signer);

    Livewire::test(VerifySignature::class)
        ->set('code', strtolower($signature->verification_code))
        ->call('lookup')
        ->assertHasNoErrors()
        ->assertSee('Signer Person')
        ->set('file', UploadedFile::fake()->createWithContent('copy.pdf', $signatureRequest->attachment->fresh()->contents()))
        ->call('checkFile')
        ->assertSee('This file matches')
        ->set('file', UploadedFile::fake()->createWithContent('other.pdf', esignPdf('Unrelated.')))
        ->call('checkFile')
        ->assertSee('does not match any signed version');
});

it('keeps documents sent for signature from being removed', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);

    $this->actingAs(User::factory()->create(['name' => 'Owner Person', 'office' => 'ITD']));

    Livewire::test(OutgoingTransaction::class, ['recordId' => $signatureRequest->record_id])
        ->call('deleteAttachment', $signatureRequest->attachment_id);

    expect(Attachment::find($signatureRequest->attachment_id))->not->toBeNull();
});
