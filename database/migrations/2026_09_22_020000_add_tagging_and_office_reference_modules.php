<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Two more switches: tagging people, and each office giving a record its
     * own reference ID. Both start on, so deploying changes nothing.
     */
    public function up(): void
    {
        $now = now();

        foreach (['tagging', 'office_references'] as $module) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => $module],
                ['enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('module_settings')->whereIn('module', ['tagging', 'office_references'])->delete();
    }
};
