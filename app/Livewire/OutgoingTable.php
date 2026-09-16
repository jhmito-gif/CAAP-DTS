<?php

namespace App\Livewire;

use App\Models\Record;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OutgoingTable extends Component
{
    use WithPagination;

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

        $data = Record::search($this->search)
            ->where(function ($query) use ($userOffice) {
                $query->where('owner', $userOffice)
                      ->orWhere('origin', $userOffice);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.outgoing-table', [
            'data' => $data,
            // Who inside this office holds each document on the page (one query).
            'holders' => \App\Models\InternalRouting::holderNames(
                collect($data->items())->pluck('id'),
                $userOffice
            ),
        ]);
    }
}
