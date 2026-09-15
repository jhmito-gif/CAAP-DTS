<?php

use App\Livewire\ManageSignatureRequests;
use App\Livewire\SignatureSettings;
use App\Livewire\VerifySignature;
use App\Models\Attachment;
use App\Models\EsignLog;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Models\UserSignature;
use App\Support\DocumentSigner;
use App\Support\SignatureImage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

function logTestPng(): string
{
    $image = imagecreatetruecolor(300, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagesetthickness($image, 4);
    imageline($image, 20, 70, 280, 30, imagecolorallocate($image, 10, 10, 10));

    ob_start();
    imagepng($image);

    return ob_get_clean();
}

function logTestSigner(): User
{
    $user = User::factory()->create(['name' => 'Signer Person', 'office' => 'CPO']);

    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt(app(Google2FA::class)->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ])->save();

    UserSignature::create(['user_id' => $user->id, 'image' => base64_encode(SignatureImage::normalize(logTestPng()))]);

    return $user->fresh();
}

function logTestCode(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));
}

function logTestRequest(User $signer): SignatureRequest
{
    $record = Record::create([
        'reference' => 'ITD-2026-0200',
        'subject' => 'Memorandum for signature',
        'created_by' => 'Owner Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    $pdf = Pdf::loadHTML('<p>For signature.</p>')->setPaper('a4')->output();
    $attachment = Attachment::storeEncrypted($record, null, UploadedFile::fake()->createWithContent('memo.pdf', $pdf), 'Owner Person');

    RecordTagging::create(['record_id' => $record->id, 'user_id' => $signer->id, 'office' => $signer->office, 'tagged_by' => 'Owner Person']);

    return SignatureRequest::create(['attachment_id' => $attachment->id, 'record_id' => $record->id, 'signer_id' => $signer->id]);
}

$placement = ['page' => 1, 'x' => 380, 'y' => 60, 'width' => 180, 'height' => 80];

it('logs a successful signature to the database and the esign log file', function () use ($placement) {
    Log::spy();

    $signer = logTestSigner();
    $signatureRequest = logTestRequest($signer);

    $signature = app(DocumentSigner::class)->sign($signatureRequest, $signer, $placement, 'password', logTestCode($signer), null, null);

    $entry = EsignLog::where('event', 'signature.signed')->sole();

    expect($entry->outcome)->toBe(EsignLog::SUCCESS)
        ->and($entry->user_name)->toBe('Signer Person')
        ->and($entry->user_office)->toBe('CPO')
        ->and($entry->record_reference)->toBe('ITD-2026-0200')
        ->and($entry->signature_id)->toBe($signature->id)
        ->and($entry->context['verification_code'])->toBe($signature->verification_code)
        ->and($entry->context['signed_sha256'])->toBe($signature->signed_sha256)
        ->and($entry->context['document'])->toBe('memo.pdf');

    Log::shouldHaveReceived('channel')->with('esign');
});

it('logs wrong passwords, wrong codes and the lockout without storing the secrets', function () use ($placement) {
    $signer = logTestSigner();
    $signatureRequest = logTestRequest($signer);
    $service = app(DocumentSigner::class);

    $attempt = function (string $password, string $code) use ($service, $signatureRequest, $signer, $placement) {
        try {
            $service->sign($signatureRequest, $signer, $placement, $password, $code, null, null);
        } catch (ValidationException) {
            //
        }
    };

    $attempt('wrong-password', '123456');

    foreach (range(1, DocumentSigner::MAX_ATTEMPTS - 1) as $i) {
        $attempt('password', '000000');
    }

    $attempt('password', logTestCode($signer));

    expect(EsignLog::where('event', 'signature.password_failed')->count())->toBe(1)
        ->and(EsignLog::where('event', 'signature.code_failed')->count())->toBe(DocumentSigner::MAX_ATTEMPTS - 1)
        ->and(EsignLog::where('event', 'signature.locked_out')->count())->toBe(1)
        ->and(EsignLog::where('outcome', EsignLog::FAILURE)->count())->toBe(DocumentSigner::MAX_ATTEMPTS + 1);

    // Only the logged details (timestamps would contain "000000" microseconds).
    $logged = EsignLog::all(['context', 'user_agent', 'ip_address'])->toJson();

    expect($logged)->not->toContain('wrong-password')
        ->and($logged)->not->toContain('000000')
        ->and($logged)->not->toContain('password":');
});

it('keeps the integrity-failure entry even though the signing transaction rolls back', function () use ($placement) {
    $signer = logTestSigner();
    $signatureRequest = logTestRequest($signer);

    Storage::disk('local')->put(
        $signatureRequest->attachment->path,
        Crypt::encryptString(Pdf::loadHTML('<p>Tampered.</p>')->setPaper('a4')->output())
    );

    try {
        app(DocumentSigner::class)->sign($signatureRequest, $signer, $placement, 'password', logTestCode($signer), null, null);
    } catch (ValidationException) {
        //
    }

    $entry = EsignLog::where('event', 'signature.integrity_failed')->sole();

    expect($entry->outcome)->toBe(EsignLog::FAILURE)
        ->and($entry->context)->toHaveKeys(['expected_sha256', 'actual_sha256']);
});

it('logs signature requests being created, removed and refused', function () {
    $owner = User::factory()->create(['name' => 'Owner Person', 'office' => 'ITD']);
    $signer = logTestSigner();
    $signatureRequest = logTestRequest($signer);
    $signatureRequest->delete();

    $this->actingAs($owner);

    $component = Livewire::test(ManageSignatureRequests::class)
        ->call('open', $signatureRequest->attachment_id)
        ->call('requestSignature', $signer->id);

    $created = SignatureRequest::where('signer_id', $signer->id)->sole();
    $component->call('cancelRequest', $created->id);

    expect(EsignLog::where('event', 'request.created')->sole()->context['signer_name'])->toBe('Signer Person')
        ->and(EsignLog::where('event', 'request.cancelled')->sole()->user_name)->toBe('Owner Person');

    $this->actingAs(User::factory()->create(['office' => 'HR']));
    Livewire::test(ManageSignatureRequests::class)->call('open', $signatureRequest->attachment_id);

    expect(EsignLog::where('event', 'request.denied')->where('outcome', EsignLog::FAILURE)->exists())->toBeTrue();
});

it('logs who opened or was refused the signing page', function () {
    $signer = logTestSigner();
    $signatureRequest = logTestRequest($signer);
    $stranger = User::factory()->create(['name' => 'Stranger', 'office' => 'HR']);

    $this->actingAs($stranger)->get(route('esign.sign', $signatureRequest))->assertForbidden();
    $this->actingAs($signer)->get(route('esign.sign', $signatureRequest))->assertOk();

    expect(EsignLog::where('event', 'sign_page.denied')->sole()->user_name)->toBe('Stranger')
        ->and(EsignLog::where('event', 'sign_page.opened')->sole()->user_name)->toBe('Signer Person');
});

it('logs verification checks and signature profile changes', function () {
    $signer = logTestSigner();

    $this->actingAs($signer);

    Livewire::test(VerifySignature::class)
        ->set('code', 'NOPE0-NOPE0')
        ->call('lookup');

    Livewire::test(SignatureSettings::class)
        ->call('saveDrawn', 'data:image/png;base64,' . base64_encode(logTestPng()))
        ->call('remove');

    expect(EsignLog::where('event', 'verify.code_lookup')->where('outcome', EsignLog::FAILURE)->exists())->toBeTrue()
        ->and(EsignLog::where('event', 'profile.signature_saved')->sole()->context['method'])->toBe('drawn')
        ->and(EsignLog::where('event', 'profile.signature_removed')->exists())->toBeTrue();
});

it('keeps the e-sign log append-only', function () {
    $entry = EsignLog::create(['event' => 'sign_page.opened', 'outcome' => EsignLog::INFO]);

    expect(fn () => $entry->update(['outcome' => EsignLog::SUCCESS]))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);

    expect(EsignLog::count())->toBe(1);
});

it('shows the e-sign log to admins only', function () {
    EsignLog::create(['event' => 'signature.signed', 'outcome' => EsignLog::SUCCESS, 'user_name' => 'Signer Person']);

    $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
        ->get(\App\Filament\Resources\EsignLogResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Signer Person');

    $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
        ->get(\App\Filament\Resources\EsignLogResource::getUrl('index'))
        ->assertForbidden();
});
