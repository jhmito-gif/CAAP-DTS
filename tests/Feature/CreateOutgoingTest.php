<?php

use App\Livewire\CreateIncoming;
use App\Livewire\CreateOutgoing;
use App\Models\Office;
use App\Models\Record;
use App\Models\ReferenceSequence;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function sendOutgoingRecordAs(User $user): void
{
    test()->actingAs($user);

    Livewire::test(CreateOutgoing::class)
        ->set('office', 'Finance')
        ->set('subject', 'Outgoing reference record')
        ->set('remarks', 'For your action')
        ->set('status', 'Pending')
        ->call('createRecord')
        ->assertHasNoErrors();
}

it('does not inherit the reference number of records from other offices', function () {
    Office::create(['name' => 'Finance', 'description' => 'Finance office']);
    Status::create(['name' => 'Pending']);

    $year = now()->year;
    $user = User::factory()->create(['office' => 'ITD']);

    Record::create([
        'reference' => "ITD-{$year}-0003",
        'subject' => 'Own outgoing record',
        'created_by' => 'System',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    // Finance logged ITD's document as incoming under Finance's own number.
    Record::create([
        'reference' => "Finance-{$year}-0050",
        'origin_reference' => "ITD-{$year}-0003",
        'subject' => 'Finance incoming copy',
        'created_by' => 'System',
        'origin' => 'ITD',
        'owner' => 'Finance',
    ]);

    // Legacy incoming record saved with the sending office's reference.
    Record::create([
        'reference' => "CFO-{$year}-0001",
        'subject' => 'Legacy hand-typed incoming record',
        'created_by' => 'System',
        'origin' => 'CFO',
        'owner' => 'ITD',
    ]);

    sendOutgoingRecordAs($user);

    expect(Record::where('reference', "ITD-{$year}-0004")->exists())->toBeTrue()
        ->and(Record::where('reference', "ITD-{$year}-0051")->exists())->toBeFalse()
        ->and(Record::where('reference', "ITD-{$year}-0002")->exists())->toBeFalse();
});

it('uses and advances the configured reference sequence for outgoing records', function () {
    Office::create(['name' => 'Finance', 'description' => 'Finance office']);
    Status::create(['name' => 'Pending']);

    $year = now()->year;
    $user = User::factory()->create(['office' => 'ITD']);

    ReferenceSequence::create([
        'office' => 'ITD',
        'year' => $year,
        'next_number' => 42,
    ]);

    sendOutgoingRecordAs($user);

    expect(Record::where('reference', "ITD-{$year}-0042")->exists())->toBeTrue()
        ->and(ReferenceSequence::where('office', 'ITD')->where('year', $year)->value('next_number'))->toBe(43);
});

it('shares one reference sequence between outgoing and incoming records', function () {
    Office::create(['name' => 'Finance', 'description' => 'Finance office']);
    Status::create(['name' => 'Pending']);

    $year = now()->year;
    $user = User::factory()->create(['office' => 'ITD']);

    sendOutgoingRecordAs($user);

    expect(Record::where('reference', "ITD-{$year}-0001")->exists())->toBeTrue();

    Livewire::test(CreateIncoming::class)
        ->assertSee("ITD-{$year}-0002");
});
