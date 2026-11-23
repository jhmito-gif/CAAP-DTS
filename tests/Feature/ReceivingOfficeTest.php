<?php

use App\Livewire\IncomingTable;
use App\Livewire\OutgoingTable;
use App\Livewire\OutgoingTransaction;
use App\Livewire\TransactionTable;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** CPO created a record and sent it to ODG; ODG has not received it yet. */
function sentFromCpoToOdg(): array
{
    $record = Record::create([
        'reference' => 'CPO-2026-2507',
        'subject' => 'Request for approval',
        'created_by' => 'CPO Clerk',
        'origin' => 'CPO',
        'owner' => 'CPO',
    ]);

    $movement = Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For approval',
        'status' => 'Pending',
        'destination' => 'ODG',
        'office' => 'CPO',
        'forwarded_by' => 'CPO Clerk',
    ]);

    return [$record, $movement];
}

it('will not let the sending office mark its own movement as received', function () {
    [$record, $movement] = sentFromCpoToOdg();

    $this->actingAs(User::factory()->create(['name' => 'CPO Clerk', 'office' => 'CPO']));

    // No button on either record page...
    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->assertDontSee("markAsReceived({$movement->id})", false);

    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->assertDontSee("markAsReceived({$movement->id})", false);

    // ...and calling the action directly is refused.
    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id)
        ->assertDispatched('banner-message', style: 'danger', message: 'Only ODG can mark this as received.');

    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id)
        ->assertDispatched('banner-message', style: 'danger');

    expect($movement->fresh()->date_recieved)->toBeNull()
        ->and($movement->fresh()->recieved_by)->toBeNull();
});

it('lets the office the document was sent to receive it', function () {
    [$record, $movement] = sentFromCpoToOdg();

    $this->actingAs(User::factory()->create(['name' => 'ODG Clerk', 'office' => 'ODG']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->assertSee("markAsReceived({$movement->id})", false)
        ->call('markAsReceived', $movement->id);

    expect($movement->fresh()->recieved_by)->toBe('ODG Clerk');
});

it('lets an admin receive on the office\'s behalf', function () {
    [$record, $movement] = sentFromCpoToOdg();

    $this->actingAs(User::factory()->create(['name' => 'Admin', 'office' => 'ITD', 'role' => User::ROLE_ADMIN]));

    expect($movement->isReceivableBy(auth()->user()))->toBeTrue();

    $movement->forceFill(['date_recieved' => now()])->save();

    expect($movement->fresh()->isReceivableBy(auth()->user()))->toBeFalse();
});

it('shows routing in the incoming list as sender first, then receiver', function () {
    [, $movement] = sentFromCpoToOdg();
    $movement->update(['recieved_by' => 'ODG Clerk', 'date_recieved' => now()]);

    $this->actingAs(User::factory()->create(['name' => 'ODG Clerk', 'office' => 'ODG']));

    Livewire::test(IncomingTable::class)
        ->assertSeeInOrder(['Sent by CPO Clerk', 'Received by ODG Clerk'], false);
});

it('offers the page size the lists open with, and ignores sizes it does not offer', function (string $table) {
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test($table)
        ->assertSee('<option value="10">10</option>', false)
        ->set('perPage', 100000)
        ->assertViewHas('data', fn ($data) => $data->perPage() === 10)
        ->set('perPage', 50)
        ->assertViewHas('data', fn ($data) => $data->perPage() === 50);
})->with(['incoming' => IncomingTable::class, 'outgoing' => OutgoingTable::class]);
