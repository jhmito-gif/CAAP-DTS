<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The receiving office's own reference ID, recorded when it marks a
     * movement as received and printed stacked on the RAS.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('received_reference')->nullable()->after('recieved_by');
            $table->index('received_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['received_reference']);
            $table->dropColumn('received_reference');
        });
    }
};
