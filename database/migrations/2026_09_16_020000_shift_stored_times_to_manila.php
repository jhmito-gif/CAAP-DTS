<?php

use App\Support\TimezoneShift;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The app used to run in UTC, so every stored datetime is UTC wall-clock
     * time while the office reads Manila time (UTC+8) -- eight hours out on
     * screen, and a day out for "today" counts and year-based reference
     * numbering between midnight and 8am.
     *
     * The app now runs in Asia/Manila, so the stored values are moved to match.
     */
    public function up(): void
    {
        // All or nothing: a half-shifted database would be worse than the offset.
        DB::transaction(fn () => TimezoneShift::apply(8));
    }

    public function down(): void
    {
        DB::transaction(fn () => TimezoneShift::apply(-8));
    }
};
