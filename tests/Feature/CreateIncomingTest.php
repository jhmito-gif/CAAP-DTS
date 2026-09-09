<?php

use App\Livewire\CreateIncoming;
use App\Models\Office;
use App\Models\Record;
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
        ->set('subject', 'Auto reference record')
        ->set('remarks', 'Received without reference')
        ->set('status', 'Pending')
        ->call('createRecord')
        ->assertHasNoErrors();

    $record = Record::where('reference', "Receiving Office-{$year}-0008")->first();

    expect($record)->not->toBeNull()
        ->and($record->origin)->toBe('Finance')
        ->and($record->owner)->toBe('Receiving Office');
});
