<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->boolean('is_confidential')->default(false)->after('is_urgent');
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->boolean('is_confidential')->default(false)->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn('is_confidential');
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('is_confidential');
        });
    }
};
