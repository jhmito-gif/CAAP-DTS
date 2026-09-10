<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Links a record to the personnel the document is about. The office is
     * snapshotted at tagging time so the tag still reads correctly if the
     * person is later moved to another office.
     */
    public function up(): void
    {
        Schema::create('record_taggings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('office')->nullable();
            $table->string('tagged_by')->nullable();

            $table->timestamps();

            $table->unique(['record_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_taggings');
    }
};
