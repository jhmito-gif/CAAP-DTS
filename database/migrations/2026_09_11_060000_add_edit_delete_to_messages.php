<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('body');
            $table->timestamp('deleted_for_everyone_at')->nullable()->after('edited_at');
        });

        // "Remove for you" -- a per-user hide that leaves the message intact
        // for everyone else.
        Schema::create('message_hides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_hides');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['edited_at', 'deleted_for_everyone_at']);
        });
    }
};
