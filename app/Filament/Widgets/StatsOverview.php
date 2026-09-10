<?php

namespace App\Filament\Widgets;

use App\Models\Office;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    // Lead the dashboard, ahead of the account widget (sort -3).
    protected static ?int $sort = -4;

    protected ?string $heading = 'Overview';

    protected function getStats(): array
    {
        // Records per day for the last 7 days (oldest first) in one grouped query.
        $since = Carbon::today()->subDays(6);

        $counts = Record::query()
            ->where('created_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck(DB::raw('count(*) as aggregate'), DB::raw('date(created_at) as day'));

        $trend = collect(range(6, 0))
            ->map(fn (int $daysAgo) => (int) ($counts[Carbon::today()->subDays($daysAgo)->toDateString()] ?? 0))
            ->all();

        $totalRecords = Record::count();
        $urgent = Record::where('is_urgent', true)->count();

        $transactions = Transaction::count();
        $pending = Transaction::whereNull('date_recieved')->count();

        $users = User::count();
        $offices = Office::count();

        return [
            Stat::make('Total Records', number_format($totalRecords))
                ->description($urgent > 0 ? "{$urgent} marked urgent" : 'None urgent')
                ->descriptionIcon($urgent > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($urgent > 0 ? 'danger' : 'primary')
                ->chart($trend),

            Stat::make('Transactions', number_format($transactions))
                ->description($pending > 0 ? "{$pending} awaiting receipt" : 'All received')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('Personnel', number_format($users))
                ->description("Across {$offices} " . str('office')->plural($offices))
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
