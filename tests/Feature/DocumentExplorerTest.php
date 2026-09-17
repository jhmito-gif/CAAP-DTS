<?php

use App\Livewire\DocumentExplorer;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\FolderShortcut;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function documentManager(string $office = 'ITD'): User
{
    return User::factory()->create(['office' => $office, 'manages_documents' => true]);
}

function libraryFile(string $office, ?DocumentFolder $folder = null, string $title = 'Annual report.pdf'): Document
{
    return Document::create([
        'office' => $office,
        'folder_id' => $folder?->id,
        'title' => $title,
        'original_name' => $title,
        'path' => 'documents/' . uniqid() . '.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 2048,
        'is_encrypted' => true,
        'uploaded_by' => 'ITD Staff',
    ]);
}

it('lists folders before files, in the office library', function () {
    $user = documentManager();
    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Zebra folder', 'created_by' => 'ITD Staff']);
    libraryFile('ITD', null, 'Alpha report.pdf');

    $this->actingAs($user);

    Livewire::test(DocumentExplorer::class)
        ->assertSee('Zebra folder')
        ->assertSee('Alpha report.pdf')
        ->assertSeeInOrder(['Zebra folder', 'Alpha report.pdf']);

    expect($folder->path)->toBe('/Zebra folder');
});

it('creates, renames and moves folders, keeping paths in step', function () {
    $this->actingAs(documentManager());

    $component = Livewire::test(DocumentExplorer::class)
        ->set('newFolderName', 'Memoranda')
        ->call('createFolder');

    $memoranda = DocumentFolder::where('name', 'Memoranda')->firstOrFail();
    expect($memoranda->path)->toBe('/Memoranda');

    // A folder made inside it, then the parent renamed: the child follows.
    $component->call('open', "library:{$memoranda->id}")
        ->set('newFolderName', '2026')
        ->call('createFolder');

    $year = DocumentFolder::where('name', '2026')->firstOrFail();
    expect($year->path)->toBe('/Memoranda/2026');

    $component->call('open', 'library')
        ->call('startRename', "folder-{$memoranda->id}")
        ->set('renameValue', 'Memos')
        ->call('saveRename');

    expect($memoranda->fresh()->path)->toBe('/Memos')
        ->and($year->fresh()->path)->toBe('/Memos/2026');
});

it('refuses two folders of the same name in one place', function () {
    $this->actingAs(documentManager());
    DocumentFolder::create(['office' => 'ITD', 'name' => 'Contracts', 'created_by' => 'x']);

    Livewire::test(DocumentExplorer::class)
        ->set('newFolderName', 'Contracts')
        ->call('createFolder')
        ->assertHasErrors('newFolderName');

    expect(DocumentFolder::where('name', 'Contracts')->count())->toBe(1);
});

it('makes an unnamed folder ready to be named, without clashing', function () {
    $this->actingAs(documentManager());

    $component = Livewire::test(DocumentExplorer::class)->call('createFolder');

    $first = DocumentFolder::where('name', 'New folder')->firstOrFail();

    // It opens for renaming, with the new folder selected.
    $component->assertSet('renamingKey', "folder-{$first->id}")
        ->assertSet('renameValue', 'New folder');

    $component->call('cancelRename')->call('createFolder');

    expect(DocumentFolder::where('name', 'New folder (2)')->exists())->toBeTrue();
});

it('moves a library file into a folder by dragging it', function () {
    $this->actingAs(documentManager());

    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Reports', 'created_by' => 'x']);
    $document = libraryFile('ITD');

    Livewire::test(DocumentExplorer::class)
        ->call('moveInto', $folder->id, ["document-{$document->id}"]);

    expect($document->fresh()->folder_id)->toBe($folder->id);
});

it('never moves a folder inside itself', function () {
    $this->actingAs(documentManager());

    $parent = DocumentFolder::create(['office' => 'ITD', 'name' => 'Parent', 'created_by' => 'x']);
    $child = DocumentFolder::create(['office' => 'ITD', 'parent_id' => $parent->id, 'name' => 'Child', 'created_by' => 'x']);

    Livewire::test(DocumentExplorer::class)
        ->call('moveInto', $child->id, ["folder-{$parent->id}"]);

    expect($parent->fresh()->parent_id)->toBeNull()
        ->and($child->fresh()->path)->toBe('/Parent/Child');
});

it('files a record attachment into a folder as a link, leaving the file on its record', function () {
    Storage::fake('local');
    $user = documentManager();

    $record = Record::create([
        'reference' => 'ITD-2026-0001', 'subject' => 'Network upgrade', 'created_by' => 'ITD Staff',
        'origin' => 'ITD', 'owner' => 'ITD',
    ]);

    Transaction::create([
        'record_id' => $record->id, 'internal_reference' => $record->reference, 'remarks' => 'x',
        'status' => 'For Action', 'destination' => 'ODG', 'office' => 'ITD', 'forwarded_by' => 'ITD Staff',
    ]);

    $attachment = Attachment::create([
        'record_id' => $record->id, 'original_name' => 'memo.pdf', 'path' => 'attachments/memo.pdf',
        'disk' => 'local', 'mime_type' => 'application/pdf', 'size' => 10, 'uploaded_by' => 'ITD Staff', 'is_encrypted' => true,
    ]);

    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Filed', 'created_by' => 'x']);

    $this->actingAs($user);

    Livewire::test(DocumentExplorer::class)
        ->call('moveInto', $folder->id, ["attachment-{$attachment->id}"]);

    expect(FolderShortcut::where('folder_id', $folder->id)->where('attachment_id', $attachment->id)->exists())->toBeTrue()
        // The file itself never left its record.
        ->and($attachment->fresh()->record_id)->toBe($record->id);

    // And the shortcut shows up in that folder.
    Livewire::test(DocumentExplorer::class)
        ->call('open', "library:{$folder->id}")
        ->assertSee('memo.pdf');
});

it('keeps someone who does not manage documents from organising', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'manages_documents' => false]));

    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Contracts', 'created_by' => 'x']);
    $document = libraryFile('ITD');

    Livewire::test(DocumentExplorer::class)
        ->set('newFolderName', 'Mine')
        ->call('createFolder')
        ->call('moveInto', $folder->id, ["document-{$document->id}"]);

    expect(DocumentFolder::where('name', 'Mine')->exists())->toBeFalse()
        ->and($document->fresh()->folder_id)->toBeNull();
});

it('still offers uploading to any office member, and lets them delete what is theirs', function () {
    Storage::fake('local');

    // Not a document manager: they may add files, just not reshape the tree.
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'manages_documents' => false]));

    $document = libraryFile('ITD', null, 'minutes.pdf');

    Livewire::test(DocumentExplorer::class)
        ->assertViewHas('canUpload', true)
        ->assertViewHas('canOrganise', false)
        ->set('selected', ["document-{$document->id}"])
        ->call('deleteSelected');

    expect(Document::whereKey($document->id)->exists())->toBeFalse();
});

it('does not offer uploading into another office’s library', function () {
    $this->actingAs(User::factory()->create(['office' => 'ODG', 'manages_documents' => true]));

    Livewire::test(DocumentExplorer::class)
        ->set('office', 'ITD')
        ->assertViewHas('canUpload', false);
});

it('appoints and stands down a document manager from the command line', function () {
    $user = User::factory()->create(['office' => 'ITD', 'email' => 'chief@caap.gov.ph', 'manages_documents' => false]);

    $this->artisan('documents:manager chief@caap.gov.ph')->assertSuccessful();
    expect($user->fresh()->manages_documents)->toBeTrue();

    $this->artisan('documents:manager chief@caap.gov.ph --revoke')->assertSuccessful();
    expect($user->fresh()->manages_documents)->toBeFalse();

    $this->artisan('documents:manager nobody@caap.gov.ph')->assertFailed();
});

it('will not delete a folder with things still in it', function () {
    $this->actingAs(documentManager());

    $folder = DocumentFolder::create(['office' => 'ITD', 'name' => 'Busy', 'created_by' => 'x']);
    libraryFile('ITD', $folder);

    Livewire::test(DocumentExplorer::class)
        ->set('selected', ["folder-{$folder->id}"])
        ->call('deleteSelected');

    expect(DocumentFolder::whereKey($folder->id)->exists())->toBeTrue();
});

it('puts the floating viewer on the documents page', function () {
    $this->actingAs(documentManager());

    $this->get(route('documents.index'))
        ->assertOk()
        // The window shell, and the script that drives it.
        ->assertSee('data-viewer-window', escape: false)
        ->assertSee('resources/js/viewer.js', escape: false);
});

it('shows another office nothing of this one', function () {
    documentManager();
    DocumentFolder::create(['office' => 'ITD', 'name' => 'ITD Only', 'created_by' => 'x']);

    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(DocumentExplorer::class)->assertDontSee('ITD Only');
});
