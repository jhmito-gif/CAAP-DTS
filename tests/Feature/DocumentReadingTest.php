<?php

use App\Livewire\DocumentExplorer;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentText;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DocumentReader;
use App\Support\DocumentSearch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** A real PDF with a text layer, saved into an office's library. */
function readableDocument(string $office, string $body, string $title = 'memo.pdf', ?DocumentFolder $folder = null): Document
{
    $pdf = Pdf::loadHTML("<h1>Memorandum</h1><p>{$body}</p>")->setPaper('a4')->output();
    $path = sys_get_temp_dir() . '/' . uniqid('read-', true) . '.pdf';
    file_put_contents($path, $pdf);

    $user = User::factory()->create(['office' => $office, 'name' => 'Uploader']);

    $document = Document::storeEncrypted(
        new UploadedFile($path, $title, 'application/pdf', null, true),
        $user,
        ['title' => $title, 'folder_id' => $folder?->id],
    );

    @unlink($path);

    return $document;
}

it('queues a file to be read as soon as it is stored', function () {
    Storage::fake('local');

    $document = readableDocument('ITD', 'Nothing in particular.');

    $row = DocumentText::where('source_type', 'document')->where('source_id', $document->id)->first();

    expect($row)->not->toBeNull()->and($row->status)->toBe('pending');
});

it('reads the text out of a PDF and can then find it by its contents', function () {
    Storage::fake('local');

    $document = readableDocument('ITD', 'The runway lighting system at Iloilo Airport needs replacement bulbs.');

    $row = DocumentText::where('source_id', $document->id)->firstOrFail();
    app(DocumentReader::class)->read($row);
    $row->refresh();

    expect($row->status)->toBe('done')
        ->and($row->method)->toBe('text layer')
        ->and($row->characters)->toBeGreaterThan(20)
        ->and($row->text)->toContain('runway lighting');

    $searcher = User::factory()->create(['office' => 'ITD']);
    $hits = app(DocumentSearch::class)->find($searcher, 'runway lighting');

    expect($hits)->toHaveCount(1)
        ->and($hits->first()->name)->toBe('memo.pdf')
        ->and($hits->first()->snippet)->toContain('runway lighting');
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('does not find what the searcher could not open', function () {
    Storage::fake('local');

    $document = readableDocument('ITD', 'The runway lighting system at Iloilo Airport needs replacement bulbs.');
    app(DocumentReader::class)->read(DocumentText::where('source_id', $document->id)->firstOrFail());

    // Another office: the file is theirs to neither browse nor find.
    $outsider = User::factory()->create(['office' => 'ODG']);

    expect(app(DocumentSearch::class)->find($outsider, 'runway lighting'))->toHaveCount(0);
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('keeps a closed folder out of the search results', function () {
    Storage::fake('local');

    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Investigations', 'created_by' => 'x']);
    $folder->update(['is_restricted' => true]);

    $document = readableDocument('ITD', 'Statement taken from the witness about the incident.', 'statement.pdf', $folder);
    app(DocumentReader::class)->read(DocumentText::where('source_id', $document->id)->firstOrFail());

    $staff = User::factory()->create(['office' => 'ITD']);
    $manager = User::factory()->create(['office' => 'ITD', 'manages_documents' => true]);

    expect(app(DocumentSearch::class)->find($staff, 'witness'))->toHaveCount(0)
        ->and(app(DocumentSearch::class)->find($manager, 'witness'))->toHaveCount(1);
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('does not quote a confidential record file back to someone not cleared for it', function () {
    Storage::fake('local');

    $record = Record::create([
        'reference' => 'ITD-2026-0900', 'subject' => 'Complaint', 'created_by' => 'ITD Staff',
        'origin' => 'ITD', 'owner' => 'ITD', 'is_confidential' => true,
    ]);

    $transaction = Transaction::create([
        'record_id' => $record->id, 'internal_reference' => $record->reference, 'remarks' => 'x',
        'status' => 'For Action', 'destination' => 'ODG', 'office' => 'ITD', 'forwarded_by' => 'ITD Staff',
    ]);

    $pdf = Pdf::loadHTML('<p>The complaint concerns unauthorised overtime claims.</p>')->setPaper('a4')->output();
    $path = sys_get_temp_dir() . '/' . uniqid('conf-', true) . '.pdf';
    file_put_contents($path, $pdf);

    $attachment = Attachment::storeEncrypted(
        $record,
        $transaction,
        new UploadedFile($path, 'complaint.pdf', 'application/pdf', null, true),
        'ITD Staff',
    );

    @unlink($path);

    app(DocumentReader::class)->read(DocumentText::where('source_type', 'attachment')->where('source_id', $attachment->id)->firstOrFail());

    // ODG is in the routing, so the record is reachable -- but not its details.
    $odg = User::factory()->create(['office' => 'ODG']);
    $hit = app(DocumentSearch::class)->find($odg, 'overtime')->first();

    expect($hit)->not->toBeNull()
        ->and($hit->name)->toBe('Confidential file')
        ->and($hit->snippet)->toBe('Confidential — hidden from you')
        ->and($hit->snippet)->not->toContain('overtime');
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('reads a file on demand from the explorer', function () {
    Storage::fake('local');

    $document = readableDocument('ITD', 'Budget for the replacement of airfield lighting.');
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(DocumentExplorer::class)
        ->call('readNow', "document-{$document->id}");

    expect(DocumentText::where('source_id', $document->id)->first()->status)->toBe('done');
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('will not read a file the person cannot open', function () {
    Storage::fake('local');

    $document = readableDocument('ITD', 'Budget for the replacement of airfield lighting.');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(DocumentExplorer::class)
        ->call('readNow', "document-{$document->id}");

    expect(DocumentText::where('source_id', $document->id)->first()->status)->toBe('pending');
});

it('skips a kind of file it cannot read', function () {
    Storage::fake('local');

    $document = Document::create([
        'office' => 'ITD', 'title' => 'sheet.xlsx', 'original_name' => 'sheet.xlsx',
        'path' => 'documents/sheet.xlsx', 'disk' => 'local',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'size' => 10, 'is_encrypted' => true, 'uploaded_by' => 'x',
    ]);

    // Nothing was queued for it in the first place.
    expect(DocumentText::where('source_id', $document->id)->exists())->toBeFalse();

    $row = DocumentText::create(['source_type' => 'document', 'source_id' => $document->id, 'status' => 'pending']);
    app(DocumentReader::class)->read($row);

    expect($row->fresh()->status)->toBe('skipped');
});

it('forgets the text when the file is deleted', function () {
    Storage::fake('local');

    $document = readableDocument('ITD', 'Something to say.');
    expect(DocumentText::where('source_id', $document->id)->exists())->toBeTrue();

    $document->delete();

    expect(DocumentText::where('source_type', 'document')->where('source_id', $document->id)->exists())->toBeFalse();
});

it('reads queued files from the command line', function () {
    Storage::fake('local');

    readableDocument('ITD', 'The annual inspection is scheduled for October.');

    $this->artisan('documents:read --limit=5')->assertSuccessful();

    expect(DocumentText::where('status', 'done')->count())->toBe(1);
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');
