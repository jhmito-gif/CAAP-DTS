<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * The incoming and outgoing lists poll for themselves, so a record logged by
 * another office turns up without anyone pressing refresh.
 *
 * Polling alone would slide rows in silently, which is worse than not showing
 * them: the reader loses their place and never learns why. So each page
 * remembers the newest row that existed when it was opened, and counts what
 * has landed since -- that count is what the "just arrived" chip shows.
 */
trait TracksNewArrivals
{
    /**
     * Newest row id in this office's feed when the page was opened. Public so
     * it survives the round trip; a tampered value only changes the count in
     * the chip, never what the viewer is allowed to see.
     */
    public ?int $seenId = null;

    /** This office's feed, without the search box or pagination applied. */
    abstract protected function arrivalsQuery(): Builder;

    /** Treat everything currently in the feed as already seen. */
    protected function markArrivalsSeen(): void
    {
        $this->seenId = (int) $this->arrivalsQuery()->max('id');
    }

    /** The chip: clear the counter and jump back to the newest page. */
    public function catchUp(): void
    {
        $this->markArrivalsSeen();
        $this->resetPage();
    }

    /** How many rows have landed since the page was opened. */
    protected function arrivedSinceOpened(): int
    {
        if ($this->seenId === null) {
            return 0;
        }

        return $this->arrivalsQuery()->where('id', '>', $this->seenId)->count();
    }
}
