<?php

use App\Filament\Resources\ModuleSettingResource\Pages\ListModuleSettings;
use App\Livewire\CreateOutgoing;
use App\Livewire\DocumentExplorer;
use App\Livewire\InternalTrail;
use App\Livewire\OutgoingTable;
use App\Livewire\WorkProgress;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentText;
use App\Models\InternalRouting;
use App\Models\ModuleSetting;
use App\Models\Office;
use App\Models\Record;
use App\Models\SignatureRequest;
use App\Models\Status;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DocumentHandling;
use App\Support\DocumentSearch;
use App\Support\Modules;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
});

/** A record routed ITD → ODG, with one PDF attached. */
function moduleRecord(string $reference = 'ITD-2026-0400'): array
{
    $record = Record::create([
        'reference' => $reference,
        'subject' => 'Runway lighting repairs',
        'created_by' => 'ITD Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    $transaction = Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'For Action',
        'destination' => 'ODG',
        'office' => 'ITD',
        'forwarded_by' => 'ITD Person',
    ]);

    $attachment = Attachment::storeEncrypted(
        $record,
        $transaction,
        UploadedFile::fake()->createWithContent('memo.pdf', '%PDF-1.4 memo'),
        'ITD Person'
    );

    return [$record, $attachment];
}

/*
|--------------------------------------------------------------------------
| The switches themselves
|--------------------------------------------------------------------------
*/
it('starts with every module switched on', function () {
    foreach (array_keys(Modules::CATALOGUE) as $module) {
        expect(Modules::enabled($module))->toBeTrue();
    }

    expect(ModuleSetting::count())->toBe(count(Modules::CATALOGUE));
});

it('keeps the core of the system on, whatever is asked', function () {
    expect(Modules::enabled('incoming'))->toBeTrue()
        ->and(Modules::enabled('outgoing'))->toBeTrue()
        ->and(fn () => Modules::set('outgoing', false))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('sees a change straight away, cache and all', function () {
    expect(Modules::enabled(Modules::CHAT))->toBeTrue();

    Modules::set(Modules::CHAT, false, 'Admin');
    expect(Modules::enabled(Modules::CHAT))->toBeFalse();

    Modules::set(Modules::CHAT, true, 'Admin');
    expect(Modules::enabled(Modules::CHAT))->toBeTrue();
});

it('lets an admin switch a module from the admin panel, and records who', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'office' => 'ITD', 'name' => 'Admin Person']);
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $chat = ModuleSetting::where('module', Modules::CHAT)->firstOrFail();

    Livewire::test(ListModuleSettings::class)
        ->assertSee('Chat')
        ->assertSee('Nothing else depends on chat')
        ->call('updateTableColumnState', 'enabled', (string) $chat->getKey(), false);

    expect(Modules::enabled(Modules::CHAT))->toBeFalse()
        ->and($chat->fresh()->updated_by)->toBe('Admin Person');
});

it('keeps the switches from anyone who is not an admin', function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_USER, 'office' => 'ITD']));

    $this->get('/admin/module-settings')->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| A switched-off module goes
|--------------------------------------------------------------------------
*/
it('answers "turned off" for a switched-off module\'s pages', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Modules::set(Modules::DOCUMENTS, false);
    $this->get(route('documents.index'))->assertNotFound()->assertSee('Document library is turned off');

    Modules::set(Modules::ESIGN, false);
    $this->get(route('esign.queue'))->assertNotFound()->assertSee('E-signatures is turned off');
    $this->get(route('esign.verify'))->assertNotFound();

    // Nothing is deleted: switched back on, it is there again.
    Modules::set(Modules::DOCUMENTS, true);
    $this->get(route('documents.index'))->assertOk();
});

it('takes a switched-off module out of the menu', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $this->get(route('dashboard'))->assertSee('Documents for Signature');

    Modules::set(Modules::DOCUMENTS, false);
    Modules::set(Modules::ESIGN, false);
    Modules::set(Modules::CHAT, false);

    $page = $this->get(route('dashboard'))->assertOk();

    $page->assertDontSee('Documents for Signature')
        ->assertDontSee(route('documents.index'), escape: false)
        ->assertDontSee('wire:name="chat-box"', escape: false);

    // The core stays.
    $page->assertSee(route('incoming-record'), escape: false)
        ->assertSee(route('outgoing-record'), escape: false);
});

/*
|--------------------------------------------------------------------------
| ...and nothing else breaks
|--------------------------------------------------------------------------
*/
it('lets a document waiting for a signature move on while signing is off', function () {
    [$record, $attachment] = moduleRecord();
    $signer = User::factory()->create(['office' => 'ITD']);

    SignatureRequest::create([
        'attachment_id' => $attachment->id,
        'record_id' => $record->id,
        'signer_id' => $signer->id,
    ]);

    // Signing on: the request holds the document back, as designed.
    expect(DocumentHandling::sendBlocker($record, $signer))->toContain('Sign the document')
        ->and(DocumentHandling::passBlocker($record, $signer))->toContain('Sign the document');

    // Signing off: nobody could sign, so the hold must not freeze the routing.
    Modules::set(Modules::ESIGN, false);

    expect(DocumentHandling::sendBlocker($record, $signer))->toBeNull()
        ->and(DocumentHandling::passBlocker($record, $signer))->toBeNull();

    // The request is kept, and holds again once signing is back.
    Modules::set(Modules::ESIGN, true);

    expect(SignatureRequest::count())->toBe(1)
        ->and(DocumentHandling::sendBlocker($record, $signer))->toContain('Sign the document');
});

it('just accepts a handoff, rather than opening a signing page that is not there', function () {
    [$record, $attachment] = moduleRecord();
    $clerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $chief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);

    SignatureRequest::create(['attachment_id' => $attachment->id, 'record_id' => $record->id, 'signer_id' => $chief->id]);

    $entry = InternalRouting::create([
        'record_id' => $record->id, 'office' => 'ITD',
        'from_user_id' => $clerk->id, 'from_name' => 'ITD Clerk',
        'to_user_id' => $chief->id, 'to_name' => 'ITD Chief',
        'action' => 'For e-signature',
    ]);

    Modules::set(Modules::ESIGN, false);
    $this->actingAs($chief);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->assertDontSee('Accept & sign')
        ->call('acknowledge', $entry->id)
        ->assertNoRedirect();

    expect($entry->fresh()->received_at)->not->toBeNull();
});

it('still sends an outgoing record while signing is off, without asking for signatures', function () {
    Office::create(['name' => 'ITD', 'description' => 'ITD office']);
    Office::create(['name' => 'HR', 'description' => 'HR office']);
    Status::create(['name' => 'Pending']);

    $creator = User::factory()->create(['office' => 'ITD']);
    $signer = User::factory()->create(['office' => 'HR', 'name' => 'Signer Alpha']);

    Modules::set(Modules::ESIGN, false);
    $this->actingAs($creator);

    $pdf = UploadedFile::fake()->createWithContent('memo.pdf', Pdf::loadHTML('<p>Memo</p>')->output());

    Livewire::test(CreateOutgoing::class)
        ->set('office', 'HR')
        ->set('status', 'Pending')
        ->set('subject', 'Memorandum')
        ->set('remarks', 'For information')
        ->set('attachments', [$pdf])
        // The form does not offer signatories...
        ->assertDontSee('Request signatures')
        // ...and a request smuggled in anyway is not acted on.
        ->set('signers', [$signer->id])
        ->call('createRecord')
        ->assertHasNoErrors();

    expect(Record::count())->toBe(1)
        ->and(Attachment::count())->toBe(1)
        ->and(SignatureRequest::count())->toBe(0);
});

it('lets anyone in the office send a document out while internal routing is off', function () {
    [$record] = moduleRecord();
    $holder = User::factory()->create(['name' => 'ITD Holder', 'office' => 'ITD']);
    $colleague = User::factory()->create(['name' => 'ITD Colleague', 'office' => 'ITD']);

    InternalRouting::create([
        'record_id' => $record->id, 'office' => 'ITD',
        'from_user_id' => $colleague->id, 'from_name' => 'ITD Colleague',
        'to_user_id' => $holder->id, 'to_name' => 'ITD Holder',
        'action' => 'For action', 'received_at' => now(),
    ]);

    // Routing on: only the holder may send it out.
    expect(DocumentHandling::sendBlocker($record, $colleague))->toContain('only they can send it out');

    // Routing off: there is no panel to hand it back, so the hold is lifted.
    Modules::set(Modules::INTERNAL_ROUTING, false);
    expect(DocumentHandling::sendBlocker($record, $colleague))->toBeNull();

    // The trail is kept.
    expect(InternalRouting::count())->toBe(1);
});

it('drops "currently with" from the lists while internal routing is off, without breaking them', function () {
    [$record] = moduleRecord();
    $holder = User::factory()->create(['name' => 'ITD Holder', 'office' => 'ITD']);

    InternalRouting::create([
        'record_id' => $record->id, 'office' => 'ITD',
        'from_user_id' => $holder->id, 'from_name' => 'ITD Holder',
        'to_user_id' => $holder->id, 'to_name' => 'ITD Holder',
        'action' => 'For action', 'received_at' => now(),
    ]);

    $this->actingAs($holder);

    Livewire::test(OutgoingTable::class)->assertSee('With: ITD Holder');

    Modules::set(Modules::INTERNAL_ROUTING, false);

    Livewire::test(OutgoingTable::class)
        ->assertSee('ITD-2026-0400')
        ->assertDontSee('With: ITD Holder');
});

it('keeps record attachments working while the document library is off', function () {
    [$record, $attachment] = moduleRecord();
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Modules::set(Modules::DOCUMENTS, false);

    // A record's own files are not library files: they still open.
    $this->get(route('attachments.view', $attachment))->assertOk();
    $this->get(route('outgoing-transactions', $record->id))->assertOk();
});

it('still finds records by what their files say while the document library is off', function () {
    [$record, $attachment] = moduleRecord();

    DocumentText::where('source_type', 'attachment')->where('source_id', $attachment->id)->update([
        'status' => 'done', 'text' => 'Replacement of the airfield lighting at Iloilo Airport.', 'read_at' => now(),
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));
    Modules::set(Modules::DOCUMENTS, false);

    expect(Record::search('airfield lighting')->pluck('reference'))->toContain('ITD-2026-0400');
});

it('falls back to references and names while reading is off', function () {
    [$record, $attachment] = moduleRecord();

    DocumentText::where('source_type', 'attachment')->where('source_id', $attachment->id)->update([
        'status' => 'done', 'text' => 'Replacement of the airfield lighting at Iloilo Airport.', 'read_at' => now(),
    ]);

    $user = User::factory()->create(['office' => 'ITD']);
    $this->actingAs($user);

    Modules::set(Modules::DOCUMENT_READING, false);

    // Contents no longer searched...
    expect(Record::search('airfield lighting')->count())->toBe(0);

    // ...but the reference and subject still are, and the explorer still
    // finds a file by its name.
    expect(Record::search('ITD-2026-0400')->count())->toBe(1)
        ->and(Record::search('Runway lighting')->count())->toBe(1)
        ->and(app(DocumentSearch::class)->find($user, 'memo')->pluck('name'))->toContain('memo.pdf');

    Livewire::test(DocumentExplorer::class)->assertOk();
});

it('keeps queueing new files while reading is off, and reads none of them', function () {
    $user = User::factory()->create(['office' => 'ITD']);
    $this->actingAs($user);

    Modules::set(Modules::DOCUMENT_READING, false);

    $document = Document::storeEncrypted(
        UploadedFile::fake()->createWithContent('minutes.pdf', Pdf::loadHTML('<p>Minutes</p>')->output()),
        $user,
        ['title' => 'minutes.pdf'],
    );

    // Queued, so switching back on reads what came in meanwhile.
    expect(DocumentText::where('source_type', 'document')->where('source_id', $document->id)->value('status'))->toBe('pending');

    // The command and the explorer both decline to read.
    $this->artisan('documents:read')->expectsOutputToContain('switched off')->assertSuccessful();

    Livewire::test(DocumentExplorer::class)->call('readNow', "document-{$document->id}");

    expect(DocumentText::where('source_id', $document->id)->value('status'))->toBe('pending');

    // And the progress bar has nothing to show.
    Livewire::test(WorkProgress::class)->assertDontSee('Reading documents');
});

it('leaves notifications alone when chat is off', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Modules::set(Modules::CHAT, false);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('wire:name="notification-center"', escape: false);

    $this->get('/chat-attachments/1/view')->assertNotFound();
});
