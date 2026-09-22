<?php

use App\Filament\Resources\ReferenceSequenceResource\Pages\ListReferenceSequences;
use App\Models\ReferenceSequence;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'office' => 'ITD']));

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->spd = ReferenceSequence::create(['office' => 'SPD', 'year' => 2026, 'next_number' => 12]);
    $this->itd = ReferenceSequence::create(['office' => 'ITD', 'year' => 2026, 'next_number' => 933]);
    $this->old = ReferenceSequence::create(['office' => 'SPD', 'year' => 2025, 'next_number' => 400]);
});

it('searches the reference settings by office without a missing column', function () {
    DB::enableQueryLog();

    // The search that failed in production: "next_reference" is not a column.
    Livewire::test(ListReferenceSequences::class)
        ->searchTable('SPD')
        ->assertCanSeeTableRecords([$this->spd, $this->old])
        ->assertCanNotSeeTableRecords([$this->itd]);

    // MariaDB refuses the missing column outright, but SQLite -- what the
    // tests run on -- quietly reads an unknown quoted name as a string. So
    // check the SQL itself never asks for it.
    $sql = collect(DB::getQueryLog())->pluck('query')->implode("\n");

    expect($sql)->not->toContain('next_reference');
});

it('finds one sequence by its whole next reference', function () {
    Livewire::test(ListReferenceSequences::class)
        ->searchTable('SPD-2026-0012')
        ->assertCanSeeTableRecords([$this->spd])
        ->assertCanNotSeeTableRecords([$this->itd, $this->old]);
});

it('finds sequences by office and year together', function () {
    Livewire::test(ListReferenceSequences::class)
        ->searchTable('SPD-2025')
        ->assertCanSeeTableRecords([$this->old])
        ->assertCanNotSeeTableRecords([$this->spd, $this->itd]);
});

it('finds sequences by year alone', function () {
    Livewire::test(ListReferenceSequences::class)
        ->searchTable('2025')
        ->assertCanSeeTableRecords([$this->old])
        ->assertCanNotSeeTableRecords([$this->spd, $this->itd]);
});
