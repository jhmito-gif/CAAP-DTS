<?php

use App\Http\Controllers\ChunkedUploadController;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentText;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** Sends a file the way the browser does: in pieces, then a call to finish. */
function sendInPieces(string $bytes, string $name, array $finish = [], int $pieceSize = 1024): array
{
    $upload = (string) Str::uuid();
    $pieces = str_split($bytes, $pieceSize);
    $sent = [];

    foreach ($pieces as $index => $piece) {
        $path = sys_get_temp_dir() . '/' . uniqid('piece-', true);
        file_put_contents($path, $piece);

        $sent[] = test()->post(route('documents.upload.chunk'), [
            'upload' => $upload,
            'index' => $index,
            'total' => count($pieces),
            'chunk' => new UploadedFile($path, (string) $index, 'application/octet-stream', null, true),
        ]);
    }

    $response = test()->postJson(route('documents.upload.finish'), array_merge([
        'upload' => $upload,
        'name' => $name,
        'office' => 'ITD',
    ], $finish));

    return ['pieces' => $sent, 'finish' => $response, 'upload' => $upload];
}

function samplePdf(string $body = 'The airfield lighting inspection is due in October.'): string
{
    return Pdf::loadHTML("<h1>Memorandum</h1><p>{$body}</p>")->setPaper('a4')->output();
}

it('takes a file in pieces, puts it together and stores it', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $pdf = samplePdf();
    $result = sendInPieces($pdf, 'memo.pdf');

    foreach ($result['pieces'] as $piece) {
        $piece->assertOk();
    }

    $result['finish']->assertOk();

    $document = Document::where('original_name', 'memo.pdf')->first();

    expect($document)->not->toBeNull()
        ->and($document->office)->toBe('ITD')
        // Put together byte for byte.
        ->and($document->contents())->toBe($pdf);
});

it('reads the file for searching straight away', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $result = sendInPieces(samplePdf('The airfield lighting inspection is due in October.'), 'memo.pdf');

    $result['finish']->assertOk()
        ->assertJsonPath('read.status', 'done')
        ->assertJsonPath('read.method', 'text layer');

    $document = Document::where('original_name', 'memo.pdf')->firstOrFail();
    $text = DocumentText::where('source_type', 'document')->where('source_id', $document->id)->firstOrFail();

    expect($text->status)->toBe('done')
        ->and($text->text)->toContain('airfield lighting');
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('clears the pieces away once the file is assembled', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $result = sendInPieces(samplePdf(), 'memo.pdf');
    $result['finish']->assertOk();

    expect(Storage::disk('local')->allFiles('chunk-uploads'))->toBeEmpty();
});

it('files the upload into the folder it was dropped on', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'manages_documents' => true]));

    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Inspections', 'created_by' => 'x']);

    sendInPieces(samplePdf(), 'memo.pdf', ['folder' => $folder->id])['finish']->assertOk();

    expect(Document::where('original_name', 'memo.pdf')->first()->folder_id)->toBe($folder->id);
});

it('refuses a folder the person may not add to', function () {
    Storage::fake('local');

    $closed = DocumentFolder::create(['office' => 'ITD', 'name' => 'Investigations', 'created_by' => 'x']);
    $closed->update(['is_restricted' => true]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    sendInPieces(samplePdf(), 'memo.pdf', ['folder' => $closed->id])['finish']->assertForbidden();

    expect(Document::count())->toBe(0);
});

it('refuses another office\'s library', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    sendInPieces(samplePdf(), 'memo.pdf')['finish']->assertForbidden();

    expect(Document::count())->toBe(0);
});

it('refuses a kind of file that is not allowed', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    sendInPieces('MZ not really a program', 'trojan.exe')['finish']->assertStatus(422);

    expect(Document::count())->toBe(0);
});

it('refuses a file that is not what its name says', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    // Named .pdf, but the bytes are a PNG.
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    sendInPieces($png, 'pretend.pdf')['finish']->assertStatus(422);

    expect(Document::count())->toBe(0);
});

it('will not let one person collect another\'s pieces', function () {
    Storage::fake('local');

    $first = User::factory()->create(['office' => 'ITD']);
    $second = User::factory()->create(['office' => 'ITD']);

    $upload = (string) Str::uuid();
    $path = sys_get_temp_dir() . '/' . uniqid('piece-', true);
    file_put_contents($path, samplePdf());

    $this->actingAs($first)->post(route('documents.upload.chunk'), [
        'upload' => $upload,
        'index' => 0,
        'total' => 1,
        'chunk' => new UploadedFile($path, '0', 'application/octet-stream', null, true),
    ])->assertOk();

    // The pieces are kept per person, so this finds nothing to assemble.
    $this->actingAs($second)->postJson(route('documents.upload.finish'), [
        'upload' => $upload,
        'name' => 'memo.pdf',
        'office' => 'ITD',
    ])->assertStatus(422);

    expect(Document::count())->toBe(0);
});

it('needs someone signed in', function () {
    Storage::fake('local');

    $this->postJson(route('documents.upload.finish'), [
        'upload' => (string) Str::uuid(),
        'name' => 'memo.pdf',
        'office' => 'ITD',
    ])->assertUnauthorized();
});

it('sweeps up pieces of uploads that were never finished', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Storage::disk('local')->put('chunk-uploads/1/abandoned/00000', 'a piece');

    // Nothing is old enough yet.
    $this->artisan('documents:purge-chunks')->assertSuccessful();
    expect(Storage::disk('local')->exists('chunk-uploads/1/abandoned/00000'))->toBeTrue();

    // A day later, it is swept up.
    $this->travel(25)->hours();

    $this->artisan('documents:purge-chunks')->assertSuccessful();
    expect(Storage::disk('local')->exists('chunk-uploads/1/abandoned/00000'))->toBeFalse();
});
