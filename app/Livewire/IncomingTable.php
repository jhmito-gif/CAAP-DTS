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

        return view('livewire.incoming-table', [
            'data' => Transaction::search($this->search)
                ->where(function ($query) use ($userOffice) {
                    $query->where('destination', $userOffice)
                          ->orWhereHas('record', function ($q) use ($userOffice) {
                              // Grouping reference and subject check for the office constraint
                              $q->where(function ($subQ) use ($userOffice) {
                                  $subQ->where('reference', $userOffice)
                                       ->orWhere('subject', $userOffice);
                              });
                          });
                })
                ->orderBy('created_at', 'desc') 
                ->paginate($this->perPage),
            'latest' => $latest // Pass it to the view
        ]);
    }
}
