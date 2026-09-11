<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            // Hashed access token that gates viewing a confidential record's
            // RAS and files, even for cleared/tagged viewers.
            $table->string('confidential_token')->nullable()->after('is_confidential');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn('confidential_token');
        });
    }
};
