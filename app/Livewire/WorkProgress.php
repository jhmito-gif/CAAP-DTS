<?php

namespace App\Livewire;

use App\Models\DocumentText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * The floating progress bar.
 *
 * Work that outlives a page -- reading documents so they can be searched --
 * is tracked in the database, so the bar shows the same progress on every
 * page and keeps counting after a reload or a move to another screen.
 *
 * It lives in the app layout, polls slowly while idle and briskly while there
 * is something to report, and takes itself away when the work is done.
 */
class WorkProgress extends Component
{
    /** How long a finished batch stays on screen. */
    private const LINGER_SECONDS = 25;

    /** Anything read in this window counts towards the batch in progress. */
    private const BATCH_MINUTES = 15;

    public function render()
    {
        $reading = $this->reading();

        return view('livewire.work-progress', [
            'reading' => $reading,
            // Brisk while there is work, slow while there is none.
            'interval' => $reading['pending'] > 0 ? '3s' : '20s',
        ]);
    }

    /**
     * Where the document reading has got to.
     *
     * @return array{pending: int, done: int, failed: int, total: int, percent: int, show: bool, finished: bool}
     */
    private function reading(): array
    {
        // Nothing to report while signed out, or while reading is switched off
        // -- the queue does not move then, so a bar would only sit still.
        if (! Auth::check() || \App\Support\Modules::disabled(\App\Support\Modules::DOCUMENT_READING)) {
            return ['pending' => 0, 'done' => 0, 'failed' => 0, 'total' => 0, 'percent' => 0, 'show' => false, 'finished' => false];
        }

        $since = now()->subMinutes(self::BATCH_MINUTES);

        $counts = DocumentText::query()
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending', ['pending'])
            ->selectRaw('SUM(CASE WHEN status = ? AND read_at >= ? THEN 1 ELSE 0 END) as done', ['done', $since])
            ->selectRaw('SUM(CASE WHEN status = ? AND updated_at >= ? THEN 1 ELSE 0 END) as failed', ['failed', $since])
            ->selectRaw('MAX(read_at) as last_read')
            ->first();

        $pending = (int) ($counts->pending ?? 0);
        $done = (int) ($counts->done ?? 0);
        $failed = (int) ($counts->failed ?? 0);
        $total = $pending + $done;

        // Once everything is read the bar lingers a moment, then goes.
        $lastRead = $counts->last_read ? \Illuminate\Support\Carbon::parse($counts->last_read) : null;
        $justFinished = $pending === 0 && $done > 0 && $lastRead?->gt(now()->subSeconds(self::LINGER_SECONDS));

        return [
            'pending' => $pending,
            'done' => $done,
            'failed' => $failed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            'show' => $pending > 0 || $justFinished,
            'finished' => $pending === 0,
        ];
    }
}
