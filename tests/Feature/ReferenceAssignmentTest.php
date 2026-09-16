<?php

use App\Livewire\AssignReference;
use App\Livewire\TransactionTable;
use App\Models\Record;
use App\Models\ReferenceSequence;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function referenceRecord(string $owner = 'ITD'): Record
{
    // ITD-2026-0667, then 0668, ... -- record references are unique.
    return Record::create([
        'reference' => $owner . '-2026-' . str_pad((string) (667 + Record::count()), 4, '0', STR_PAD_LEFT),
        'subject' => 'IT network infrastructure',
        'created_by' => "{$owner} Person",
        'origin' => $owner,
        'owner' => $owner,
    ]);
}

function referenceMovement(Record $record, string $from, string $to, array $attributes = []): Transaction
{
    return Transaction::create($attributes + [
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For appropriate action',
        'status' => 'Pending',
        'destination' => $to,
        'office' => $from,
        'forwarded_by' => "{$from} Person",
    ]);
}

it('marks a movement as received in one click, without asking for a reference ID', function () {
    $record = referenceRecord();
    $movement = referenceMovement($record, 'ITD', 'CPO');

    $this->actingAs(User::factory()->create(['name' => 'CPO Person', 'office' => 'CPO']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id)
        ->assertDispatched('banner-message');

    $movement->refresh();

    expect($movement->date_recieved)->not->toBeNull()
        ->and($movement->recieved_by)->toBe('CPO Person')
        ->and($movement->received_reference)->toBeNull();
});

it('assigns the office reference before the document is received, and keeps it afterwards', function () {
    $year = now()->year;
    $record = referenceRecord();
    $movement = referenceMovement($record, 'ITD', 'CPO');

    ReferenceSequence::create(['office' => 'CPO', 'year' => $year, 'next_number' => 1555]);

    $this->actingAs(User::factory()->create(['name' => 'CPO Person', 'office' => 'CPO']));

    Livewire::test(AssignReference::class)
        ->call('open', $record->id)
        ->assertDispatched('open-assign-reference-modal')
        ->assertSet('reference', "CPO-{$year}-1555")
        ->assertSet('reused', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('close-assign-reference-modal');

    // Numbered while still in transit: signing can quote it before it arrives.
    expect($movement->fresh()->received_reference)->toBe("CPO-{$year}-1555")
        ->and($movement->fresh()->date_recieved)->toBeNull()
        ->and(ReferenceSequence::where('office', 'CPO')->where('year', $year)->value('next_number'))->toBe(1556);

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id);

    expect($movement->fresh()->received_reference)->toBe("CPO-{$year}-1555")
        ->and($movement->fresh()->date_recieved)->not->toBeNull();
});

it('keeps one number per office per document, across every movement', function () {
    $record = referenceRecord();
    referenceMovement($record, 'ITD', 'CPO');

    $this->actingAs(User::factory()->create(['office' => 'CPO']));

    Livewire::test(AssignReference::class)
        ->call('open', $record->id)
        ->set('reference', ' CPO-07867 ')
        ->call('save')
        ->assertHasNoErrors();

    // The document leaves and comes back to CPO later.
    referenceMovement($record, 'ITD', 'CPO');

    Livewire::test(AssignReference::class)
        ->call('open', $record->id)
        ->assertSet('reference', 'CPO-07867')
        ->assertSet('reused', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Transaction::where('record_id', $record->id)->where('destination', 'CPO')->pluck('received_reference')->unique()->values()->all())
        ->toBe(['CPO-07867']);
});

it('gives the originating office its own record number back', function () {
    $record = referenceRecord('ITD');
    $back = referenceMovement($record, 'CPO', 'ITD');

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(AssignReference::class)
        ->call('open', $record->id)
        ->assertSet('reference', $record->reference)
        ->assertSet('reused', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($back->fresh()->received_reference)->toBe($record->reference);
});

it('refuses a blank number, or one another document already carries at the office', function () {
    $taken = referenceRecord();
    referenceMovement($taken, 'ITD', 'CPO', ['received_reference' => 'CPO-TAKEN']);

    $record = referenceRecord();
    $movement = referenceMovement($record, 'ITD', 'CPO');

    $this->actingAs(User::factory()->create(['office' => 'CPO']));

    Livewire::test(AssignReference::class)
        ->call('open', $record->id)
        ->set('reference', '')
        ->call('save')
        ->assertHasErrors(['reference' => 'required'])
        ->set('reference', 'CPO-TAKEN')
        ->call('save')
        ->assertHasErrors(['reference' => 'unique']);

    expect($movement->fresh()->received_reference)->toBeNull();
});

it('offers no reference ID to an office the document never reached', function () {
    $record = referenceRecord();
    referenceMovement($record, 'ITD', 'CPO');

    $this->actingAs(User::factory()->create(['office' => 'HR']));

    Livewire::test(AssignReference::class)
        ->call('open', $record->id)
        ->assertNotDispatched('open-assign-reference-modal');

    expect(Transaction::whereNotNull('received_reference')->count())->toBe(0);
});

it('stacks every office reference ID in the record header', function () {
    $record = referenceRecord();
    referenceMovement($record, 'ITD', 'CPO', ['received_reference' => 'CPO-2026-1555']);
    referenceMovement($record, 'CPO', 'HR', ['received_reference' => 'HR-07867']);

    $this->actingAs(User::factory()->create(['office' => 'HR']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->assertSee('HR-07867')
        ->assertSee('CPO-2026-1555')
        ->assertSee($record->reference);
});

it('gives the destination office its own reference ID the moment the document is sent', function () {
    $year = now()->year;
    $record = referenceRecord('ITD');

    ReferenceSequence::create(['office' => 'ODG', 'year' => $year, 'next_number' => 2132]);

    $this->actingAs(User::factory()->create(['name' => 'ITD Person', 'office' => 'ITD']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->set('remarks', 'For signature')
        ->set('status', 'Pending')
        ->set('office', 'ODG')
        ->call('sendTransaction');

    $movement = Transaction::where('record_id', $record->id)->latest('id')->first();

    expect($movement->destination)->toBe('ODG')
        ->and($movement->received_reference)->toBe("ODG-{$year}-2132")
        // Numbered while in transit: nobody has received it yet.
        ->and($movement->date_recieved)->toBeNull()
        ->and(ReferenceSequence::where('office', 'ODG')->where('year', $year)->value('next_number'))->toBe(2133);
});

it('stacks the sending and receiving office numbers on the RAS', function () {
    $year = now()->year;
    $record = referenceRecord('ITD');

    ReferenceSequence::create(['office' => 'ODG', 'year' => $year, 'next_number' => 2132]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->set('remarks', 'For signature')
        ->set('status', 'Pending')
        ->set('office', 'ODG')
        ->call('sendTransaction');

    $record->load(['transactions' => fn ($query) => $query->orderBy('created_at')->orderBy('id')]);

    $html = view('pdfs.record', ['record' => $record])->render();
    $positions = [strpos($html, "ODG-{$year}-2132"), strpos($html, $record->reference)];

    expect($positions)->not->toContain(false)
        ->and($positions[0])->toBeLessThan($positions[1]);
});

it('reuses an office number when the document is sent back to it', function () {
    $year = now()->year;
    $record = referenceRecord('ITD');

    referenceMovement($record, 'ITD', 'ODG', ['received_reference' => "ODG-{$year}-2132"]);
    ReferenceSequence::create(['office' => 'ODG', 'year' => $year, 'next_number' => 2133]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->set('remarks', 'Returned for signature')
        ->set('status', 'Pending')
        ->set('office', 'ODG')
        ->call('sendTransaction');

    expect(Transaction::where('record_id', $record->id)->where('destination', 'ODG')->pluck('received_reference')->unique()->values()->all())
        ->toBe(["ODG-{$year}-2132"])
        // A returning document consumes no new number.
        ->and(ReferenceSequence::where('office', 'ODG')->where('year', $year)->value('next_number'))->toBe(2133);
});

it('never loses the origin number, deriving it for documents the office originated', function () {
    // Originated here: its own reference is the origin number.
    $own = referenceRecord('ITD');
    expect($own->originNumber())->toBe($own->reference);

    // Logged as incoming with the sending office's number.
    $incoming = Record::create([
        'reference' => 'ITD-2026-0900',
        'origin_reference' => 'CFO-2026-0777',
        'subject' => 'Incoming from CFO',
        'created_by' => 'ITD Person',
        'origin' => 'CFO',
        'owner' => 'ITD',
    ]);
    expect($incoming->originNumber())->toBe('CFO-2026-0777');

    // Logged as incoming without one: nothing to show, and nothing invented.
    $blank = Record::create([
        'reference' => 'ITD-2026-0901',
        'subject' => 'Incoming, no number given',
        'created_by' => 'ITD Person',
        'origin' => 'CFO',
        'owner' => 'ITD',
    ]);
    expect($blank->originNumber())->toBeNull();
});

it('carries the origin number onto each movement it forwards', function () {
    $record = referenceRecord('ITD');

    $this->actingAs(User::factory()->create(['name' => 'ITD Person', 'office' => 'ITD']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->set('remarks', 'For appropriate action')
        ->set('status', 'Pending')
        ->set('office', 'HR')
        ->call('sendTransaction');

    expect(Transaction::where('record_id', $record->id)->latest('id')->value('origin_reference'))
        ->toBe($record->reference);
});

it('prints the origin number once on the RAS for a document the office originated', function () {
    $record = referenceRecord('ITD');
    referenceMovement($record, 'ITD', 'HR', ['received_reference' => 'HR-07867']);

    $record->load(['transactions' => fn ($query) => $query->orderBy('created_at')->orderBy('id')]);

    $html = view('pdfs.record', ['record' => $record])->render();

    expect($html)->toContain($record->reference)
        ->and($html)->toContain('HR-07867')
        // No separate "Internal Tracking Number" line when it is the same number.
        ->and($html)->not->toContain('Internal Tracking Number');
});

it('finds a record by any reference ID assigned along its route', function () {
    $record = referenceRecord();
    referenceMovement($record, 'ITD', 'CPO', ['received_reference' => 'CPO-2026-1555']);

    expect(Record::search('CPO-2026-1555')->pluck('reference')->all())->toBe([$record->reference]);
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
        $transaction = referenceMovement($record, 'ITD', $office, [
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
