<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Record;
use Carbon\Carbon;

class TableRecord extends Component
{
    use WithPagination;

    public $search = '';   
    public $perPage = 2;    
    public $sortBy = 'subject';
    public $sortDir = 'ASC';

    

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public $record_status = '';

    public function render()
    {
        // $records = Record::latest()
        // ->where('subject', 'like', "%{$this->search}%")
        // ->where('owner', Auth::user()->office)
        // ->orderBy('created_at', 'desc')
        // ->paginate($this->perPage);

        // return view('livewire.table-record', compact('records'));

        return view('livewire.table-record', [
        'searchTest' => $this->search,
        'records' => Record::latest()
            ->when($this->search, function ($query) {
                $query->where('subject', 'like', '%' . $this->search . '%');
            })
            ->paginate(10),
    ]);
    }
}
