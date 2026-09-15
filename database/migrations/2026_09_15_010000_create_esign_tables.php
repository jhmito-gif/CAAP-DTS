<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * E-signing of uploaded PDFs: each user's signature image, the signatories
     * assigned to a document, and an audit row (with file fingerprints) for
     * every signature applied.
     */
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            // SHA-256 of the file as uploaded, checked before the first signature.
            $table->char('sha256', 64)->nullable()->after('size');

            // Set once every assigned signatory has signed; the document is then locked.
            $table->timestamp('signing_completed_at')->nullable()->after('is_encrypted');
        });

        Schema::create('user_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Base64 PNG, encrypted with the app key.
            $table->longText('image');

            $table->timestamps();
        });

        Schema::create('signature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attachment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['attachment_id', 'signer_id']);
        });

        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signature_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attachment_id')->constrained()->cascadeOnDelete();

            // Kept when the account is deleted; the signer's name is snapshotted below.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // The signed version of the document, encrypted at rest.
            $table->string('path');
            $table->unsignedBigInteger('size')->default(0);

            // Where the stamp was placed, in PDF points (origin bottom-left).
            $table->unsignedSmallInteger('page');
            $table->decimal('x', 8, 2);
            $table->decimal('y', 8, 2);
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);

            // Fingerprints of the version that was signed and the version produced.
            $table->char('source_sha256', 64);
            $table->char('signed_sha256', 64)->index();

            $table->string('verification_code', 20)->unique();
            $table->string('signer_name');
            $table->string('signer_office')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
        Schema::dropIfExists('signature_requests');
        Schema::dropIfExists('user_signatures');

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn(['sha256', 'signing_completed_at']);
        });
    }
};
