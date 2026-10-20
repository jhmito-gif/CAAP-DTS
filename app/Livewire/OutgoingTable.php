<?php

namespace App\Livewire;

use App\Livewire\Concerns\TracksNewArrivals;
use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OutgoingTable extends Component
{
    use TracksNewArrivals;
    use WithPagination;

    public $perPage = 10;
    public $search = '';

    protected $listeners = ['recordAdded' => '$refresh'];

    public function mount(): void
    {
        $this->markArrivalsSeen();
    }

    /** Records this office owns or raised -- a shared desk logs into one list. */
    protected function arrivalsQuery(): Builder
    {
        $userOffice = Auth::user()->office;

        return Record::query()->where(function ($query) use ($userOffice) {
            $query->where('owner', $userOffice)->orWhere('origin', $userOffice);
        });
    }

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

        $data = Record::search($this->search)
            ->where(function ($query) use ($userOffice) {
                $query->where('owner', $userOffice)
                      ->orWhere('origin', $userOffice);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.outgoing-table', [
            'data' => $data,
            // Records logged by the office since the page was opened.
            'arrived' => $this->arrivedSinceOpened(),
            // Who inside this office holds each document on the page (one
            // query) -- nobody, while internal routing is switched off.
            'holders' => \App\Support\Modules::enabled(\App\Support\Modules::INTERNAL_ROUTING)
                ? \App\Models\InternalRouting::holderNames(collect($data->items())->pluck('id'), $userOffice)
                : collect(),
        ]);
    }
}
