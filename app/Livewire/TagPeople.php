<?php

namespace App\Livewire;

use App\Models\Office;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\User;
use App\Notifications\RecordTagged;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Tags the personnel a document is about.
 *
 * Pick an office, then check off people in that office. Newly tagged people
 * get a database notification; untagging is silent.
 */
class TagPeople extends Component
{
    public int $recordId;

    /** Office currently being browsed in the picker. */
    public string $office = '';

    /** User ids ticked in the picker, as strings (checkbox values). */
    public array $selected = [];

    public bool $showModal = false;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
        $this->office = Auth::user()->office ?? '';
        $this->syncSelectionFromRecord();
    }

    /**
     * Reset the ticked boxes to whoever is actually tagged right now.
     */
    protected function syncSelectionFromRecord(): void
    {
        $this->selected = RecordTagging::where('record_id', $this->recordId)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function openModal(): void
    {
        // Re-read on open so the picker reflects any tagging done elsewhere.
        $this->syncSelectionFromRecord();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /**
     * Persist the ticked people, notifying only those newly added.
     */
    public function saveTags(): void
    {
        $record = Record::findOrFail($this->recordId);

        $existing = RecordTagging::where('record_id', $record->id)
            ->pluck('user_id')
            ->all();

        $chosen = collect($this->selected)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Only people who exist get tagged.
        $users = User::whereIn('id', $chosen)->get();

        $added = $users->reject(fn (User $user) => in_array($user->id, $existing, true));
        $removedIds = array_values(array_diff($existing, $users->pluck('id')->all()));

        foreach ($added as $user) {
            RecordTagging::create([
                'record_id' => $record->id,
                'user_id' => $user->id,
                'office' => $user->office,
                'tagged_by' => Auth::user()->name,
            ]);

            $user->notify(new RecordTagged($record, Auth::user()->name));
        }

        if ($removedIds !== []) {
            RecordTagging::where('record_id', $record->id)
                ->whereIn('user_id', $removedIds)
                ->delete();
        }

        $this->showModal = false;

        $this->dispatch('tags-updated', recordId: $record->id);
        $this->dispatch('notifications-changed');

        session()->flash('tag-message', $this->summarise($added->count(), count($removedIds)));
    }

    protected function summarise(int $added, int $removed): string
    {
        if ($added === 0 && $removed === 0) {
            return 'No changes to tagged personnel.';
        }

        $parts = [];

        if ($added > 0) {
            $parts[] = $added . ' ' . \Illuminate\Support\Str::plural('person', $added) . ' tagged and notified';
        }

        if ($removed > 0) {
            $parts[] = $removed . ' removed';
        }

        return ucfirst(implode(', ', $parts)) . '.';
    }

    public function render()
    {
        $record = Record::with('taggedUsers')->findOrFail($this->recordId);

        $personnel = filled($this->office)
            ? User::where('office', $this->office)->orderBy('name')->get()
            : collect();

        return view('livewire.tag-people', [
            'record' => $record,
            'taggedUsers' => $record->taggedUsers,
            'personnel' => $personnel,
            'officeOptions' => Office::orderBy('name')->pluck('name', 'name')->toArray(),
        ]);
    }
}
