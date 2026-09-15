<?php

use App\Livewire\ManageSignatureRequests;
use App\Livewire\OutgoingTransaction;
use App\Livewire\VerifySignature;
use App\Models\Attachment;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Models\UserSignature;
use App\Notifications\SignatureRequested;
use App\Support\DocumentSigner;
use App\Support\PdfSignatureStamper;
use App\Support\PdfSigningException;
use App\Support\SignatureImage;
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

/** A signer with a confirmed authenticator and a saved signature. */
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

    return $user->fresh();
}

function esignCode(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));
}

/** An ITD record with one uploaded PDF and a signature request for $signer. */
function esignRequest(User $signer): SignatureRequest
{
    $record = Record::create([
        'reference' => 'ITD-2026-0100',
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

it('signs with password and authenticator code, saving a new version and an audit trail', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);
    $owner = User::factory()->create(['name' => 'Owner Person', 'office' => 'ITD']);
    $original = $signatureRequest->attachment->originalContents();

    $signature = app(DocumentSigner::class)->sign(
        $signatureRequest, $signer, esignPlacement(), 'password', esignCode($signer), '10.0.0.8', 'Test Browser'
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

it('rejects a wrong password or code and locks the signer out after repeated failures', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);
    $service = app(DocumentSigner::class);

    $errors = function (callable $attempt): array {
        try {
            $attempt();
        } catch (ValidationException $exception) {
            return $exception->errors();
        }

        return [];
    };

    expect($errors(fn () => $service->sign($signatureRequest, $signer, esignPlacement(), 'wrong-password', '123456', null, null)))
        ->toHaveKey('password');

    foreach (range(1, DocumentSigner::MAX_ATTEMPTS - 1) as $attempt) {
        expect($errors(fn () => $service->sign($signatureRequest, $signer, esignPlacement(), 'password', '000000', null, null)))
            ->toHaveKey('code');
    }

    $locked = $errors(fn () => $service->sign($signatureRequest, $signer, esignPlacement(), 'password', esignCode($signer), null, null));

    expect($locked['code'][0])->toContain('Too many failed attempts')
        ->and($signatureRequest->fresh()->signed_at)->toBeNull();
});

it('will not sign for someone else, without two-factor authentication, or without a saved signature', function () {
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
    $signer->signature->delete();
    expect($service->blocker($signatureRequest->fresh(), $signer->fresh()))->toContain('Save your signature');
});

it('refuses to sign a document whose stored file was altered', function () {
    $signer = esignSigner();
    $signatureRequest = esignRequest($signer);

    Storage::disk('local')->put($signatureRequest->attachment->path, Crypt::encryptString(esignPdf('Tampered text.')));

    expect(fn () => app(DocumentSigner::class)->sign($signatureRequest, $signer, esignPlacement(), 'password', esignCode($signer), null, null))
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
    $signature = app(DocumentSigner::class)->sign($signatureRequest, $signer, esignPlacement(), 'password', esignCode($signer), null, null);

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
