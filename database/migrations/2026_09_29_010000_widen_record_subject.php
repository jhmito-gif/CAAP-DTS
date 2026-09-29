<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Subjects were capped at 255 characters by the column, which is shorter
     * than plenty of real correspondence: the save reached the database and
     * failed there ("Data too long for column 'subject'") instead of being
     * caught in the form. TEXT lifts the wall; the form now holds the limit.
     */
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->text('subject')->change();
        });
    }

    /**
     * Going back means the column can no longer hold what is in it, so any
     * long subject is trimmed first rather than left for the database to
     * refuse mid-migration.
     */
    public function down(): void
    {
        DB::table('records')->select('id', 'subject')->orderBy('id')->chunk(500, function ($records) {
            foreach ($records as $record) {
                if (mb_strlen((string) $record->subject) > 255) {
                    DB::table('records')
                        ->where('id', $record->id)
                        ->update(['subject' => Str::limit((string) $record->subject, 252)]);
                }
            }
        });

        Schema::table('records', function (Blueprint $table) {
            $table->string('subject')->change();
        });
    }
};
