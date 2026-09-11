<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remark_templates', function (Blueprint $table) {
            $table->id();
            $table->string('text', 500);
            $table->timestamps();
        });

        // Seed common presets so the dropdown is usable immediately.
        $now = now();
        $defaults = [
            'For your appropriate action.',
            'For your information and reference.',
            'For signature.',
            'For review and comment.',
            'For approval.',
            'Forwarded for compliance.',
            'Please expedite.',
            'Noted and returned.',
        ];

        DB::table('remark_templates')->insert(
            array_map(fn ($text) => ['text' => $text, 'created_at' => $now, 'updated_at' => $now], $defaults)
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('remark_templates');
    }
};
