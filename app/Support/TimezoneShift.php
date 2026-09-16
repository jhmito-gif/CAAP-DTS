<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves every stored datetime by a number of hours. Used once, when the app
 * stopped running in UTC and started running in Asia/Manila: the values in the
 * database were written as UTC wall-clock time and must be re-read as local
 * time. Date-only columns (a received date, for instance) are left alone.
 */
class TimezoneShift
{
    /** Framework bookkeeping, not document history. */
    private const SKIP_TABLES = [
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    /**
     * @return array<string, int>  rows updated, keyed by "table.column"
     */
    public static function apply(int $hours): array
    {
        $touched = [];

        foreach (Schema::getTableListing() as $table) {
            // Some drivers report "schema.table".
            $table = str_contains($table, '.') ? (string) last(explode('.', $table)) : $table;

            if (in_array($table, self::SKIP_TABLES, true)) {
                continue;
            }

            foreach (Schema::getColumns($table) as $column) {
                if (! in_array(strtolower((string) $column['type_name']), ['timestamp', 'datetime'], true)) {
                    continue;
                }

                $rows = static::shift($table, $column['name'], $hours);

                if ($rows > 0) {
                    $touched["{$table}.{$column['name']}"] = $rows;
                }
            }
        }

        return $touched;
    }

    private static function shift(string $table, string $column, int $hours): int
    {
        $quoted = static::quote($column);

        $expression = match (DB::connection()->getDriverName()) {
            'sqlite' => sprintf("datetime(%s, '%+d hours')", $quoted, $hours),
            'pgsql' => sprintf("%s + interval '%d hours'", $quoted, $hours),
            'sqlsrv' => sprintf('DATEADD(hour, %d, %s)', $hours, $quoted),
            default => sprintf('DATE_ADD(%s, INTERVAL %d HOUR)', $quoted, $hours),
        };

        return DB::table($table)
            ->whereNotNull($column)
            // Legacy MySQL rows can hold '0000-00-00 00:00:00'. No date function
            // can shift those, so leave them exactly as they are.
            ->where($column, '>', '1000-01-01 00:00:00')
            ->update([$column => DB::raw($expression)]);
    }

    private static function quote(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => '`' . str_replace('`', '``', $column) . '`',
            'sqlsrv' => '[' . str_replace(']', ']]', $column) . ']',
            default => '"' . str_replace('"', '""', $column) . '"',
        };
    }
}
