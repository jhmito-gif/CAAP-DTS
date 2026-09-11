<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            // Newly uploaded files are stored encrypted at rest. The flag lets
            // us serve older (plaintext) files without attempting to decrypt.
            $table->boolean('is_encrypted')->default(false)->after('is_confidential');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('is_encrypted');
        });
    }
};
