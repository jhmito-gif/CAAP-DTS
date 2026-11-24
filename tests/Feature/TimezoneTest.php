<?php

use App\Models\Record;
use App\Support\TimezoneShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('runs the app in Manila time', function () {
    expect(config('app.timezone'))->toBe('Asia/Manila')
        ->and(now()->getTimezone()->getName())->toBe('Asia/Manila')
        ->and(now()->format('P'))->toBe('+08:00');
});

it('shifts stored datetimes, and shifts them back', function () {
    $record = Record::create([
        'reference' => 'ITD-2026-0001',
        'subject' => 'Timezone check',
        'created_by' => 'ITD Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    // A record written while the app still ran in UTC: 00:30 UTC is 08:30 here.
    DB::table('records')->where('id', $record->id)->update([
        'created_at' => '2026-01-01 00:30:00',
        'updated_at' => '2026-01-01 00:30:00',
    ]);

    $touched = TimezoneShift::apply(8);

    expect($touched)->toHaveKey('records.created_at')
        ->and((string) DB::table('records')->where('id', $record->id)->value('created_at'))
        ->toStartWith('2026-01-01 08:30:00');

    TimezoneShift::apply(-8);

    expect((string) DB::table('records')->where('id', $record->id)->value('created_at'))
        ->toStartWith('2026-01-01 00:30:00');
});

it('leaves legacy zero dates untouched', function () {
    $record = Record::create([
        'reference' => 'ITD-2026-0003',
        'subject' => 'Imported without a date',
        'created_by' => 'ITD Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    // Rows imported into MySQL can hold a zero datetime; no date function
    // can shift one, so the migration must skip it rather than fail.
    DB::table('records')->where('id', $record->id)->update(['created_at' => '0000-00-00 00:00:00']);

    TimezoneShift::apply(8);

    expect((string) DB::table('records')->where('id', $record->id)->value('created_at'))
        ->toStartWith('0000-00-00 00:00:00');
});

it('leaves date-only columns alone', function () {
    // No app table keeps a bare date any more (the received date now keeps
    // its time), so probe with one of our own.
    \Illuminate\Support\Facades\Schema::create('tz_probe', function ($table) {
        $table->id();
        $table->date('day')->nullable();
        $table->timestamps();
    });

    DB::table('tz_probe')->insert([
        // A time shift must not move a bare date's day.
        'day' => '2026-01-01',
        'created_at' => '2026-01-01 00:30:00',
        'updated_at' => '2026-01-01 00:30:00',
    ]);

    TimezoneShift::apply(-8);

    expect((string) DB::table('tz_probe')->value('day'))->toBe('2026-01-01')
        ->and((string) DB::table('tz_probe')->value('created_at'))->toStartWith('2025-12-31 16:30:00');
});
