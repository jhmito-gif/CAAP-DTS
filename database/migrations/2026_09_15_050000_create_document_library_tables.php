<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Document library: categories, files uploaded straight into an office's
     * library (outside routing), and a category on routed attachments.
     */
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('office');
            $table->foreignId('document_category_id')->nullable()->constrained('document_categories')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->char('sha256', 64)->nullable();
            $table->boolean('is_encrypted')->default(true);

            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('uploaded_by')->nullable();

            $table->timestamps();

            $table->index(['office', 'created_at']);
        });

        // No foreign key here: adding one would rebuild the attachments table
        // on MariaDB. DocumentCategory clears the column when a category is deleted.
        if (! Schema::hasColumn('attachments', 'document_category_id')) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('document_category_id')->nullable()->index();
            });
        }

        $now = now();

        DB::table('document_categories')->insert(array_map(
            fn (string $name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now],
            ['Memorandum', 'Letter', 'Office Order', 'Report', 'Contract', 'Certificate', 'Form']
        ));
    }

    public function down(): void
    {
        if (Schema::hasColumn('attachments', 'document_category_id')) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropIndex(['document_category_id']);
                $table->dropColumn('document_category_id');
            });
        }

        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_categories');
    }
};
