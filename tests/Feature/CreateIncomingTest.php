<?php

use App\Livewire\CreateIncoming;
use App\Livewire\TransactionTable;
use App\Models\Office;
use App\Models\Record;
use App\Models\ReferenceSequence;
use App\Models\Status;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('auto-generates the next incoming reference id for the receiving office', function () {
    Office::create(['name' => 'Finance', 'description' => 'Finance office']);
    Status::create(['name' => 'Pending']);

    $year = now()->year;
    $user = User::factory()->create(['office' => 'Receiving Office']);

    Record::create([
        'reference' => "Receiving Office-{$year}-3494",
        'subject' => 'Older imported receiving office record',
        'created_by' => 'System',
        'origin' => 'Receiving Office',
        'owner' => 'Receiving Office',
        'created_at' => now()->subMonth(),
        'updated_at' => now()->subMonth(),
    ]);

    Record::create([
        'reference' => "Receiving Office-{$year}-0007",
        'subject' => 'Existing receiving office record',
        'created_by' => 'System',
        'origin' => 'Receiving Office',
        'owner' => 'Receiving Office',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(CreateIncoming::class)
        ->set('office', 'Finance')
        ->set('originReference', 'FIN-2026-0042')
        ->set('subject', 'Auto reference record')
        ->set('remarks', 'Received without reference')
        ->set('status', 'Pending')
        ->call('createRecord')
        ->assertHasNoErrors();

    $record = Record::where('reference', "Receiving Office-{$year}-0008")->first();

    expect($record)->not->toBeNull()
        ->and($record->origin)->toBe('Finance')
        ->and($record->owner)->toBe('Receiving Office')
        ->and($record->origin_reference)->toBe('FIN-2026-0042');

    $transaction = Transaction::where('record_id', $record->id)->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->internal_reference)->toBe($record->reference)
        ->and($transaction->origin_reference)->toBe('FIN-2026-0042');
});

it('shows the next internal reference id before saving', function () {
    Office::create(['name' => 'Finance', 'description' => 'Finance office']);

    $year = now()->year;
    $user = User::factory()->create(['office' => 'Receiving Office']);

    Record::create([
        'reference' => "Receiving Office-{$year}-0007",
        'subject' => 'Existing receiving office record',
        'created_by' => 'System',
        'origin' => 'Receiving Office',
        'owner' => 'Receiving Office',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(CreateIncoming::class)
        ->assertSee("Receiving Office-{$year}-0008");
});

it('uses the configured reference sequence when it is higher than the latest record', function () {
    Office::create(['name' => 'Finance', 'description' => 'Finance office']);
    Status::create(['name' => 'Pending']);

    $year = now()->year;
    $user = User::factory()->create(['office' => 'Receiving Office']);

    ReferenceSequence::create([
        'office' => 'Receiving Office',
        'year' => $year,
        'next_number' => 42,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateIncoming::class)
        ->assertSee("Receiving Office-{$year}-0042")
        ->set('office', 'Finance')
        ->set('originReference', 'FIN-2026-0042')
        ->set('subject', 'Configured reference record')
        ->set('remarks', 'Received with configured sequence')
        ->set('status', 'Pending')
        ->call('createRecord')
        ->assertHasNoErrors();

    expect(Record::where('reference', "Receiving Office-{$year}-0042")->exists())->toBeTrue()
        ->and(ReferenceSequence::where('office', 'Receiving Office')->where('year', $year)->value('next_number'))->toBe(43);
});

it('requires origin reference ids to be unique per origin office', function () {
    Office::create(['name' => 'Finance', 'description' => 'Finance office']);
    Status::create(['name' => 'Pending']);

    $user = User::factory()->create(['office' => 'Receiving Office']);

    Record::create([
        'reference' => 'Receiving Office-2026-0001',
        'origin_reference' => 'FIN-2026-0042',
        'subject' => 'Existing finance record',
        'created_by' => 'System',
        'origin' => 'Finance',
        'owner' => 'Receiving Office',
    ]);

    $this->actingAs($user);

    Livewire::test(CreateIncoming::class)
        ->set('office', 'Finance')
        ->set('originReference', 'FIN-2026-0042')
        ->set('subject', 'Duplicate origin reference record')
        ->set('remarks', 'Received with duplicate origin reference')
        ->set('status', 'Pending')
        ->call('createRecord')
        ->assertHasErrors(['originReference' => 'unique']);
});

it('snapshots reference ids when forwarding a record', function () {
    Office::create(['name' => 'Receiving Office', 'description' => 'Receiving office']);
    Office::create(['name' => 'Next Office', 'description' => 'Next office']);
    Status::create(['name' => 'Forwarded']);

    $user = User::factory()->create(['office' => 'Receiving Office']);
    $record = Record::create([
        'reference' => 'Receiving Office-2026-0009',
        'origin_reference' => 'FIN-2026-0042',
        'subject' => 'Forwarding snapshot record',
        'created_by' => 'System',
        'origin' => 'Finance',
        'owner' => 'Receiving Office',
    ]);

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'origin_reference' => $record->origin_reference,
        'remarks' => 'Initial received movement',
        'status' => 'Received',
        'destination' => 'Receiving Office',
        'office' => 'Finance',
        'forwarded_by' => 'System',
        'recieved_by' => $user->name,
        'date_recieved' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->set('office', 'Next Office')
        ->set('status', 'Forwarded')
        ->set('remarks', 'Sending to next office')
        ->call('sendTransaction')
        ->assertHasNoErrors();

    $transaction = Transaction::where('record_id', $record->id)
        ->where('destination', 'Next Office')
        ->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->internal_reference)->toBe('Receiving Office-2026-0009')
        ->and($transaction->origin_reference)->toBe('FIN-2026-0042');
});

it('prints movement remarks without changing original document information on the RAS', function () {
    $record = Record::create([
        'reference' => 'CURRENT-2026-9999',
        'origin_reference' => 'CURRENT-ORIGIN-9999',
        'subject' => 'Printable RAS snapshot record',
        'created_by' => 'System',
        'origin' => 'Finance',
        'owner' => 'Receiving Office',
    ]);

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => 'SNAPSHOT-2026-0001',
        'origin_reference' => 'FIN-SNAPSHOT-0042',
        'remarks' => 'Forwarded with required action',
        'status' => 'Forwarded',
        'destination' => 'Next Office',
        'office' => 'Receiving Office',
        'forwarded_by' => 'System',
    ]);

    $record->load(['transactions' => fn ($query) => $query->orderBy('created_at')->orderBy('id')]);

    $html = view('pdfs.record', ['record' => $record])->render();

    expect($html)->toContain('ROUTING ACTION SLIP')
        ->and($html)->toContain('Forwarded with required action')
        ->and($html)->toContain('Forwarded')
        ->and($html)->toContain('Reference Number:')
        ->and($html)->toContain('CURRENT-ORIGIN-9999')
        ->and($html)->toContain('Internal Tracking Number:')
        ->and($html)->toContain('CURRENT-2026-9999')
        ->and($html)->not->toContain('Tracking Ref: SNAPSHOT-2026-0001')
        ->and($html)->not->toContain('Origin Ref: FIN-SNAPSHOT-0042');
});
