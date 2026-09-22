<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which optional parts of the system are switched on. Every module starts
     * on, so deploying this changes nothing until an admin turns one off.
     *
     * Turning a module off hides it; it deletes nothing. Its data stays where
     * it is, and switching it back on brings everything back as it was.
     */
    public function up(): void
    {
        Schema::create('module_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->unique();
            $table->boolean('enabled')->default(true);
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('module_settings')->insert(array_map(
            fn (string $module) => ['module' => $module, 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['documents', 'esign', 'internal_routing', 'chat', 'document_reading'],
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('module_settings');
    }
};
