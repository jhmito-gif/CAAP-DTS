<?php

use App\Livewire\DocumentExplorer;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\FolderPolicy;
use App\Models\Office;
use App\Models\User;
use App\Support\FolderAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function folder(string $office, string $name, ?DocumentFolder $parent = null): DocumentFolder
{
    return DocumentFolder::create([
        'office' => $office,
        'parent_id' => $parent?->id,
        'name' => $name,
        'created_by' => 'Seed',
    ]);
}

function fileIn(DocumentFolder $folder, string $title = 'plan.pdf'): Document
{
    return Document::create([
        'office' => $folder->office,
        'folder_id' => $folder->id,
        'title' => $title,
        'original_name' => $title,
        'path' => 'documents/' . uniqid() . '.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 64,
        'is_encrypted' => true,
        'uploaded_by' => 'Seed',
    ]);
}

function access(): FolderAccess
{
    return app(FolderAccess::class);
}

it('lets an office use its own folders and keeps other offices out', function () {
    $folder = folder('ITD', 'Contracts');
    $insider = User::factory()->create(['office' => 'ITD']);
    $outsider = User::factory()->create(['office' => 'ODG']);

    expect(access()->level($insider, $folder))->toBe('edit')
        ->and(access()->level($outsider, $folder))->toBe('none');
});

it('shares a folder with another office, and everything inside it', function () {
    $contracts = folder('ITD', 'Contracts');
    $signed = folder('ITD', '2026', $contracts);

    FolderPolicy::create([
        'folder_id' => $contracts->id,
        'subject_type' => 'office',
        'subject' => 'ODG',
        'level' => 'view',
    ]);

    $odg = User::factory()->create(['office' => 'ODG']);

    // The grant reaches the folder below it too.
    expect(access()->level($odg, $contracts))->toBe('view')
        ->and(access()->level($odg, $signed))->toBe('view');
});

it('shares with one person, and with everyone', function () {
    $folder = folder('ITD', 'Circulars');
    $person = User::factory()->create(['office' => 'ODG']);
    $other = User::factory()->create(['office' => 'HR']);

    FolderPolicy::create([
        'folder_id' => $folder->id,
        'subject_type' => 'user',
        'subject' => (string) $person->id,
        'level' => 'edit',
    ]);

    expect(access()->level($person, $folder))->toBe('edit')
        ->and(access()->level($other, $folder))->toBe('none');

    FolderPolicy::create([
        'folder_id' => $folder->id,
        'subject_type' => 'everyone',
        'subject' => null,
        'level' => 'view',
    ]);

    expect(access()->level($other->fresh(), $folder))->toBe('view');
});

it('closes a folder to the office that owns it until someone is named', function () {
    $folder = folder('ITD', 'Investigations');
    $folder->update(['is_restricted' => true]);

    $staff = User::factory()->create(['office' => 'ITD']);
    $named = User::factory()->create(['office' => 'ITD']);

    FolderPolicy::create([
        'folder_id' => $folder->id,
        'subject_type' => 'user',
        'subject' => (string) $named->id,
        'level' => 'view',
    ]);

    expect(access()->level($staff, $folder))->toBe('none')
        ->and(access()->level($named, $folder))->toBe('view');

    // A folder below a closed one is closed as well.
    $inside = folder('ITD', 'Statements', $folder);
    expect(access()->level($staff, $inside))->toBe('none')
        ->and(access()->level($named, $inside))->toBe('view');
});

it('always lets the office document managers and admins in', function () {
    $folder = folder('ITD', 'Investigations');
    $folder->update(['is_restricted' => true]);

    $manager = User::factory()->create(['office' => 'ITD', 'manages_documents' => true]);
    $admin = User::factory()->create(['office' => 'ODG', 'role' => User::ROLE_ADMIN]);

    expect(access()->level($manager, $folder))->toBe('manage')
        ->and(access()->level($admin, $folder))->toBe('manage');
});

it('hides a closed folder and its files from the explorer', function () {
    $open = folder('ITD', 'Open matters');
    $closed = folder('ITD', 'Investigations');
    $closed->update(['is_restricted' => true]);
    fileIn($closed, 'witness-statement.pdf');

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(DocumentExplorer::class)
        ->assertSee('Open matters')
        ->assertDontSee('Investigations')
        // Nor by walking straight into it.
        ->call('open', "library:{$closed->id}")
        ->assertDontSee('witness-statement.pdf');
});

it('refuses the file itself to someone the folder shuts out', function () {
    Storage::fake('local');

    $closed = folder('ITD', 'Investigations');
    $closed->update(['is_restricted' => true]);
    $document = fileIn($closed, 'witness-statement.pdf');

    // Library files are stored encrypted; the controller decrypts on the way out.
    Storage::disk('local')->put($document->path, Crypt::encryptString('%PDF-1.4'));

    $this->actingAs(User::factory()->create(['office' => 'ITD']));
    $this->get(route('documents.view', $document))->assertForbidden();

    // Once shared with them, the same URL opens.
    FolderPolicy::create([
        'folder_id' => $closed->id,
        'subject_type' => 'everyone',
        'subject' => null,
        'level' => 'view',
    ]);

    $this->get(route('documents.view', $document))->assertOk();
});

it('opens a shared folder to another office in their explorer', function () {
    Office::firstOrCreate(['name' => 'ITD'], ['description' => 'Information Technology Department']);
    Office::firstOrCreate(['name' => 'ODG'], ['description' => 'Office of the Director General']);

    $shared = folder('ITD', 'Circulars');
    fileIn($shared, 'circular-01.pdf');

    FolderPolicy::create([
        'folder_id' => $shared->id,
        'subject_type' => 'office',
        'subject' => 'ODG',
        'level' => 'view',
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(DocumentExplorer::class)
        ->set('office', 'ITD')
        ->call('open', "library:{$shared->id}")
        ->assertSee('circular-01.pdf');
});

it('lets a manager share a folder and take it back', function () {
    Office::firstOrCreate(['name' => 'ODG'], ['description' => 'Office of the Director General']);

    $folder = folder('ITD', 'Circulars');
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'manages_documents' => true]));

    $component = Livewire::test(DocumentExplorer::class)
        ->call('shareFolder', $folder->id)
        ->set('shareSubjectType', 'office')
        ->set('shareSubject', 'ODG')
        ->set('shareLevel', 'view')
        ->call('addPolicy');

    $policy = FolderPolicy::where('folder_id', $folder->id)->firstOrFail();
    expect($policy->subject)->toBe('ODG')->and($policy->level)->toBe('view');

    $component->call('toggleRestricted');
    expect($folder->fresh()->is_restricted)->toBeTrue();

    $component->call('removePolicy', $policy->id);
    expect(FolderPolicy::count())->toBe(0);
});

it('will not let an ordinary member share a folder', function () {
    $folder = folder('ITD', 'Circulars');
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'manages_documents' => false]));

    Livewire::test(DocumentExplorer::class)
        ->call('shareFolder', $folder->id)
        ->assertSet('sharingFolderId', 0)
        ->set('shareSubject', 'ODG')
        ->call('addPolicy');

    expect(FolderPolicy::count())->toBe(0);
});

it('lets someone granted manage reshape that folder without managing the library', function () {
    $folder = folder('ITD', 'Joint project');
    $guest = User::factory()->create(['office' => 'ODG']);

    FolderPolicy::create([
        'folder_id' => $folder->id,
        'subject_type' => 'user',
        'subject' => (string) $guest->id,
        'level' => 'manage',
    ]);

    $this->actingAs($guest);

    Livewire::test(DocumentExplorer::class)
        ->set('office', 'ITD')
        ->call('open', "library:{$folder->id}")
        ->set('newFolderName', 'Minutes')
        ->call('createFolder');

    expect(DocumentFolder::where('name', 'Minutes')->where('parent_id', $folder->id)->exists())->toBeTrue();

    // But not the rest of ITD's library.
    Livewire::test(DocumentExplorer::class)
        ->set('office', 'ITD')
        ->call('open', 'library')
        ->set('newFolderName', 'Sneaky')
        ->call('createFolder');

    expect(DocumentFolder::where('name', 'Sneaky')->exists())->toBeFalse();
});
