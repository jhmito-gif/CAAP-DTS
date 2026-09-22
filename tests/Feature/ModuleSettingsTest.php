<?php

use App\Filament\Resources\ModuleSettingResource\Pages\ListModuleSettings;
use App\Livewire\AssignReference;
use App\Livewire\CreateOutgoing;
use App\Livewire\DocumentExplorer;
use App\Livewire\InternalTrail;
use App\Livewire\OutgoingTable;
use App\Livewire\OutgoingTransaction;
use App\Livewire\TagPeople;
use App\Livewire\WorkProgress;
use App\Models\RecordTagging;
use App\Models\ReferenceSequence;
use App\Support\OfficeReference;
use App\Support\SignatureRequester;
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

/*
|--------------------------------------------------------------------------
| Tagging people
|--------------------------------------------------------------------------
*/
it('takes the tagging controls away while tagging is off', function () {
    [$record] = moduleRecord();
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $this->get(route('outgoing-transactions', $record->id))->assertSee('Tagged Personnel');

    Modules::set(Modules::TAGGING, false);

    $this->get(route('outgoing-transactions', $record->id))
        ->assertOk()
        ->assertDontSee('Tagged Personnel')
        ->assertDontSee('wire:name="tag-people"', escape: false);
});

it('will not save tags while tagging is off, so nobody loses access by it', function () {
    [$record] = moduleRecord();
    $viewer = User::factory()->create(['office' => 'HR']);

    // Someone already tagged: a cleared viewer, say.
    RecordTagging::create(['record_id' => $record->id, 'user_id' => $viewer->id, 'office' => 'HR', 'tagged_by' => 'ITD Person']);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));
    Modules::set(Modules::TAGGING, false);

    // Saving with nobody ticked would normally untag them.
    Livewire::test(TagPeople::class, ['recordId' => $record->id])
        ->set('selected', [])
        ->call('saveTags');

    expect(RecordTagging::where('record_id', $record->id)->where('user_id', $viewer->id)->exists())->toBeTrue();
});

it('keeps a tagged viewer cleared for a confidential record while tagging is off', function () {
    [$record] = moduleRecord();
    $record->update(['is_confidential' => true]);

    $viewer = User::factory()->create(['office' => 'HR']);
    RecordTagging::create(['record_id' => $record->id, 'user_id' => $viewer->id, 'office' => 'HR', 'tagged_by' => 'ITD Person']);

    Modules::set(Modules::TAGGING, false);

    expect($record->fresh()->canViewConfidentialDetails($viewer))->toBeTrue();
});

it('still lets a signatory in to sign while tagging is off', function () {
    [$record, $attachment] = moduleRecord();
    $record->update(['is_confidential' => true]);

    $signer = User::factory()->create(['office' => 'HR']);
    $requester = User::factory()->create(['office' => 'ITD']);

    Modules::set(Modules::TAGGING, false);

    app(SignatureRequester::class)->request($attachment, $signer, $requester, 'test');

    // Signing depends on the signatory being cleared, tagging or not.
    expect($record->fresh()->canViewConfidentialDetails($signer))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Office reference IDs
|--------------------------------------------------------------------------
*/
it('keeps one central reference, and leaves the sequences alone, while office references are off', function () {
    [$record] = moduleRecord();

    // On: the receiving office gets a number of its own, from its sequence.
    expect(OfficeReference::allocate($record, 'HR'))->toStartWith('HR-')
        ->and(ReferenceSequence::where('office', 'HR')->exists())->toBeTrue();

    // Off: no new number, and no office's sequence is used up for it.
    Modules::set(Modules::OFFICE_REFERENCES, false);

    expect(OfficeReference::allocate($record, 'CPO'))->toBeNull()
        ->and(ReferenceSequence::where('office', 'CPO')->exists())->toBeFalse();
});

it('sends a record with no new office number while office references are off', function () {
    [$record] = moduleRecord();

    Transaction::where('record_id', $record->id)->update(['date_recieved' => now(), 'recieved_by' => 'ODG Person']);
    Office::create(['name' => 'HR', 'description' => 'HR office']);
    Status::create(['name' => 'For Action']);

    Modules::set(Modules::OFFICE_REFERENCES, false);
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->set('office', 'HR')
        ->set('status', 'For Action')
        ->set('remarks', 'Please act')
        ->call('sendTransaction')
        ->assertHasNoErrors();

    $sent = Transaction::where('record_id', $record->id)->where('destination', 'HR')->firstOrFail();

    expect($sent->received_reference)->toBeNull()
        // The record's own reference still travels with it.
        ->and($sent->internal_reference)->toBe('ITD-2026-0400');
});

it('prints only the central reference on the RAS while office references are off, and keeps the others', function () {
    [$record] = moduleRecord();

    Transaction::create([
        'record_id' => $record->id, 'internal_reference' => $record->reference, 'remarks' => 'x',
        'status' => 'For Action', 'destination' => 'CPO', 'office' => 'ODG', 'forwarded_by' => 'ODG Person',
        'received_reference' => 'CPO-2026-1555',
    ]);

    $slip = fn () => view('pdfs.record', [
        'record' => $record->fresh()->load(['transactions' => fn ($q) => $q->orderBy('created_at')->orderBy('id')]),
        'masked' => false,
    ])->render();

    expect($slip())->toContain('CPO-2026-1555')->toContain('ITD-2026-0400');

    Modules::set(Modules::OFFICE_REFERENCES, false);

    expect($slip())->toContain('ITD-2026-0400')->not->toContain('CPO-2026-1555');

    // Hidden, not deleted: still stored, and back on the slip once on again.
    expect(Transaction::where('received_reference', 'CPO-2026-1555')->exists())->toBeTrue();

    Modules::set(Modules::OFFICE_REFERENCES, true);
    expect($slip())->toContain('CPO-2026-1555');
});

it('takes the Reference ID button away, and refuses a new one, while office references are off', function () {
    [$record] = moduleRecord('ODG-2026-0400');
    $record->update(['origin' => 'ODG', 'owner' => 'ODG']);

    Transaction::create([
        'record_id' => $record->id, 'internal_reference' => $record->reference, 'remarks' => 'x',
        'status' => 'For Action', 'destination' => 'ITD', 'office' => 'ODG', 'forwarded_by' => 'ODG Person',
        'received_reference' => 'ITD-2026-0777',
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $this->get(route('show-transactions', $record->id))->assertSee('ITD-2026-0777');

    Modules::set(Modules::OFFICE_REFERENCES, false);

    $this->get(route('show-transactions', $record->id))
        ->assertOk()
        ->assertDontSee('ITD-2026-0777')
        ->assertDontSee('Assign reference ID')
        ->assertDontSee('Change reference ID');

    Livewire::test(AssignReference::class)
        ->call('open', $record->id)
        ->assertDispatched('banner-message')
        ->assertNotDispatched('open-assign-reference-modal');
});

it('still finds a record by an office number given before the switch', function () {
    [$record] = moduleRecord();

    Transaction::create([
        'record_id' => $record->id, 'internal_reference' => $record->reference, 'remarks' => 'x',
        'status' => 'For Action', 'destination' => 'CPO', 'office' => 'ODG', 'forwarded_by' => 'ODG Person',
        'received_reference' => 'CPO-2026-1555',
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));
    Modules::set(Modules::OFFICE_REFERENCES, false);

    expect(Record::search('CPO-2026-1555')->pluck('reference'))->toContain('ITD-2026-0400');
});
