<?php

use App\Livewire\ReceiveTransaction;
use App\Models\Record;
use App\Models\ReferenceSequence;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function recordRoutedTo(string $destination, array $transaction = []): Transaction
{
    // ITD-2026-0667, then 0668, ... -- record references are unique.
    $record = Record::create([
        'reference' => 'ITD-2026-' . str_pad((string) (667 + Record::count()), 4, '0', STR_PAD_LEFT),
        'subject' => 'IT network infrastructure',
        'created_by' => 'ITD Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    return Transaction::create($transaction + [
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For appropriate action',
        'status' => 'Pending',
        'destination' => $destination,
        'office' => 'ITD',
        'forwarded_by' => 'ITD Person',
    ]);
}

it("suggests the receiving office's next reference ID and records it on receipt", function () {
    $year = now()->year;
    $receiver = User::factory()->create(['name' => 'CPO Person', 'office' => 'CPO']);
    $transaction = recordRoutedTo('CPO');

    ReferenceSequence::create(['office' => 'CPO', 'year' => $year, 'next_number' => 1555]);

    $this->actingAs($receiver);

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $transaction->id)
        ->assertDispatched('open-receive-transaction-modal')
        ->assertSet('receivedReference', "CPO-{$year}-1555")
        ->call('receive')
        ->assertHasNoErrors()
        ->assertDispatched('record-updated');

    $transaction->refresh();

    expect($transaction->received_reference)->toBe("CPO-{$year}-1555")
        ->and($transaction->recieved_by)->toBe('CPO Person')
        ->and($transaction->date_recieved)->not->toBeNull()
        ->and(ReferenceSequence::where('office', 'CPO')->where('year', $year)->value('next_number'))->toBe(1556);
});

it("accepts the office's own format without moving its sequence backwards", function () {
    $year = now()->year;
    $receiver = User::factory()->create(['name' => 'PD Person', 'office' => 'PD']);
    $custom = recordRoutedTo('PD');

    ReferenceSequence::create(['office' => 'PD', 'year' => $year, 'next_number' => 40]);

    $this->actingAs($receiver);

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $custom->id)
        ->set('receivedReference', ' PD-07867 ')
        ->call('receive')
        ->assertHasNoErrors();

    $older = Transaction::create([
        'record_id' => $custom->record_id,
        'remarks' => 'Returned',
        'status' => 'Pending',
        'destination' => 'PD',
        'office' => 'ITD',
        'forwarded_by' => 'ITD Person',
    ]);

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $older->id)
        ->set('receivedReference', "PD-{$year}-0005")
        ->call('receive')
        ->assertHasNoErrors();

    expect($custom->fresh()->received_reference)->toBe('PD-07867')
        ->and($older->fresh()->received_reference)->toBe("PD-{$year}-0005")
        ->and(ReferenceSequence::where('office', 'PD')->where('year', $year)->value('next_number'))->toBe(40);
});

it('requires a reference ID the office has not given another document', function () {
    $receiver = User::factory()->create(['name' => 'CPO Person', 'office' => 'CPO']);
    $transaction = recordRoutedTo('CPO');

    // A different document already carries this number at CPO.
    recordRoutedTo('CPO', [
        'received_reference' => 'CPO-TAKEN',
        'recieved_by' => 'CPO Person',
        'date_recieved' => now()->subDay(),
    ]);

    $this->actingAs($receiver);

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $transaction->id)
        ->set('receivedReference', '')
        ->call('receive')
        ->assertHasErrors(['receivedReference' => 'required'])
        ->set('receivedReference', 'CPO-TAKEN')
        ->call('receive')
        ->assertHasErrors(['receivedReference' => 'unique']);

    expect($transaction->fresh()->date_recieved)->toBeNull();
});

it('suggests the record number when the owning office receives its own logged incoming entry', function () {
    $receiver = User::factory()->create(['name' => 'ITD Person', 'office' => 'ITD']);

    $record = Record::create([
        'reference' => 'ITD-2026-0005',
        'origin_reference' => 'CFO-2026-0777',
        'subject' => 'Incoming from CFO',
        'created_by' => 'ITD Person',
        'origin' => 'CFO',
        'owner' => 'ITD',
    ]);

    $transaction = Transaction::create([
        'record_id' => $record->id,
        'remarks' => 'Received',
        'status' => 'Pending',
        'destination' => 'ITD',
        'office' => 'CFO',
        'forwarded_by' => 'ITD Person',
    ]);

    $this->actingAs($receiver);

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $transaction->id)
        ->assertSet('receivedReference', 'ITD-2026-0005')
        ->call('receive')
        ->assertHasNoErrors();

    expect($transaction->fresh()->received_reference)->toBe('ITD-2026-0005');
});

it('gives an office one number per document, reusing it when the document comes back', function () {
    $record = null;

    // CPO numbers the document on its way out of ITD.
    $outgoing = recordRoutedTo('CPO');
    $record = $outgoing->record;

    $this->actingAs(User::factory()->create(['name' => 'CPO Person', 'office' => 'CPO']));

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $outgoing->id)
        ->assertSet('reusedReference', false)
        ->call('receive')
        ->assertHasNoErrors();

    $cpoNumber = $outgoing->fresh()->received_reference;

    $movement = fn (string $from, string $to) => Transaction::create([
        'record_id' => $record->id,
        'remarks' => 'Returned',
        'status' => 'Pending',
        'destination' => $to,
        'office' => $from,
        'forwarded_by' => "{$from} Person",
    ]);

    $backToItd = $movement('CPO', 'ITD');
    $againToCpo = $movement('ITD', 'CPO');

    // The originating office gets its own record number back, not a second one.
    $this->actingAs(User::factory()->create(['name' => 'ITD Person', 'office' => 'ITD']));

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $backToItd->id)
        ->assertSet('receivedReference', $record->reference)
        ->assertSet('reusedReference', true)
        ->call('receive')
        ->assertHasNoErrors();

    // CPO sees the same document a second time and reuses its first number.
    $this->actingAs(User::factory()->create(['name' => 'CPO Person', 'office' => 'CPO']));

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $againToCpo->id)
        ->assertSet('receivedReference', $cpoNumber)
        ->assertSet('reusedReference', true)
        ->call('receive')
        ->assertHasNoErrors();

    expect($backToItd->fresh()->received_reference)->toBe($record->reference)
        ->and($againToCpo->fresh()->received_reference)->toBe($cpoNumber)
        ->and(Transaction::where('record_id', $record->id)
            ->whereNotNull('received_reference')
            ->orderBy('id')
            ->pluck('received_reference')
            ->unique()
            ->values()
            ->all())
        ->toBe([$cpoNumber, $record->reference]);
});

it('refuses offices outside the record and movements already received', function () {
    $outsider = User::factory()->create(['name' => 'HR Person', 'office' => 'HR']);
    $transaction = recordRoutedTo('CPO');

    $this->actingAs($outsider);

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $transaction->id)
        ->assertNotDispatched('open-receive-transaction-modal')
        ->set('transactionId', $transaction->id)
        ->set('receivedReference', 'HR-SNEAKY')
        ->call('receive');

    expect($transaction->fresh()->date_recieved)->toBeNull();

    $received = recordRoutedTo('CPO', ['recieved_by' => 'CPO Person', 'date_recieved' => now(), 'received_reference' => 'CPO-FIRST']);
    $this->actingAs(User::factory()->create(['office' => 'CPO']));

    Livewire::test(ReceiveTransaction::class)
        ->call('open', $received->id)
        ->assertNotDispatched('open-receive-transaction-modal');
});

it('finds a record by any reference ID assigned along its route', function () {
    recordRoutedTo('CPO', ['received_reference' => 'CPO-2026-1555']);

    expect(Record::search('CPO-2026-1555')->pluck('reference')->all())->toBe(['ITD-2026-0667']);
});

it('stacks every office reference ID on the RAS, newest movement first', function () {
    $record = Record::create([
        'reference' => 'ITD-2026-0667',
        'subject' => 'IT network infrastructure',
        'created_by' => 'ITD Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    foreach ([['CPO', 'CPO-2026-1555', 3], ['PD', 'PD-07867', 2], ['ODG', 'ODG-26-2769', 1]] as [$office, $reference, $daysAgo]) {
        $transaction = Transaction::create([
            'record_id' => $record->id,
            'remarks' => 'Forwarded',
            'status' => 'Forwarded',
            'destination' => $office,
            'office' => 'ITD',
            'forwarded_by' => 'ITD Person',
            'recieved_by' => "{$office} Person",
            'date_recieved' => now()->subDays($daysAgo),
            'received_reference' => $reference,
        ]);

        $transaction->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
    }

    $record->load(['transactions' => fn ($query) => $query->orderBy('created_at')->orderBy('id')]);

    $html = view('pdfs.record', ['record' => $record])->render();

    $positions = array_map(fn ($reference) => strpos($html, $reference), ['ODG-26-2769', 'PD-07867', 'CPO-2026-1555', 'ITD-2026-0667']);

    expect($positions)->not->toContain(false)
        ->and($positions)->toBe(collect($positions)->sort()->values()->all());
});
