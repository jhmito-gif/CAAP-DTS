<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Every uploaded file now has to be given a document type. "Other" is the
     * way out for a file none of the listed types fits (an invitation, a
     * resolution…), so nobody is stopped from sending a document -- and an
     * admin can see from it which types are still missing.
     */
    public function up(): void
    {
        if (DB::table('document_categories')->where('name', 'Other')->exists()) {
            return;
        }

        DB::table('document_categories')->insert([
            'name' => 'Other',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Left in place: files may already be filed under it, and deleting it
     * would quietly strip their type.
     */
    public function down(): void
    {
    }
};
