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
    $latest = Record::where(function ($query) {
            $query->where('origin', Auth::user()->office)
                  ->orWhere('owner', Auth::user()->office);
        })
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

        return view('livewire.incoming-table', [
            'data' => Transaction::search($this->search)
                ->where(function ($query) use ($userOffice, $legacyRecordIds) {
                    $query->where('destination', $userOffice)
                          ->when($legacyRecordIds->isNotEmpty(), fn ($q) => $q->orWhereIn('record_id', $legacyRecordIds));
                })
                ->orderBy('created_at', 'desc') 
                ->paginate($this->perPage),
            'latest' => $latest // Pass it to the view
        ]);
    }
}
