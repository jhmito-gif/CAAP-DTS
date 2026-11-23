<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Models\Record;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class IncomingTable extends Component
{
    use WithPagination;

    /** The page sizes the "Per page" list offers; anything else falls back to 10. */
    public const PER_PAGE = [3, 5, 10, 50, 100, 200];

    public $perPage = 10;
    public $search = '';

    protected $listeners = ['recordAdded' => '$refresh'];

    // Reset pagination when searching to avoid landing on empty pages
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $userOffice = Auth::user()->office;

       $year = now()->year;
    
    // Fetch the single latest record
    // This office's own latest record -- not another office's copy of a
    // document that merely started here.
    $latest = Record::where('owner', Auth::user()->office)
        ->whereYear('created_at', $year)
        ->latest('id')
        ->first();

        // Legacy rows matched on the record itself. Resolved first: an OR with a
        // subquery makes the database scan every transaction instead of using
        // the destination index.
        $legacyRecordIds = Record::query()
            ->where('reference', $userOffice)
            ->orWhere('subject', $userOffice)
            ->pluck('id');

        $data = Transaction::search($this->search)
            ->where(function ($query) use ($userOffice, $legacyRecordIds) {
                $query->where('destination', $userOffice)
                      ->when($legacyRecordIds->isNotEmpty(), fn ($q) => $q->orWhereIn('record_id', $legacyRecordIds));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int) $this->perPage, self::PER_PAGE, true) ? (int) $this->perPage : 10);

        return view('livewire.incoming-table', [
            'data' => $data,
            'latest' => $latest, // Pass it to the view
            // Who inside this office holds each document on the page (one
            // query) -- nobody, while internal routing is switched off.
            'holders' => \App\Support\Modules::enabled(\App\Support\Modules::INTERNAL_ROUTING)
                ? \App\Models\InternalRouting::holderNames(collect($data->items())->pluck('record_id'), $userOffice)
                : collect(),
        ]);
    }
}
