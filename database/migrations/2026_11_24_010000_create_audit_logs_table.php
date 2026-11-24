<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only trail of who did what, across the whole system: records
     * created, edited, sent and received; files added, opened and moved;
     * access granted; sign-ins; and every change made in the admin panel.
     * (E-signing keeps its own trail in esign_logs.)
     *
     * No foreign keys, so nothing done to a user or record later rewrites a
     * row; names and references are copied in instead.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // "record.created", "transaction.received", "auth.login", ...
            $table->string('action', 64);

            // What was acted on: its model, id and a readable name.
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();

            // The record it concerns, so a record's whole history is one filter.
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('record_reference')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_office')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Field => [before, after]. Never passwords or tokens: those show
            // only as "(changed)".
            $table->json('changes')->nullable();
            $table->json('context')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('record_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
