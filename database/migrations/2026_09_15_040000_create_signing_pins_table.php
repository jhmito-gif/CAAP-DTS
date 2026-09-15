<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personal signing PINs (hashed), used instead of the account password
     * when signing documents.
     */
    public function up(): void
    {
        Schema::create('signing_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('pin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_pins');
    }
};
