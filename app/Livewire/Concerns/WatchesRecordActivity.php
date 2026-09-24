<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * An open record page keeps itself current: when another office forwards the
 * document or marks it received, the trail updates where you are reading it.
 *
 * The page only re-renders when the trail has actually moved. Two reasons:
 * these views are large, and a re-render throws away browser-side state -- the
 * send modal is opened by plain JavaScript, so morphing fresh markup over it
 * would close it under the user's hands. While the modal is open the refresh
 * is held back entirely and picked up on the next poll after it closes.
 */
trait WatchesRecordActivity
{
    /** Set from the browser while a modal is open; nothing is refreshed then. */
    public bool $formBusy = false;

    /** Shape of the routing trail as the viewer currently sees it. */
    public string $activitySeen = '';

    public function pollActivity(): void
    {
        if ($this->formBusy || $this->activitySignature() === $this->activitySeen) {
            // No HTML goes back, so the page the user is reading is left alone.
            $this->skipRender();
        }

        // Otherwise the component renders as usual and render() records the
        // trail the viewer has now been shown.
    }

    /** Called from render(): whatever is on screen is, by definition, seen. */
    protected function noteActivitySeen(): void
    {
        $this->activitySeen = $this->activitySignature();
    }

    /**
     * Cheap fingerprint of the record's movements: one aggregate row. The count
     * catches deletions, the id catches new movements, the timestamp catches an
     * edited remark or status.
     *
     * Received and referenced are counted rather than left to the timestamp:
     * updated_at only resolves to the second, so a movement received in the
     * same second it was logged would otherwise look unchanged.
     */
    protected function activitySignature(): string
    {
        $trail = DB::table('transactions')
            ->where('record_id', $this->recordId)
            ->selectRaw(
                'COUNT(*) as entries, MAX(id) as last_id, MAX(updated_at) as touched, '
                .'COUNT(date_recieved) as received, COUNT(received_reference) as referenced'
            )
            ->first();

        return implode('|', [
            $trail->entries ?? 0,
            $trail->last_id ?? 0,
            $trail->touched ?? '',
            $trail->received ?? 0,
            $trail->referenced ?? 0,
        ]);
    }
}
