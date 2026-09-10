<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'internal_reference')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('internal_reference')->nullable()->after('record_id');
            });
        }

        if (! Schema::hasColumn('transactions', 'origin_reference')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('origin_reference')->nullable()->after('internal_reference');
            });
        }

        DB::table('transactions')
            ->whereNotNull('record_id')
            ->update([
                'internal_reference' => DB::raw('(select reference from records where records.id = transactions.record_id)'),
                'origin_reference' => DB::raw('(select origin_reference from records where records.id = transactions.record_id)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'origin_reference')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('origin_reference');
            });
        }

        if (Schema::hasColumn('transactions', 'internal_reference')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('internal_reference');
            });
        }
    }
};
