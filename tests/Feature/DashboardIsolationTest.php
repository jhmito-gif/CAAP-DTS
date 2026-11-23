<?php

use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A record an office created and sent on to another office. */
function routedRecord(string $reference, string $subject, string $from, ?string $to, array $flags = []): Record
{
    $record = Record::create([
        'reference' => $reference,
        'subject' => $subject,
        'created_by' => "{$from} Person",
        'origin' => $from,
        'owner' => $from,
        ...$flags,
    ]);

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $reference,
        'remarks' => 'For action',
        'status' => 'Pending',
        'destination' => $to,
        'office' => $from,
        'forwarded_by' => "{$from} Person",
    ]);

    return $record;
}

it('shows an office only its own records', function () {
    routedRecord('HR-2026-0001', 'Leave application', 'HR', 'ITD', ['is_urgent' => true]);
    routedRecord('PD-2026-0001', 'Budget proposal', 'PD', 'ODG', ['is_urgent' => true]);

    $this->actingAs(User::factory()->create(['office' => 'HR']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('HR-2026-0001')
        ->assertSee('Leave application')
        ->assertDontSee('PD-2026-0001')
        ->assertDontSee('Budget proposal');
});

it('hides a confidential subject from an office it was only routed to', function () {
    routedRecord('PD-2026-0002', 'Disciplinary case of a named employee', 'PD', 'HR', [
        'is_urgent' => true,
        'is_confidential' => true,
    ]);

    // HR receives it: it learns the record exists, not what it is about.
    $this->actingAs(User::factory()->create(['office' => 'HR']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('PD-2026-0002')
        ->assertSee('Confidential record')
        ->assertDontSee('Disciplinary case of a named employee');

    // The owning office still reads its own subject.
    $this->actingAs(User::factory()->create(['office' => 'PD']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Disciplinary case of a named employee')
        ->assertDontSee('Confidential record');
});

it('shows nothing to a user who has no office', function () {
    // A transaction with no destination: "destination IS NULL" would catch it.
    routedRecord('PD-2026-0003', 'Draft not yet routed', 'PD', null, ['is_urgent' => true]);

    $this->actingAs(User::factory()->create(['office' => null]));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('PD-2026-0003')
        ->assertDontSee('Draft not yet routed')
        ->assertViewHas('awaitingReceipt', 0)
        ->assertViewHas('incomingRecords', 0);
});
