<?php

use App\Livewire\DocumentExplorer;
use App\Livewire\WorkProgress;
use App\Models\Document;
use App\Models\DocumentText;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function queuedText(string $status, ?int $minutesAgo = null): DocumentText
{
    $document = Document::create([
        'office' => 'ITD',
        'title' => 'file.pdf',
        'original_name' => 'file.pdf',
        'path' => 'documents/' . uniqid() . '.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'is_encrypted' => true,
        'uploaded_by' => 'x',
    ]);

    return DocumentText::updateOrCreate(
        ['source_type' => 'document', 'source_id' => $document->id],
        [
            'status' => $status,
            'read_at' => $status === 'done' ? now()->subMinutes($minutesAgo ?? 0) : null,
        ],
    );
}

it('stays out of the way when there is nothing to report', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(WorkProgress::class)
        ->assertDontSee('Reading documents')
        ->assertDontSee('Documents read');
});

it('shows how far the reading has got', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    queuedText('pending');
    queuedText('pending');
    queuedText('pending');
    queuedText('done');

    Livewire::test(WorkProgress::class)
        ->assertSee('Reading documents')
        ->assertSee('1 of 4 done')
        // Something still to do: it checks back briskly.
        ->assertSee('wire:poll.3s', escape: false);
});

it('says so when a file could not be read', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    queuedText('pending');
    queuedText('failed');

    Livewire::test(WorkProgress::class)->assertSee('could not be read');
});

it('lingers a moment when the last file is done, then goes', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    queuedText('done');

    Livewire::test(WorkProgress::class)
        ->assertSee('Documents read')
        // Nothing left to do: it checks back slowly.
        ->assertSee('wire:poll.20s', escape: false);

    // A while later it is gone, and the counting has moved on.
    $this->travel(30)->seconds();

    Livewire::test(WorkProgress::class)->assertDontSee('Documents read');
});

it('rides along on every page, so progress survives a move', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    queuedText('pending');

    // The bar comes from the layout, so any page carries it.
    foreach ([route('dashboard'), route('documents.index')] as $page) {
        $this->get($page)
            ->assertOk()
            ->assertSee('Reading documents')
            ->assertSee('aria-label="Reading documents"', escape: false);
    }
});

it('lets a document manager queue everything that has never been read', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'manages_documents' => true]));

    $document = Document::create([
        'office' => 'ITD', 'title' => 'unread.pdf', 'original_name' => 'unread.pdf',
        'path' => 'documents/unread.pdf', 'disk' => 'local', 'mime_type' => 'application/pdf',
        'size' => 10, 'is_encrypted' => true, 'uploaded_by' => 'x',
    ]);

    DocumentText::where('source_id', $document->id)->delete();

    Livewire::test(DocumentExplorer::class)->call('readEverything');

    expect(DocumentText::where('source_id', $document->id)->first()?->status)->toBe('pending');
});

it('will not let anyone else start a full read', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'manages_documents' => false]));

    Document::create([
        'office' => 'ITD', 'title' => 'unread.pdf', 'original_name' => 'unread.pdf',
        'path' => 'documents/unread.pdf', 'disk' => 'local', 'mime_type' => 'application/pdf',
        'size' => 10, 'is_encrypted' => true, 'uploaded_by' => 'x',
    ]);

    DocumentText::query()->delete();

    Livewire::test(DocumentExplorer::class)->call('readEverything');

    expect(DocumentText::count())->toBe(0);
});
