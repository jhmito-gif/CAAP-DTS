<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Devices a signer has chosen to remember. On one of these, opening a
     * signing session needs the signing PIN only -- the authenticator code is
     * what the device remembers. Each row is revocable and expires on its own.
     */
    public function up(): void
    {
        Schema::create('signing_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Random token, stored hashed; the browser holds the plain value.
            $table->string('token_hash', 64)->unique();

            $table->string('label')->nullable();
            $table->string('ip_address', 45)->nullable();

            // Plain datetimes: a NOT NULL timestamp column would take the zero
            // date as its default, which MariaDB refuses to create here.
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('expires_at');

            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_devices');
    }
};
