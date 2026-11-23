<?php

use App\Livewire\IncomingTable;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** CPO's record, sent to ODG, which gave it a number of its own. */
function cpoRecordSentToOdg(?string $odgNumber = 'ODG-2026-0079'): Record
{
    $record = Record::create([
        'reference' => 'CPO-2026-2461',
        'subject' => 'Self-assessment',
        'created_by' => 'CPO Clerk',
        'origin' => 'CPO',
        'owner' => 'CPO',
    ]);

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'received_reference' => $odgNumber,
        'remarks' => 'For notation',
        'status' => 'Information / Notation',
        'destination' => 'ODG',
        'office' => 'CPO',
        'forwarded_by' => 'CPO Clerk',
    ]);

    return $record;
}

it('leads the incoming list with the office\'s own number, then the sender\'s', function () {
    cpoRecordSentToOdg();

    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(IncomingTable::class)
        ->assertSeeInOrder(['ODG-2026-0079', 'CPO-2026-2461', 'from CPO'])
        ->assertDontSee('Reference ID:');
});

it('leads with the record\'s number until the office has given it one', function () {
    cpoRecordSentToOdg(odgNumber: null);

    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(IncomingTable::class)
        ->assertSee('CPO-2026-2461')
        ->assertDontSee('from CPO');
});

it('leads with the record\'s number while office numbers are switched off', function () {
    cpoRecordSentToOdg();
    Modules::set(Modules::OFFICE_REFERENCES, false);

    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(IncomingTable::class)
        ->assertSee('CPO-2026-2461')
        ->assertDontSee('ODG-2026-0079');
});

it('shows the office\'s own latest record above incoming, not another office\'s copy', function () {
    Record::create([
        'reference' => 'ODG-2026-0001', 'subject' => 'Own memo',
        'created_by' => 'ODG Clerk', 'origin' => 'ODG', 'owner' => 'ODG',
    ]);

    // CPO logged ODG's document on arrival, later: origin ODG, owner CPO.
    Record::create([
        'reference' => 'CPO-2026-2525', 'subject' => 'Management letter',
        'origin_reference' => 'ODG-26-9752',
        'created_by' => 'CPO Clerk', 'origin' => 'ODG', 'owner' => 'CPO',
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(IncomingTable::class)
        ->assertViewHas('latest', fn ($latest) => $latest?->reference === 'ODG-2026-0001');
});

it('counts on the dashboard the same outgoing records the list shows', function () {
    Record::create([
        'reference' => 'ODG-2026-0001', 'subject' => 'Own memo',
        'created_by' => 'ODG Clerk', 'origin' => 'ODG', 'owner' => 'ODG',
    ]);
    Record::create([
        'reference' => 'CPO-2026-2525', 'subject' => 'Management letter',
        'created_by' => 'CPO Clerk', 'origin' => 'ODG', 'owner' => 'CPO',
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    $this->get(route('dashboard'))->assertViewHas('outgoingRecords', 1);
});
