<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * transactions.date_recieved was a date-only column: "Mark as received" saved
 * the moment, but the database kept only the day, so every receipt read as
 * 12:00 AM -- earlier than the send it answered. Keep the time from now on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->withLegacyZeroDates(function (): void {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dateTime('date_recieved')->nullable()->change();
            });
        });

        $this->restoreTimesFromAuditLog();
    }

    public function down(): void
    {
        $this->withLegacyZeroDates(function (): void {
            Schema::table('transactions', function (Blueprint $table) {
                $table->date('date_recieved')->nullable()->change();
            });
        });
    }

    /**
     * Old rows carry '0000-00-00' received dates, which strict MySQL/MariaDB
     * refuses to convert. They are carried over exactly as they are (the app
     * already treats them as received), so relax the SQL mode for the column
     * change only, then put it back.
     */
    private function withLegacyZeroDates(callable $change): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $change();

            return;
        }

        $mode = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;

        DB::statement("SET SESSION sql_mode = ''");

        try {
            $change();
        } finally {
            DB::statement('SET SESSION sql_mode = ' . DB::getPdo()->quote($mode));
        }
    }

    /**
     * Receipts made since the audit log went live have their exact moment
     * there. Older ones keep the day only and are shown without a time.
     */
    private function restoreTimesFromAuditLog(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')
            ->where('action', 'transaction.received')
            ->where('subject_type', 'Transaction')
            ->whereNotNull('subject_id')
            ->orderBy('id')
            ->get(['subject_id', 'created_at'])
            ->each(function ($entry): void {
                $moment = (string) $entry->created_at;

                DB::table('transactions')
                    ->where('id', $entry->subject_id)
                    ->whereDate('date_recieved', substr($moment, 0, 10))
                    ->whereTime('date_recieved', '00:00:00')
                    ->update(['date_recieved' => $moment]);
            });
    }
};
