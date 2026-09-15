<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the columns the lists, record pages and dashboard filter and
     * sort on. Without them every routing lookup scans the whole transactions
     * table, which timed out requests (504) once the tables grew.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private array $indexes = [
        'transactions' => [
            'transactions_record_id_index' => ['record_id'],
            'transactions_destination_created_at_index' => ['destination', 'created_at'],
            'transactions_office_created_at_index' => ['office', 'created_at'],
        ],
        'records' => [
            'records_owner_index' => ['owner'],
            'records_created_at_index' => ['created_at'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                // Skip indexes that already exist (e.g. added by hand on a server).
                if (Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }
};
