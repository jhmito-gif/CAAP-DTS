<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function Index($id)
    {
        $recordID = $id;

        return view('transactions.index', compact('recordID'));
    }


    public function Outgoing($id)
    {
        $recordID = $id;

        return view('transactions.outgoing', compact('recordID'));
    }


    public function toggleUrgent($id)
    {
        $record = Record::findOrFail($id);

        $record->is_urgent = !$record->is_urgent;

        $record->save();

        return back()->with(
            'message',
            $record->is_urgent
                ? 'Record marked as urgent.'
                : 'Urgent status removed.'
        );
    }
}