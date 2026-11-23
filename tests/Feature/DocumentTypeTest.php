<?php

use App\Livewire\CreateOutgoing;
use App\Livewire\DocumentExplorer;
use App\Livewire\ManageRecord;
use App\Models\Access;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentFolder;
use App\Models\Office;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    foreach (['ITD', 'HR', 'CPO'] as $name) {
        Office::firstOrCreate(['name' => $name], ['description' => "{$name} office"]);
    }
});

function typeId(string $name): int
{
    return (int) DocumentCategory::where('name', $name)->value('id');
}

function typedRecord(string $owner, string $reference): Record
{
    return Record::create([
        'reference' => $reference,
        'subject' => "Subject of {$reference}",
        'created_by' => "{$owner} Person",
        'origin' => $owner,
        'owner' => $owner,
    ]);
}

function typedAttachment(Record $record, string $name, ?string $type): Attachment
{
    return Attachment::storeEncrypted(
        $record,
        null,
        UploadedFile::fake()->createWithContent($name, "contents of {$name}"),
        'Uploader',
        $type ? typeId($type) : null
    );
}

function typedDocument(string $office, string $title, ?string $type, ?DocumentFolder $folder = null): Document
{
    return Document::create([
        'office' => $office,
        'folder_id' => $folder?->id,
        'document_category_id' => $type ? typeId($type) : null,
        'title' => $title,
        'original_name' => $title,
        'path' => 'documents/' . uniqid() . '.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 2048,
        'is_encrypted' => true,
        'uploaded_by' => "{$office} Staff",
    ]);
}

function outgoingDraft(): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test(CreateOutgoing::class)
        ->set('office', 'HR')
        ->set('status', 'Pending')
        ->set('subject', 'Quarterly report')
        ->set('remarks', 'For information');
}

/*
|--------------------------------------------------------------------------
| Picking a type on upload
|--------------------------------------------------------------------------
*/

it('asks for the document type of every file attached to an outgoing record', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    outgoingDraft()
        ->set('attachments', [UploadedFile::fake()->image('scan.png'), UploadedFile::fake()->image('annex.png')])
        ->set('uploadTypes', [0 => typeId('Report')])
        ->call('createRecord')
        ->assertHasErrors(['uploadTypes.1' => 'required'])
        ->assertSee('document type for annex.png');

    expect(Record::count())->toBe(0);
});

it('files each attachment under the type picked for it', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    outgoingDraft()
        ->set('attachments', [UploadedFile::fake()->image('report.png'), UploadedFile::fake()->image('cover-letter.png')])
        ->set('uploadTypes', [typeId('Report'), typeId('Letter')])
        ->call('createRecord')
        ->assertHasNoErrors();

    expect(Attachment::where('original_name', 'report.png')->value('document_category_id'))->toBe(typeId('Report'))
        ->and(Attachment::where('original_name', 'cover-letter.png')->value('document_category_id'))->toBe(typeId('Letter'));
});

it('keeps the types with their files when one file is taken out', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    outgoingDraft()
        ->set('attachments', [
            UploadedFile::fake()->image('memo.png'),
            UploadedFile::fake()->image('wrong-file.png'),
            UploadedFile::fake()->image('contract.png'),
        ])
        ->set('uploadTypes', [typeId('Memorandum'), typeId('Form'), typeId('Contract')])
        ->call('removeAttachment', 1)
        ->assertSet('uploadTypes', [typeId('Memorandum'), typeId('Contract')])
        ->call('createRecord')
        ->assertHasNoErrors();

    expect(Attachment::where('original_name', 'contract.png')->value('document_category_id'))->toBe(typeId('Contract'))
        ->and(Attachment::where('original_name', 'wrong-file.png')->exists())->toBeFalse();
});

it('refuses a type that does not exist', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    outgoingDraft()
        ->set('attachments', [UploadedFile::fake()->image('scan.png')])
        ->set('uploadTypes', [999999])
        ->call('createRecord')
        ->assertHasErrors(['uploadTypes.0' => 'exists']);
});

it('does not hold up sending when no document types have been set up', function () {
    DocumentCategory::query()->get()->each->delete();
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    outgoingDraft()
        ->set('attachments', [UploadedFile::fake()->image('scan.png')])
        ->call('createRecord')
        ->assertHasNoErrors();

    expect(Attachment::sole()->document_category_id)->toBeNull();
});

it('asks for the type of a file added while editing a record', function () {
    $creator = User::factory()->create(['name' => 'ITD Person', 'office' => 'ITD']);
    $record = typedRecord('ITD', 'ITD-2026-0100');
    Transaction::create([
        'record_id' => $record->id, 'internal_reference' => $record->reference,
        'remarks' => 'For action', 'status' => 'Pending',
        'destination' => 'HR', 'office' => 'ITD', 'forwarded_by' => 'ITD Person',
    ]);

    $this->actingAs($creator);

    $component = Livewire::test(ManageRecord::class)
        ->call('edit', $record->id)
        ->set('newAttachments', [UploadedFile::fake()->image('order.png')])
        ->call('save')
        ->assertHasErrors(['uploadTypes.0' => 'required']);

    $component->set('uploadTypes', [typeId('Office Order')])
        ->call('save')
        ->assertHasNoErrors();

    expect(Attachment::sole()->document_category_id)->toBe(typeId('Office Order'));
});

it('asks for the type of a file uploaded to the library', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $finish = function (array $extra) {
        $upload = (string) Str::uuid();
        $path = sys_get_temp_dir() . '/' . uniqid('piece-', true);
        file_put_contents($path, \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<p>Memorandum on runway works.</p>')->output());

        $this->post(route('documents.upload.chunk'), [
            'upload' => $upload, 'index' => 0, 'total' => 1,
            'chunk' => new UploadedFile($path, '0', 'application/octet-stream', null, true),
        ])->assertOk();

        return $this->postJson(route('documents.upload.finish'), ['upload' => $upload, 'name' => 'memo.pdf', 'office' => 'ITD'] + $extra);
    };

    $finish([])->assertStatus(422)->assertJsonValidationErrors(['category' => 'Pick the document type before uploading.']);

    $finish(['category' => typeId('Memorandum')])->assertSuccessful();

    expect(Document::sole()->document_category_id)->toBe(typeId('Memorandum'));
});

it('offers "Other" for a file none of the types fit', function () {
    expect(DocumentCategory::where('name', 'Other')->exists())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Filtering by type on the Documents page
|--------------------------------------------------------------------------
*/

it('lists every file of one type the user may open, and nothing else', function () {
    $user = User::factory()->create(['office' => 'ITD']);

    typedDocument('ITD', 'Library memo.pdf', 'Memorandum');
    typedDocument('ITD', 'Library letter.pdf', 'Letter');
    typedDocument('HR', 'HR library memo.pdf', 'Memorandum');

    typedAttachment(typedRecord('ITD', 'ITD-2026-0001'), 'own-record-memo.pdf', 'Memorandum');
    typedAttachment(typedRecord('CPO', 'CPO-2026-0001'), 'unrelated-memo.pdf', 'Memorandum');

    $routed = typedRecord('HR', 'HR-2026-0001');
    Transaction::create([
        'record_id' => $routed->id, 'internal_reference' => $routed->reference,
        'remarks' => 'For action', 'status' => 'Pending',
        'destination' => 'ITD', 'office' => 'HR', 'forwarded_by' => 'HR Person',
    ]);
    typedAttachment($routed, 'routed-memo.pdf', 'Memorandum');

    $this->actingAs($user);

    Livewire::test(DocumentExplorer::class)
        ->call('openType', (string) typeId('Memorandum'))
        ->assertSet('location', 'type:' . typeId('Memorandum'))
        ->assertSee('Library memo.pdf')
        ->assertSee('own-record-memo.pdf')
        ->assertSee('routed-memo.pdf')
        ->assertSee('ITD-2026-0001 · Memorandum')
        ->assertDontSee('Library letter.pdf')
        ->assertDontSee('HR library memo.pdf')
        ->assertDontSee('unrelated-memo.pdf');
});

it('leaves out files in folders closed to the user, but keeps the library root', function () {
    $user = User::factory()->create(['office' => 'ITD']);
    $closed = DocumentFolder::create(['office' => 'ITD', 'name' => 'Legal', 'created_by' => 'ITD Staff', 'is_restricted' => true]);

    typedDocument('ITD', 'Root memo.pdf', 'Memorandum');
    typedDocument('ITD', 'Closed memo.pdf', 'Memorandum', $closed);

    $this->actingAs($user);

    Livewire::test(DocumentExplorer::class)
        ->call('openType', (string) typeId('Memorandum'))
        ->assertSee('Root memo.pdf')
        ->assertDontSee('Closed memo.pdf');
});

it('goes back to the library when the type filter is cleared', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(DocumentExplorer::class)
        ->call('openType', (string) typeId('Letter'))
        ->call('openType', '')
        ->assertSet('location', 'library');
});

it('searches only among files of the open type', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    typedDocument('ITD', 'Budget memo.pdf', 'Memorandum');
    typedDocument('ITD', 'Budget letter.pdf', 'Letter');

    Livewire::test(DocumentExplorer::class)
        ->call('openType', (string) typeId('Memorandum'))
        ->set('search', 'Budget')
        ->assertViewHas('found', fn ($found) => $found->pluck('name')->all() === ['Budget memo.pdf']);
});

it('finds the same records in a query as one by one', function () {
    $itd = User::factory()->create(['office' => 'ITD']);

    $owned = typedRecord('ITD', 'ITD-2026-0001');
    $originated = Record::create(['reference' => 'HR-2026-0009', 'subject' => 'x', 'created_by' => 'HR', 'origin' => 'ITD', 'owner' => 'HR']);
    $routedIn = typedRecord('HR', 'HR-2026-0002');
    $routedOut = typedRecord('CPO', 'CPO-2026-0002');
    $tagged = typedRecord('CPO', 'CPO-2026-0003');
    $granted = typedRecord('CPO', 'CPO-2026-0004');
    $unrelated = typedRecord('CPO', 'CPO-2026-0005');

    foreach ([[$routedIn, 'HR', 'ITD'], [$routedOut, 'ITD', 'HR']] as [$record, $from, $to]) {
        Transaction::create([
            'record_id' => $record->id, 'internal_reference' => $record->reference,
            'remarks' => 'x', 'status' => 'Pending', 'destination' => $to, 'office' => $from, 'forwarded_by' => 'x',
        ]);
    }

    RecordTagging::create(['record_id' => $tagged->id, 'user_id' => $itd->id, 'office' => 'ITD', 'tagged_by' => 'x']);
    Access::create(['record_id' => $granted->id, 'office' => 'ITD']);

    $oneByOne = Record::all()->filter->isAccessibleBy($itd)->pluck('id')->sort()->values()->all();
    $asQuery = Record::accessibleBy($itd)->pluck('id')->sort()->values()->all();

    expect($asQuery)->toBe($oneByOne)
        ->and($asQuery)->not->toContain($unrelated->id)
        ->and(count($asQuery))->toBe(6);
});
