<?php

use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function pageDocument(string $office = 'ITD', ?DocumentFolder $folder = null): Document
{
    return Document::create([
        'office' => $office,
        'folder_id' => $folder?->id,
        'title' => 'Annual report.pdf',
        'original_name' => 'annual-2026.pdf',
        'path' => 'documents/annual.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 120000,
        'is_encrypted' => true,
        'uploaded_by' => 'ITD Staff',
    ]);
}

it('gives a file a page of its own', function () {
    Storage::fake('local');

    $document = pageDocument();
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $this->get(route('files.show', ['key' => "document-{$document->id}"]))
        ->assertOk()
        ->assertSee('Annual report.pdf')
        ->assertSee('Library file')
        // The details are on the page itself, not fetched afterwards.
        ->assertSee('Uploaded by')
        ->assertSee('ITD Staff')
        ->assertSee('Searching')
        // And the floating window is offered on top of it.
        ->assertSee('Open in a floating window');
});

it('opens a record file on its own page, with its record to hand', function () {
    Storage::fake('local');

    $record = Record::create([
        'reference' => 'ITD-2026-0909', 'subject' => 'Runway lighting', 'created_by' => 'ITD Staff',
        'origin' => 'ITD', 'owner' => 'ITD',
    ]);

    Transaction::create([
        'record_id' => $record->id, 'internal_reference' => $record->reference, 'remarks' => 'x',
        'status' => 'For Action', 'destination' => 'ODG', 'office' => 'ITD', 'forwarded_by' => 'ITD Staff',
    ]);

    $attachment = Attachment::create([
        'record_id' => $record->id, 'original_name' => 'report.pdf', 'path' => 'attachments/report.pdf',
        'disk' => 'local', 'mime_type' => 'application/pdf', 'size' => 1000, 'uploaded_by' => 'ITD Staff', 'is_encrypted' => true,
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $this->get(route('files.show', ['key' => "attachment-{$attachment->id}"]))
        ->assertOk()
        ->assertSee('report.pdf')
        ->assertSee('ITD-2026-0909')
        ->assertSee('Open the record');
});

it('refuses the page for a file that is not yours to open', function () {
    Storage::fake('local');

    $document = pageDocument('ITD');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    $this->get(route('files.show', ['key' => "document-{$document->id}"]))->assertForbidden();
});

it('refuses the page for a file in a closed folder', function () {
    Storage::fake('local');

    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Investigations', 'created_by' => 'x']);
    $folder->update(['is_restricted' => true]);

    $document = pageDocument('ITD', $folder);
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $this->get(route('files.show', ['key' => "document-{$document->id}"]))->assertForbidden();
});

it('will not take a key that is not a file', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $this->get('/files/folder-3')->assertNotFound();
    $this->get('/files/document-abc')->assertNotFound();
});

it('needs someone signed in', function () {
    Storage::fake('local');

    $document = pageDocument();

    $this->get(route('files.show', ['key' => "document-{$document->id}"]))
        ->assertRedirect(route('login'));
});
