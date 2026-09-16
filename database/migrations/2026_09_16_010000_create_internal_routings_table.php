<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Person-to-person movements inside one office, under the office-to-office
     * routing: who passed the document to whom, and when it was accepted.
     */
    public function up(): void
    {
        Schema::create('internal_routings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('record_id')->constrained()->cascadeOnDelete();
            $table->string('office');

            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_name')->nullable();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('to_name');

            $table->string('action')->nullable();
            $table->text('remarks')->nullable();

            // Set when the recipient acknowledges it; null while in their queue.
            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->index(['record_id', 'office']);
            $table->index(['to_user_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_routings');
    }
};
