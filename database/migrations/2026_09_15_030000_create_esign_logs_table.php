<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only audit trail of e-sign activity: signing attempts, signature
     * requests, document access and signature profile changes. There are no
     * foreign keys, so deleting a user or record never rewrites a log row;
     * names and references are copied in instead.
     */
    public function up(): void
    {
        Schema::create('esign_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 64);
            $table->string('outcome', 16); // success | failure | info

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_office')->nullable();

            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('record_reference')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            $table->unsignedBigInteger('signature_request_id')->nullable();
            $table->unsignedBigInteger('signature_id')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Event details. Never passwords, authenticator codes or access tokens.
            $table->json('context')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index('created_at');
            $table->index('user_id');
            $table->index('record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esign_logs');
    }
};
