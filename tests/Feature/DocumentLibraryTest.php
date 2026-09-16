<?php

use App\Filament\Resources\DocumentCategoryResource;
use App\Livewire\DocumentLibrary;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Office;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    foreach (['ITD', 'HR', 'CPO'] as $name) {
        Office::create(['name' => $name, 'description' => "{$name} office"]);
    }
});

function libraryRecord(string $owner, string $reference, array $attributes = []): Record
{
    return Record::create($attributes + [
        'reference' => $reference,
        'subject' => "Subject of {$reference}",
        'created_by' => "{$owner} Person",
        'origin' => $owner,
        'owner' => $owner,
    ]);
}

function libraryRoute(Record $record, string $from, string $to): Transaction
{
    return Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'For Action',
        'destination' => $to,
        'office' => $from,
        'forwarded_by' => "{$from} Person",
    ]);
}

function libraryAttachment(Record $record, string $name): Attachment
{
    return Attachment::storeEncrypted($record, null, UploadedFile::fake()->createWithContent($name, "contents of {$name}"), 'Uploader Person');
}

function libraryDocument(User $user, string $name, array $details = []): Document
{
    return Document::storeEncrypted(
        UploadedFile::fake()->createWithContent($name, "library copy of {$name}"),
        $user,
        $details + ['title' => pathinfo($name, PATHINFO_FILENAME)]
    );
}

it('lists the files a user may open, following the record access rules', function () {
    $itd = User::factory()->create(['office' => 'ITD']);
    $hr = User::factory()->create(['office' => 'HR']);

    libraryAttachment(libraryRecord('ITD', 'ITD-2026-0001'), 'own-office.pdf');

    $routed = libraryRecord('HR', 'HR-2026-0001');
    libraryRoute($routed, 'HR', 'ITD');
    libraryAttachment($routed, 'routed-in.pdf');

    $tagged = libraryRecord('HR', 'HR-2026-0002');
    RecordTagging::create(['record_id' => $tagged->id, 'user_id' => $itd->id, 'office' => 'ITD', 'tagged_by' => 'HR Person']);
    libraryAttachment($tagged, 'tagged-for-me.pdf');

    $granted = libraryRecord('CPO', 'CPO-2026-0001');
    DB::table('accesses')->insert(['record_id' => $granted->id, 'office' => 'ITD', 'created_at' => now(), 'updated_at' => now()]);
    libraryAttachment($granted, 'granted-access.pdf');

    libraryAttachment(libraryRecord('CPO', 'CPO-2026-0002'), 'unrelated-office.pdf');

    // Routed to ITD, but confidential: the routing chain is not cleared.
    $confidential = libraryRecord('HR', 'HR-2026-0003', ['is_confidential' => true]);
    libraryRoute($confidential, 'HR', 'ITD');
    libraryAttachment($confidential, 'confidential-routed.pdf');

    libraryDocument($itd, 'itd-handbook.pdf');
    libraryDocument($hr, 'hr-payroll.pdf');

    $this->actingAs($itd);

    Livewire::test(DocumentLibrary::class)
        ->assertSee('own-office.pdf')
        ->assertSee('routed-in.pdf')
        ->assertSee('tagged-for-me.pdf')
        ->assertSee('granted-access.pdf')
        ->assertSee('itd-handbook')
        ->assertDontSee('unrelated-office.pdf')
        ->assertDontSee('confidential-routed.pdf')
        ->assertDontSee('hr-payroll');

    $this->actingAs(User::factory()->create(['office' => 'CPO', 'role' => User::ROLE_ADMIN]));

    Livewire::test(DocumentLibrary::class)
        ->assertSee('unrelated-office.pdf')
        ->assertSee('confidential-routed.pdf')
        ->assertSee('hr-payroll')
        ->assertSee('itd-handbook');
});

it('filters by search, office, category, file type, source, signature status and date', function () {
    $itd = User::factory()->create(['office' => 'ITD']);
    $memos = DocumentCategory::create(['name' => 'Test Memos']);

    $record = libraryRecord('ITD', 'ITD-2026-0100', ['subject' => 'Budget proposal']);
    libraryAttachment($record, 'budget.pdf')->update(['signing_completed_at' => now(), 'document_category_id' => $memos->id]);
    libraryAttachment($record, 'figures.xlsx');

    $hrRecord = libraryRecord('HR', 'HR-2026-0100');
    libraryRoute($hrRecord, 'HR', 'ITD');
    libraryAttachment($hrRecord, 'hr-notice.pdf');

    libraryDocument($itd, 'handbook.pdf', ['document_category_id' => $memos->id]);
    libraryDocument($itd, 'team-photo.png');

    $this->actingAs($itd);

    Livewire::test(DocumentLibrary::class)
        ->set('search', 'figures')
        ->assertSee('figures.xlsx')->assertDontSee('budget.pdf')->assertDontSee('handbook')
        ->set('search', 'proposal')
        ->assertSee('figures.xlsx')->assertSee('budget.pdf')->assertDontSee('hr-notice.pdf')
        ->set('search', '')
        ->set('office', 'HR')
        ->assertSee('hr-notice.pdf')->assertDontSee('budget.pdf')->assertDontSee('handbook')
        ->set('office', '')
        ->set('category', (string) $memos->id)
        ->assertSee('budget.pdf')->assertSee('handbook')->assertDontSee('figures.xlsx')->assertDontSee('team-photo')
        ->set('category', 'none')
        ->assertSee('figures.xlsx')->assertDontSee('handbook')
        ->set('category', '')
        ->set('type', 'spreadsheet')
        ->assertSee('figures.xlsx')->assertDontSee('budget.pdf')
        ->set('type', 'image')
        ->assertSee('team-photo')->assertDontSee('handbook')
        ->set('type', '')
        ->set('source', 'library')
        ->assertSee('handbook')->assertDontSee('budget.pdf')
        ->set('source', 'routed')
        ->assertSee('budget.pdf')->assertDontSee('handbook')
        ->set('source', '')
        ->set('signed', 'signed')
        ->assertSee('budget.pdf')->assertDontSee('figures.xlsx')->assertDontSee('handbook')
        ->set('signed', '')
        ->set('from', now()->addDay()->toDateString())
        ->assertDontSee('budget.pdf')->assertDontSee('handbook')->assertSee('No files match these filters')
        ->call('clearFilters')
        ->assertSet('from', '')
        ->assertSee('budget.pdf')->assertSee('handbook');
});

it('uploads files into the office library, encrypted at rest and visible only to that office', function () {
    $itd = User::factory()->create(['name' => 'ITD Person', 'office' => 'ITD']);
    $category = DocumentCategory::create(['name' => 'Test Policies']);
    // Real PDFs: uploads are type-checked by their contents, not their names.
    $pdf = Pdf::loadHTML('<p>Office policy</p>')->setPaper('a4')->output();

    $this->actingAs($itd);

    Livewire::test(DocumentLibrary::class)
        ->set('uploads', [UploadedFile::fake()->create('virus.exe', 5)])
        ->call('saveUploads')
        ->assertHasErrors(['uploads.0'])
        ->assertSee('virus.exe')
        // New picks are added to the pending list, so the rejected file is removed first.
        ->call('discardUpload', 0)
        ->assertSet('uploads', [])
        ->set('uploads', [UploadedFile::fake()->createWithContent('policy.pdf', $pdf)])
        ->set('uploadTitle', 'Office Policy 2026')
        ->set('uploadCategory', (string) $category->id)
        ->set('uploadDescription', 'Approved policy')
        ->call('saveUploads')
        ->assertHasNoErrors()
        ->assertDispatched('close-document-upload')
        ->assertSet('uploads', [])
        ->assertSee('Office Policy 2026')
        ->set('uploads', [
            UploadedFile::fake()->createWithContent('minutes.pdf', Pdf::loadHTML('<p>Minutes</p>')->output()),
            UploadedFile::fake()->image('agenda.png'),
        ])
        ->set('uploadTitle', 'Ignored for several files')
        ->call('saveUploads')
        ->assertHasNoErrors();

    expect(Document::pluck('title')->sort()->values()->all())->toBe(['Office Policy 2026', 'agenda', 'minutes']);

    $document = Document::where('title', 'Office Policy 2026')->sole();

    expect($document->office)->toBe('ITD')
        ->and($document->document_category_id)->toBe($category->id)
        ->and($document->description)->toBe('Approved policy')
        ->and($document->uploaded_by)->toBe('ITD Person')
        ->and($document->contents())->toBe($pdf)
        ->and(Storage::disk('local')->get($document->path))->not->toContain('%PDF');

    $this->get(route('documents.view', $document))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->get(route('documents.download', $document))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="policy.pdf"');

    $this->actingAs(User::factory()->create(['office' => 'HR']))
        ->get(route('documents.view', $document))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['office' => 'CPO', 'role' => User::ROLE_ADMIN]))
        ->get(route('documents.download', $document))
        ->assertOk();
});

it('lets only the owning office edit and delete library documents', function () {
    $itd = User::factory()->create(['office' => 'ITD']);
    $document = libraryDocument($itd, 'draft.pdf');
    $category = DocumentCategory::create(['name' => 'Test Drafts']);

    $this->actingAs(User::factory()->create(['office' => 'HR']));

    Livewire::test(DocumentLibrary::class)
        ->call('editItem', 'document', $document->id)
        ->assertNotDispatched('open-document-edit')
        ->call('deleteDocument', $document->id);

    expect(Document::find($document->id))->not->toBeNull();

    $this->actingAs($itd);

    Livewire::test(DocumentLibrary::class)
        ->call('editItem', 'document', $document->id)
        ->assertDispatched('open-document-edit')
        ->assertSet('editTitle', 'draft')
        ->set('editTitle', '')
        ->call('saveEdit')
        ->assertHasErrors(['editTitle'])
        ->set('editTitle', 'Final draft')
        ->set('editCategory', (string) $category->id)
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSee('Final draft');

    expect($document->fresh())
        ->title->toBe('Final draft')
        ->document_category_id->toBe($category->id);

    Livewire::test(DocumentLibrary::class)->call('deleteDocument', $document->id);

    expect(Document::find($document->id))->toBeNull();
    Storage::disk('local')->assertMissing($document->path);
});

it('lets the managing office categorise routed files, and admins open any routed file', function () {
    $category = DocumentCategory::create(['name' => 'Test Letters']);
    $record = libraryRecord('ITD', 'ITD-2026-0200');
    libraryRoute($record, 'ITD', 'HR');
    $attachment = libraryAttachment($record, 'letter.pdf');

    $this->actingAs(User::factory()->create(['office' => 'HR']));

    Livewire::test(DocumentLibrary::class)
        ->assertSee('letter.pdf')
        ->call('editItem', 'attachment', $attachment->id)
        ->assertNotDispatched('open-document-edit');

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(DocumentLibrary::class)
        ->call('editItem', 'attachment', $attachment->id)
        ->assertDispatched('open-document-edit')
        ->set('editCategory', (string) $category->id)
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSee('Test Letters');

    expect($attachment->fresh()->document_category_id)->toBe($category->id);

    $this->actingAs(User::factory()->create(['office' => 'CPO', 'role' => User::ROLE_ADMIN]))
        ->get(route('attachments.view', $attachment))
        ->assertOk();

    $category->delete();

    expect($attachment->fresh()->document_category_id)->toBeNull();
});

it('adds a Documents page to the navigation', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']))
        ->get(route('documents.index'))
        ->assertOk()
        ->assertSee('Upload')
        ->assertSee(route('documents.index'), false);
});

it('lets admins manage document categories in the admin panel', function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
        ->get(DocumentCategoryResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Memorandum');

    $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
        ->get(DocumentCategoryResource::getUrl('index'))
        ->assertForbidden();
});
