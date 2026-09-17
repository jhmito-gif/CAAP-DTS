<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The words inside a document, so it can be found by what it says rather
     * than only by its name. Filled in from the text layer of a PDF, or by
     * reading a scan with OCR (App\Support\DocumentReader).
     *
     * One row per file, whether that file is a routed attachment or a library
     * upload. The text is a copy for searching; the file itself stays the
     * record, and its access rules still decide who may see it.
     */
    public function up(): void
    {
        Schema::create('document_texts', function (Blueprint $table) {
            $table->id();

            // 'attachment' or 'document'.
            $table->string('source_type', 20);
            $table->unsignedBigInteger('source_id');

            $table->string('status', 20)->default('pending'); // pending, done, failed, skipped
            $table->string('method', 20)->nullable();         // text layer, ocr, mixed
            $table->unsignedSmallInteger('pages')->default(0);
            $table->unsignedSmallInteger('ocr_pages')->default(0);
            $table->unsignedInteger('characters')->default(0);

            $table->longText('text')->nullable();

            // The file as it was when read: a new signed version reads again.
            $table->char('sha256', 64)->nullable();

            $table->string('failure', 255)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('read_at')->nullable();

            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['status', 'updated_at']);
        });

        // Word search, where the database can do it. SQLite (the test database)
        // has no such index, and falls back to LIKE.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE document_texts ADD FULLTEXT document_texts_text_fulltext (text)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_texts');
    }
};
