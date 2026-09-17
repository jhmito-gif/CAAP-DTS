<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Folders for the document explorer: each office keeps a tree of its own,
     * holding library uploads. Record attachments are never moved -- they stay
     * with their record and are filed here as shortcuts instead.
     */
    public function up(): void
    {
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->string('office');
            $table->foreignId('parent_id')->nullable()->constrained('document_folders')->cascadeOnDelete();
            $table->string('name', 120);

            // "/Memoranda/2026": kept in step with moves and renames so
            // breadcrumbs and "everything under here" need no recursion.
            // 500 is the most utf8mb4 can carry in an index (4 bytes a
            // character, 3072 bytes the limit) and fits far deeper nesting
            // than the explorer allows.
            $table->string('path', 500);

            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by')->nullable();

            $table->timestamps();

            $table->index(['office', 'parent_id']);
            $table->index('path');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('office')
                ->constrained('document_folders')->nullOnDelete();
        });

        // A record attachment filed into a folder. The file itself stays on its
        // record; this is only a pointer, so removing it loses nothing.
        // No foreign key on attachment_id: adding one rebuilds the attachments
        // table on MariaDB, as noted when the library was first built.
        Schema::create('folder_shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('document_folders')->cascadeOnDelete();
            $table->unsignedBigInteger('attachment_id');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->unique(['folder_id', 'attachment_id']);
            $table->index('attachment_id');
        });

        // Who may reshape an office's tree: its designated document managers,
        // plus every system admin.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('manages_documents')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('manages_documents');
        });

        Schema::dropIfExists('folder_shortcuts');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropColumn('folder_id');
        });

        Schema::dropIfExists('document_folders');
    }
};
