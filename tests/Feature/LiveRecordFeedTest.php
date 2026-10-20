<?php

use App\Livewire\IncomingTable;
use App\Livewire\OutgoingTable;
use App\Livewire\OutgoingTransaction;
use App\Livewire\TransactionTable;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** A record raised by ITD and routed to ODG. */
function routedRecord(string $reference, string $destination = 'ODG', array $overrides = []): array
{
    $record = Record::create([
        'reference' => $reference,
        'subject' => 'Runway lighting repairs',
        'created_by' => 'ITD Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    $transaction = Transaction::create(array_merge([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'For Action',
        'destination' => $destination,
        'office' => 'ITD',
        'forwarded_by' => 'ITD Person',
    ], $overrides));

    return [$record, $transaction];
}

/*
|--------------------------------------------------------------------------
| The badge on a newly incoming file
|--------------------------------------------------------------------------
*/
it('badges a movement sent here that nobody has received yet', function () {
    [$record] = routedRecord('ITD-2026-0001');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(IncomingTable::class)
        ->assertSee($record->reference)
        ->assertSee('New')
        ->assertSee('not yet marked as received');
});

it('drops the badge once the movement is received', function () {
    [$record, $transaction] = routedRecord('ITD-2026-0002');
    $transaction->update(['date_recieved' => now(), 'recieved_by' => 'ODG Person']);

    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(IncomingTable::class)
        ->assertSee($record->reference)
        ->assertDontSee('not yet marked as received');
});

it('does not badge a movement addressed to another office', function () {
    // Matched into this list the legacy way -- on the record, not the
    // destination. It is not waiting on this office, so it is not new here.
    [$record] = routedRecord('ODG', 'ITD');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(IncomingTable::class)
        ->assertSee($record->reference)
        ->assertDontSee('not yet marked as received');
});

/*
|--------------------------------------------------------------------------
| Arriving while the page is open
|--------------------------------------------------------------------------
*/
it('counts what lands after the incoming page was opened, and nothing before it', function () {
    routedRecord('ITD-2026-0010');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    $page = Livewire::test(IncomingTable::class)->assertDontSee('arrived');

    routedRecord('ITD-2026-0011');
    routedRecord('ITD-2026-0012');

    $page->call('$refresh')
        ->assertSee('2 new records arrived')
        ->assertSee('ITD-2026-0011');

    // The chip clears once the reader has acknowledged it.
    $page->call('catchUp')->assertDontSee('new records arrived');
});

it('leaves out arrivals for another office', function () {
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    $page = Livewire::test(IncomingTable::class);

    routedRecord('ITD-2026-0020', 'FINANCE');

    $page->call('$refresh')->assertDontSee('arrived');
});

it('counts records the office logs while the outgoing page is open', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $page = Livewire::test(OutgoingTable::class)->assertDontSee('added');

    routedRecord('ITD-2026-0030');

    $page->call('$refresh')->assertSee('1 new record added');

    $page->call('catchUp')->assertDontSee('new record added');
});

/*
|--------------------------------------------------------------------------
| Record pages refreshing themselves
|--------------------------------------------------------------------------
*/
it('does no work at all while the record has not moved', function () {
    [$record] = routedRecord('ITD-2026-0040');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    $page = Livewire::test(TransactionTable::class, ['recordId' => $record->id]);

    // A poll on an unchanged record: one aggregate query and nothing else,
    // because the view -- the expensive part -- is never rendered.
    DB::flushQueryLog();
    DB::enableQueryLog();
    $page->call('pollActivity');
    $quiet = count(DB::getQueryLog());

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'Endorsed to finance',
        'status' => 'Forwarded',
        'destination' => 'FINANCE',
        'office' => 'ODG',
        'forwarded_by' => 'ODG Person',
    ]);

    DB::flushQueryLog();
    $page->call('pollActivity');
    $rendered = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($quiet)->toBeLessThan($rendered);
});

it('picks up a movement logged by another office while the page is open', function () {
    [$record] = routedRecord('ITD-2026-0041');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    $page = Livewire::test(TransactionTable::class, ['recordId' => $record->id]);

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'Endorsed to finance',
        'status' => 'Forwarded',
        'destination' => 'FINANCE',
        'office' => 'ODG',
        'forwarded_by' => 'ODG Person',
    ]);

    $page->call('pollActivity')->assertSee('Endorsed to finance');
});

it('holds the refresh back while a modal is open on the page', function () {
    [$record] = routedRecord('ITD-2026-0042');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    $page = Livewire::test(TransactionTable::class, ['recordId' => $record->id]);

    // The browser sets this when the send modal opens, so that whatever the
    // user is typing there is not morphed away underneath them.
    $page->set('formBusy', true);
    $onScreen = $page->get('activitySeen');

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'Endorsed to finance',
        'status' => 'Forwarded',
        'destination' => 'FINANCE',
        'office' => 'ODG',
        'forwarded_by' => 'ODG Person',
    ]);

    $page->call('pollActivity');

    // Still showing what it was showing: the movement waits for the modal.
    expect($page->get('activitySeen'))->toBe($onScreen);
});

it('notices a movement being received, not just added', function () {
    [$record, $transaction] = routedRecord('ITD-2026-0043');
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'name' => 'ITD Person']));

    $page = Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id]);
    $before = $page->get('activitySeen');

    $transaction->update(['date_recieved' => now(), 'recieved_by' => 'ODG Person']);

    $page->call('pollActivity');

    expect($page->get('activitySeen'))->not->toBe($before);
});

/*
|--------------------------------------------------------------------------
| The polling itself
|--------------------------------------------------------------------------
*/
it('polls the lists and the record pages, only while they are on screen', function () {
    [$record] = routedRecord('ITD-2026-0050');
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test(IncomingTable::class)->assertSeeHtml('wire:poll.15s.visible');
    Livewire::test(OutgoingTable::class)->assertSeeHtml('wire:poll.15s.visible');
    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->assertSeeHtml('wire:poll.20s.visible="pollActivity"');
});
