<?php

namespace App\Livewire;

use App\Models\Record;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OutgoingTable extends Component
{
    use WithPagination;

    /** The page sizes the "Per page" list offers; anything else falls back to 10. */
    public const PER_PAGE = [3, 5, 10, 50, 100, 200];

    public $perPage = 10;
    public $search = '';

    protected $listeners = ['recordAdded' => '$refresh'];

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

        // The office's own records only. A document it sent that another
        // office logged on arrival (origin = this office, owner = them) is
        // that office's record, under their number, and stays out of here.
        $data = Record::search($this->search)
            ->where('owner', $userOffice)
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int) $this->perPage, self::PER_PAGE, true) ? (int) $this->perPage : 10);

        return view('livewire.outgoing-table', [
            'data' => $data,
            // Who inside this office holds each document on the page (one
            // query) -- nobody, while internal routing is switched off.
            'holders' => \App\Support\Modules::enabled(\App\Support\Modules::INTERNAL_ROUTING)
                ? \App\Models\InternalRouting::holderNames(collect($data->items())->pluck('id'), $userOffice)
                : collect(),
        ]);
    }
}
