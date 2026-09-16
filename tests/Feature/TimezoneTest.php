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

it('leaves date-only columns alone', function () {
    $record = Record::create([
        'reference' => 'ITD-2026-0002',
        'subject' => 'Timezone check',
        'created_by' => 'ITD Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    DB::table('transactions')->insert([
        'record_id' => $record->id,
        'remarks' => 'For action',
        'status' => 'Pending',
        'destination' => 'HR',
        'office' => 'ITD',
        'forwarded_by' => 'ITD Person',
        // transactions.date_recieved is a date: a time shift must not move the day.
        'date_recieved' => '2026-01-01',
        'created_at' => '2026-01-01 00:30:00',
        'updated_at' => '2026-01-01 00:30:00',
    ]);

    TimezoneShift::apply(8);

    expect((string) DB::table('transactions')->where('record_id', $record->id)->value('date_recieved'))
        ->toStartWith('2026-01-01')
        ->and((string) DB::table('transactions')->where('record_id', $record->id)->value('created_at'))
        ->toStartWith('2026-01-01 08:30:00');
});
