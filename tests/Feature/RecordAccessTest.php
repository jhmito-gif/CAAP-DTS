<?php

use App\Livewire\OutgoingTable;
use App\Livewire\TransactionTable;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * HR logged a document it received from PD: PD is the origin, HR the owner,
 * and (as on older records) PD does not appear in the routing.
 */
function recordLoggedByHrFromPd(): Record
{
    $record = Record::create([
        'reference' => 'HR-2026-0001',
        'subject' => 'Leave application',
        'created_by' => 'HR Person',
        'origin' => 'PD',
        'owner' => 'HR',
    ]);

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'Logged as incoming',
        'status' => 'Pending',
        'destination' => 'HR',
        'office' => 'HR',
        'forwarded_by' => 'HR Person',
    ]);

    return $record;
}

it('lets the origin office open a record that another office owns', function () {
    $record = recordLoggedByHrFromPd();

    $this->actingAs(User::factory()->create(['office' => 'PD']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->assertNoRedirect()
        ->assertSee('HR-2026-0001');
});

it('still refuses an office with no link to the record', function () {
    $record = recordLoggedByHrFromPd();

    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->assertRedirect(route('dashboard'));
});

it('opens records owned by another office on the shared record page from the outgoing list', function () {
    $ownedElsewhere = recordLoggedByHrFromPd();

    $owned = Record::create([
        'reference' => 'PD-2026-0005',
        'subject' => 'Own record',
        'created_by' => 'PD Person',
        'origin' => 'PD',
        'owner' => 'PD',
    ]);

    $this->actingAs(User::factory()->create(['office' => 'PD']));

    Livewire::test(OutgoingTable::class)
        ->assertSee(route('outgoing-transactions', $owned->id), false)
        ->assertSee(route('show-transactions', $ownedElsewhere->id), false)
        ->assertDontSee(route('outgoing-transactions', $ownedElsewhere->id), false);
});
