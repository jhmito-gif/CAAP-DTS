<?php

use App\Livewire\IncomingTable;
use App\Livewire\InternalTrail;
use App\Models\Attachment;
use App\Models\InternalRouting;
use App\Models\Record;
use App\Models\SignatureRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\DocumentRoutedInternally;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

function internalRecord(string $owner = 'ITD'): Record
{
    return Record::create([
        'reference' => $owner . '-2026-' . str_pad((string) (100 + Record::count()), 4, '0', STR_PAD_LEFT),
        'subject' => 'Budget realignment',
        'created_by' => "{$owner} Person",
        'origin' => $owner,
        'owner' => $owner,
    ]);
}

function internalMovement(Record $record, string $from, string $to): Transaction
{
    return Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For appropriate action',
        'status' => 'Pending',
        'destination' => $to,
        'office' => $from,
        'forwarded_by' => "{$from} Person",
    ]);
}

it('passes a document between people inside an office and traces each handoff', function () {
    $record = internalRecord('ITD');
    $clerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $chief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);

    $this->actingAs($clerk);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->assertSee('No internal handoffs yet')
        ->set('toUserId', (string) $chief->id)
        ->set('action', 'For approval')
        ->set('remarks', 'Please sign before Friday')
        ->call('forward')
        ->assertHasNoErrors()
        ->assertDispatched('close-internal-forward-modal')
        ->assertSee('ITD Chief')
        ->assertSee('For approval');

    $entry = InternalRouting::sole();

    expect($entry->office)->toBe('ITD')
        ->and($entry->from_name)->toBe('ITD Clerk')
        ->and($entry->to_name)->toBe('ITD Chief')
        ->and($entry->remarks)->toBe('Please sign before Friday')
        ->and($entry->isPending())->toBeTrue()
        ->and(InternalRouting::holderFor($record->id, 'ITD')->to_name)->toBe('ITD Chief');

    Notification::assertSentTo($chief, DocumentRoutedInternally::class);

    $this->actingAs($chief);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->call('acknowledge', $entry->id)
        ->assertSee('Accepted');

    expect($entry->fresh()->received_at)->not->toBeNull();
});

it('lets only the person holding it, or an admin, pass it on', function () {
    $record = internalRecord('ITD');
    $clerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $chief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);
    $staff = User::factory()->create(['name' => 'ITD Staff', 'office' => 'ITD']);

    $this->actingAs($clerk);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->set('toUserId', (string) $chief->id)
        ->call('forward')
        ->assertHasNoErrors();

    // Someone else in the office no longer holds it, so cannot pass it on.
    $this->actingAs($staff);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->set('toUserId', (string) $chief->id)
        ->call('forward');

    expect(InternalRouting::count())->toBe(1);

    // The holder can, once they have accepted what was handed to them.
    $this->actingAs($chief);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->call('acknowledge', InternalRouting::latest('id')->value('id'))
        ->set('toUserId', (string) $staff->id)
        ->call('forward')
        ->assertHasNoErrors();

    expect(InternalRouting::count())->toBe(2)
        ->and(InternalRouting::holderFor($record->id, 'ITD')->to_name)->toBe('ITD Staff');

    // An admin may re-route it even without holding it.
    $this->actingAs(User::factory()->create(['name' => 'Admin Person', 'office' => 'ITD', 'role' => User::ROLE_ADMIN]));

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->set('toUserId', (string) $clerk->id)
        ->call('forward')
        ->assertHasNoErrors();

    expect(InternalRouting::holderFor($record->id, 'ITD')->to_name)->toBe('ITD Clerk');
});

it('refuses a recipient outside the office, or yourself', function () {
    $record = internalRecord('ITD');
    $clerk = User::factory()->create(['office' => 'ITD']);
    $outsider = User::factory()->create(['office' => 'HR']);

    $this->actingAs($clerk);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->set('toUserId', (string) $outsider->id)
        ->call('forward')
        ->assertHasErrors(['toUserId'])
        ->set('toUserId', (string) $clerk->id)
        ->call('forward')
        ->assertHasErrors(['toUserId']);

    expect(InternalRouting::count())->toBe(0);
    Notification::assertNothingSent();
});

it('lets only the recipient accept a handoff', function () {
    $record = internalRecord('ITD');
    $clerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $chief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);

    $entry = InternalRouting::create([
        'record_id' => $record->id,
        'office' => 'ITD',
        'from_user_id' => $clerk->id,
        'from_name' => $clerk->name,
        'to_user_id' => $chief->id,
        'to_name' => $chief->name,
    ]);

    $this->actingAs($clerk);
    Livewire::test(InternalTrail::class, ['recordId' => $record->id])->call('acknowledge', $entry->id);

    expect($entry->fresh()->received_at)->toBeNull();

    $this->actingAs($chief);
    Livewire::test(InternalTrail::class, ['recordId' => $record->id])->call('acknowledge', $entry->id);

    expect($entry->fresh()->received_at)->not->toBeNull();
});

it('opens the signing page when accepting a document handed over to sign', function () {
    Storage::fake('local');

    $record = internalRecord('ITD');
    $clerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $chief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);

    $attachment = Attachment::storeEncrypted(
        $record,
        null,
        UploadedFile::fake()->createWithContent('memo.pdf', '%PDF-1.4 memo'),
        'ITD Clerk'
    );

    $signatureRequest = SignatureRequest::create([
        'attachment_id' => $attachment->id,
        'record_id' => $record->id,
        'signer_id' => $chief->id,
        'requested_by' => $clerk->id,
    ]);

    $entry = InternalRouting::create([
        'record_id' => $record->id,
        'office' => 'ITD',
        'from_user_id' => $clerk->id,
        'from_name' => 'ITD Clerk',
        'to_user_id' => $chief->id,
        'to_name' => 'ITD Chief',
        'action' => 'For e-signature',
    ]);

    $this->actingAs($chief);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->assertSee('Accept & sign')
        ->call('acknowledge', $entry->id)
        ->assertRedirect(route('esign.sign', $signatureRequest));

    expect($entry->fresh()->received_at)->not->toBeNull();
});

it('stays on the record when accepting a handoff with nothing to sign', function () {
    $record = internalRecord('ITD');
    $clerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $chief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);

    $entry = InternalRouting::create([
        'record_id' => $record->id,
        'office' => 'ITD',
        'from_user_id' => $clerk->id,
        'from_name' => 'ITD Clerk',
        'to_user_id' => $chief->id,
        'to_name' => 'ITD Chief',
        'action' => 'For filing',
    ]);

    $this->actingAs($chief);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->assertSee('Accept')
        ->assertDontSee('Accept & sign')
        ->call('acknowledge', $entry->id)
        ->assertNoRedirect();

    expect($entry->fresh()->received_at)->not->toBeNull();
});

it('shows the trail to another office in the chain, which keeps its own', function () {
    $record = internalRecord('ITD');
    $itdClerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $itdChief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);

    InternalRouting::create([
        'record_id' => $record->id,
        'office' => 'ITD',
        'from_user_id' => $itdClerk->id,
        'from_name' => 'ITD Clerk',
        'to_user_id' => $itdChief->id,
        'to_name' => 'ITD Chief',
    ]);

    internalMovement($record, 'ITD', 'HR');

    $hrStaff = User::factory()->create(['name' => 'HR Staff', 'office' => 'HR']);
    $hrChief = User::factory()->create(['name' => 'HR Chief', 'office' => 'HR']);

    $this->actingAs($hrStaff);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->assertSee('ITD Chief')
        ->set('toUserId', (string) $hrChief->id)
        ->call('forward')
        ->assertHasNoErrors();

    expect(InternalRouting::where('office', 'HR')->count())->toBe(1)
        ->and(InternalRouting::holderFor($record->id, 'ITD')->to_name)->toBe('ITD Chief')
        ->and(InternalRouting::holderFor($record->id, 'HR')->to_name)->toBe('HR Chief');
});

it('requires the handoff to be accepted before it can be passed on', function () {
    $record = internalRecord('ITD');
    $clerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $chief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);
    $staff = User::factory()->create(['name' => 'ITD Staff', 'office' => 'ITD']);

    $entry = InternalRouting::create([
        'record_id' => $record->id,
        'office' => 'ITD',
        'from_user_id' => $clerk->id,
        'from_name' => 'ITD Clerk',
        'to_user_id' => $chief->id,
        'to_name' => 'ITD Chief',
        'action' => 'For review',
    ]);

    $this->actingAs($chief);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->assertSee('Accept the document first')
        ->set('toUserId', (string) $staff->id)
        ->call('forward');

    expect(InternalRouting::count())->toBe(1);

    // Once accepted, it can move on.
    $entry->update(['received_at' => now()]);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->set('toUserId', (string) $staff->id)
        ->call('forward')
        ->assertHasNoErrors();

    expect(InternalRouting::count())->toBe(2);
});

it('requires a requested signature before the document moves on', function () {
    Storage::fake('local');

    $record = internalRecord('ITD');
    $clerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $chief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);

    $attachment = Attachment::storeEncrypted($record, null, UploadedFile::fake()->createWithContent('memo.pdf', '%PDF-1.4 memo'), 'ITD Clerk');

    $signatureRequest = SignatureRequest::create([
        'attachment_id' => $attachment->id,
        'record_id' => $record->id,
        'signer_id' => $chief->id,
        'requested_by' => $clerk->id,
    ]);

    InternalRouting::create([
        'record_id' => $record->id,
        'office' => 'ITD',
        'from_user_id' => $clerk->id,
        'from_name' => 'ITD Clerk',
        'to_user_id' => $chief->id,
        'to_name' => 'ITD Chief',
        'action' => 'For e-signature',
        'received_at' => now(),
    ]);

    $this->actingAs($chief);

    // Cannot pass it on, nor send it out, while their signature is outstanding.
    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->assertSee('Sign the document before passing it on')
        ->set('toUserId', (string) $clerk->id)
        ->call('forward');

    expect(InternalRouting::count())->toBe(1);

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->set('remarks', 'Forwarding')
        ->set('status', 'Pending')
        ->set('office', 'HR')
        ->call('sendTransaction');

    expect(Transaction::where('record_id', $record->id)->count())->toBe(0);

    // After signing, both are open again.
    $signatureRequest->update(['signed_at' => now()]);

    Livewire::test(InternalTrail::class, ['recordId' => $record->id])
        ->set('toUserId', (string) $clerk->id)
        ->call('forward')
        ->assertHasNoErrors();

    expect(InternalRouting::count())->toBe(2);
});

it('lets only the current holder send the document to another office', function () {
    $record = internalRecord('ITD');
    $clerk = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $chief = User::factory()->create(['name' => 'ITD Chief', 'office' => 'ITD']);

    InternalRouting::create([
        'record_id' => $record->id,
        'office' => 'ITD',
        'from_user_id' => $clerk->id,
        'from_name' => 'ITD Clerk',
        'to_user_id' => $chief->id,
        'to_name' => 'ITD Chief',
        'received_at' => now(),
    ]);

    // The clerk passed it on, so they can no longer release it.
    $this->actingAs($clerk);

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->set('remarks', 'Forwarding')
        ->set('status', 'Pending')
        ->set('office', 'HR')
        ->call('sendTransaction');

    expect(Transaction::where('record_id', $record->id)->count())->toBe(0);

    // The holder can.
    $this->actingAs($chief);

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->set('remarks', 'Forwarding')
        ->set('status', 'Pending')
        ->set('office', 'HR')
        ->call('sendTransaction');

    expect(Transaction::where('record_id', $record->id)->where('destination', 'HR')->count())->toBe(1);
});

it('shows who currently holds the document in the incoming list', function () {
    $record = internalRecord('ITD');
    internalMovement($record, 'ITD', 'HR');

    $hrStaff = User::factory()->create(['name' => 'HR Staff', 'office' => 'HR']);
    $hrChief = User::factory()->create(['name' => 'HR Chief', 'office' => 'HR']);

    InternalRouting::create([
        'record_id' => $record->id,
        'office' => 'HR',
        'from_user_id' => $hrStaff->id,
        'from_name' => 'HR Staff',
        'to_user_id' => $hrChief->id,
        'to_name' => 'HR Chief',
    ]);

    $this->actingAs($hrStaff);

    Livewire::test(IncomingTable::class)
        ->assertSee('With:')
        ->assertSee('HR Chief');
});
