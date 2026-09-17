<?php

use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentText;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function recordWithFile(bool $confidential = false): Attachment
{
    $record = Record::create([
        'reference' => 'ITD-2026-0303',
        'subject' => 'Runway lighting inspection',
        'created_by' => 'ITD Staff',
        'origin' => 'ITD',
        'owner' => 'ITD',
        'is_confidential' => $confidential,
    ]);

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'For Action',
        'destination' => 'ODG',
        'office' => 'ITD',
        'forwarded_by' => 'ITD Staff',
    ]);

    return Attachment::create([
        'record_id' => $record->id,
        'original_name' => 'inspection-report.pdf',
        'path' => 'attachments/report.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 250000,
        'uploaded_by' => 'ITD Staff',
        'is_encrypted' => true,
        'is_confidential' => $confidential,
    ]);
}

it('tells you everything about a record file', function () {
    Storage::fake('local');

    $attachment = recordWithFile();

    DocumentText::where('source_type', 'attachment')->where('source_id', $attachment->id)->update([
        'status' => 'done', 'method' => 'ocr', 'pages' => 3, 'characters' => 4200, 'read_at' => now(),
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $response = $this->getJson(route('documents.info', ['key' => "attachment-{$attachment->id}"]))->assertOk();

    $facts = collect($response->json('facts'))->pluck('value', 'label');

    expect($response->json('name'))->toBe('inspection-report.pdf')
        ->and($response->json('kind'))->toBe('Record file')
        ->and($facts['Record'])->toBe('ITD-2026-0303')
        ->and($facts['Subject'])->toBe('Runway lighting inspection')
        ->and($facts['Origin'])->toBe('ITD')
        ->and($facts['Uploaded by'])->toBe('ITD Staff')
        ->and($facts['Size'])->toContain('KB')
        ->and($response->json('reading.summary'))->toContain('Read by ocr')
        ->and($response->json('reading.summary'))->toContain('3 page(s)')
        ->and($response->json('record_url'))->not->toBeNull();
});

it('tells you everything about a library file', function () {
    Storage::fake('local');

    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Reports', 'created_by' => 'x']);

    $document = Document::create([
        'office' => 'ITD',
        'folder_id' => $folder->id,
        'title' => 'Annual report.pdf',
        'description' => 'The 2026 annual report.',
        'original_name' => 'annual-2026.pdf',
        'path' => 'documents/annual.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 100000,
        'is_encrypted' => true,
        'uploaded_by' => 'ITD Staff',
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $response = $this->getJson(route('documents.info', ['key' => "document-{$document->id}"]))->assertOk();
    $facts = collect($response->json('facts'))->pluck('value', 'label');

    expect($response->json('kind'))->toBe('Library file')
        ->and($facts['Folder'])->toBe('/Reports')
        ->and($facts['File name'])->toBe('annual-2026.pdf')
        ->and($facts['Description'])->toBe('The 2026 annual report.')
        ->and($response->json('reading.summary'))->toContain('Waiting to be read');
});

it('says nothing about a file the person cannot open', function () {
    Storage::fake('local');

    $attachment = recordWithFile();

    // An office with no link to the record at all.
    $this->actingAs(User::factory()->create(['office' => 'HR']));

    $this->getJson(route('documents.info', ['key' => "attachment-{$attachment->id}"]))
        ->assertForbidden();
});

it('withholds the details of a confidential file from someone not cleared', function () {
    Storage::fake('local');

    $attachment = recordWithFile(confidential: true);

    DocumentText::where('source_type', 'attachment')->where('source_id', $attachment->id)->update([
        'status' => 'done', 'method' => 'text layer', 'pages' => 2, 'characters' => 900, 'read_at' => now(),
    ]);

    // ODG is in the routing, so the record is reachable -- its contents are not.
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    $response = $this->getJson(route('documents.info', ['key' => "attachment-{$attachment->id}"]))->assertOk();
    $facts = collect($response->json('facts'))->pluck('value', 'label');

    expect($response->json('name'))->toBe('Confidential file')
        ->and($facts['Subject'])->toBe('Confidential — hidden from you')
        ->and($facts['Handling'])->toBe('Confidential — view only')
        // Not even how much there is to read.
        ->and($response->json('reading.summary'))->toBe('Read, but withheld from you.')
        ->and($response->json('download_url'))->toBeNull();
});

it('keeps a closed folder\'s file to itself', function () {
    Storage::fake('local');

    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Investigations', 'created_by' => 'x']);
    $folder->update(['is_restricted' => true]);

    $document = Document::create([
        'office' => 'ITD', 'folder_id' => $folder->id, 'title' => 'statement.pdf',
        'original_name' => 'statement.pdf', 'path' => 'documents/s.pdf', 'disk' => 'local',
        'mime_type' => 'application/pdf', 'size' => 10, 'is_encrypted' => true, 'uploaded_by' => 'x',
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $this->getJson(route('documents.info', ['key' => "document-{$document->id}"]))->assertForbidden();
});

it('refuses a key that is not a file', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $this->getJson(route('documents.info', ['key' => 'folder-3']))->assertStatus(422);
    $this->getJson(route('documents.info', ['key' => 'attachment-nope']))->assertStatus(422);
});
