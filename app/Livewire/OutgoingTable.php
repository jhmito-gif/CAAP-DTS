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

    public function render()
    {
        $userOffice = Auth::user()->office;

        return view('livewire.outgoing-table', [
            'data' => Record::search($this->search)
                ->where(function ($query) use ($userOffice) {
                    $query->where('owner', $userOffice)
                          ->orWhere('origin', $userOffice);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($this->perPage)
        ]);
    }
}
