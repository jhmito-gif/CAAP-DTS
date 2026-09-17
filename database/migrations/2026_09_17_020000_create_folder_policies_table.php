<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who may see and use a folder, beyond the office that owns it.
     *
     * A grant names a subject -- another office, one person, or everyone
     * signed in -- and what they may do there. Grants flow down the tree, so
     * sharing a folder shares what is inside it.
     *
     * A folder can also be closed: then even its own office sees nothing in it
     * without a grant. Its document managers and system admins always may.
     */
    public function up(): void
    {
        Schema::create('folder_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('document_folders')->cascadeOnDelete();

            // 'office' (its name), 'user' (their id), or 'everyone' (no subject).
            $table->string('subject_type', 20);
            $table->string('subject', 191)->nullable();

            // 'view', 'edit' (view and add files) or 'manage' (edit and reshape).
            $table->string('level', 10)->default('view');

            $table->foreignId('granted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('granted_by')->nullable();

            $table->timestamps();

            $table->unique(['folder_id', 'subject_type', 'subject']);
            $table->index(['subject_type', 'subject']);
        });

        Schema::table('document_folders', function (Blueprint $table) {
            // Closed: nothing inside is visible without a grant.
            $table->boolean('is_restricted')->default(false)->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('document_folders', function (Blueprint $table) {
            $table->dropColumn('is_restricted');
        });

        Schema::dropIfExists('folder_policies');
    }
};
