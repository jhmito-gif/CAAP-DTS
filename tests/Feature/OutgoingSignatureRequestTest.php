<?php

use App\Livewire\CreateOutgoing;
use App\Models\Attachment;
use App\Models\EsignLog;
use App\Models\Office;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\SignatureRequest;
use App\Models\Status;
use App\Models\User;
use App\Notifications\SignatureRequested;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();

    Office::create(['name' => 'ITD', 'description' => 'ITD office']);
    Office::create(['name' => 'HR', 'description' => 'HR office']);
    Status::create(['name' => 'Pending']);
});

function outgoingPdfUpload(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, Pdf::loadHTML('<p>Memorandum</p>')->setPaper('a4')->output());
}

function fillOutgoingForm($component)
{
    return $component
        ->set('office', 'HR')
        ->set('status', 'Pending')
        ->set('subject', 'Memorandum for signature')
        ->set('remarks', 'Please sign');
}

it('requests signatures for every attached PDF when creating an outgoing record', function () {
    $creator = User::factory()->create(['name' => 'Creator Person', 'office' => 'ITD']);
    $signerA = User::factory()->create(['name' => 'Signer Alpha', 'office' => 'HR']);
    $signerB = User::factory()->create(['name' => 'Signer Bravo', 'office' => 'HR']);

    $this->actingAs($creator);

    fillOutgoingForm(Livewire::test(CreateOutgoing::class))
        ->set('attachments', [outgoingPdfUpload('memo.pdf'), outgoingPdfUpload('annex.pdf'), UploadedFile::fake()->image('photo.png')])
        ->assertSee('Request signatures')
        ->set('signerOffice', 'HR')
        ->assertSee('Signer Alpha')
        ->set('signers', [$signerA->id, $signerB->id])
        ->call('createRecord')
        ->assertHasNoErrors();

    $record = Record::sole();
    $pdfIds = Attachment::where('record_id', $record->id)->where('mime_type', 'application/pdf')->pluck('id');

    expect($pdfIds)->toHaveCount(2)
        ->and(Attachment::where('record_id', $record->id)->count())->toBe(3)
        ->and(SignatureRequest::whereIn('attachment_id', $pdfIds)->count())->toBe(4)
        ->and(SignatureRequest::where('requested_by', $creator->id)->count())->toBe(4)
        ->and(RecordTagging::where('record_id', $record->id)->pluck('user_id')->sort()->values()->all())
            ->toBe(collect([$signerA->id, $signerB->id])->sort()->values()->all())
        ->and(EsignLog::where('event', 'request.created')->count())->toBe(4)
        ->and(EsignLog::where('event', 'request.created')->first()->context['source'])->toBe('outgoing creation');

    Notification::assertSentTo([$signerA, $signerB], SignatureRequested::class);
});

it('records where the sender marked each signature', function () {
    $creator = User::factory()->create(['name' => 'Creator Person', 'office' => 'ITD']);
    $first = User::factory()->create(['name' => 'Signer Alpha', 'office' => 'HR']);
    $second = User::factory()->create(['name' => 'Signer Bravo', 'office' => 'HR']);

    $this->actingAs($creator);

    $component = fillOutgoingForm(Livewire::test(CreateOutgoing::class))
        ->set('attachments', [outgoingPdfUpload('memo.pdf')])
        ->set('signerOffice', 'HR')
        ->set('signers', [$first->id, $second->id]);

    $key = $component->get('attachments')[0]->getFilename();

    // Their own boxes, so two approvers do not sign on top of each other, and
    // one of them signs two pages.
    $component
        ->call('setPlacements', $key, $first->id, [
            ['page' => 1, 'x' => 120.5, 'y' => 300.25, 'width' => 180, 'height' => 80],
            ['page' => 2, 'x' => 120.5, 'y' => 96, 'width' => 180, 'height' => 80],
        ])
        ->call('setPlacements', $key, $second->id, [
            ['page' => 1, 'x' => 320.0, 'y' => 300.25, 'width' => 180, 'height' => 80],
        ])
        ->call('createRecord')
        ->assertHasNoErrors();

    $alpha = SignatureRequest::where('signer_id', $first->id)->sole();
    $bravo = SignatureRequest::where('signer_id', $second->id)->sole();

    expect($alpha->placements())->toBe([
        ['page' => 1, 'x' => 120.5, 'y' => 300.25, 'width' => 180.0, 'height' => 80.0],
        ['page' => 2, 'x' => 120.5, 'y' => 96.0, 'width' => 180.0, 'height' => 80.0],
    ])
        ->and($alpha->markedPages())->toBe([1, 2])
        ->and($bravo->placements())->toHaveCount(1)
        ->and($bravo->placement()['x'])->toBe(320.0)
        ->and($alpha->placed_by)->toBe('Creator Person');
});

it('leaves the placement empty when the sender does not mark one', function () {
    $creator = User::factory()->create(['office' => 'ITD']);
    $signer = User::factory()->create(['office' => 'HR']);

    $this->actingAs($creator);

    fillOutgoingForm(Livewire::test(CreateOutgoing::class))
        ->set('attachments', [outgoingPdfUpload('memo.pdf')])
        ->set('signerOffice', 'HR')
        ->set('signers', [$signer->id])
        ->call('createRecord')
        ->assertHasNoErrors();

    expect(SignatureRequest::sole()->placements())->toBe([]);
});

it('offers and sends no signature requests without a PDF attachment', function () {
    $creator = User::factory()->create(['office' => 'ITD']);
    $signer = User::factory()->create(['office' => 'HR']);

    $this->actingAs($creator);

    fillOutgoingForm(Livewire::test(CreateOutgoing::class))
        ->set('attachments', [UploadedFile::fake()->image('photo.png')])
        ->assertDontSee('Request signatures')
        // Even a tampered request with signers but no PDF sends nothing.
        ->set('signers', [$signer->id])
        ->call('createRecord')
        ->assertHasNoErrors();

    expect(Record::count())->toBe(1)
        ->and(SignatureRequest::count())->toBe(0);

    Notification::assertNothingSent();
});

it('clears the chosen signatories when the last PDF is removed', function () {
    $signer = User::factory()->create(['office' => 'HR']);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(CreateOutgoing::class)
        ->set('attachments', [outgoingPdfUpload('memo.pdf')])
        ->set('signers', [$signer->id])
        ->call('removeAttachment', 0)
        ->assertSet('signers', [])
        ->assertDontSee('Request signatures');
});
