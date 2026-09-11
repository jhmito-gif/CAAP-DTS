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

    /**
     * Mark/unmark a record confidential. Only the owning office or an admin
     * may change this.
     */
    public function toggleConfidential($id)
    {
        $record = Record::findOrFail($id);

        $user = \Illuminate\Support\Facades\Auth::user();
        $isOwner = in_array($user->office, [$record->owner, $record->origin], true);

        if (! $isOwner && ! $user->isAdmin()) {
            return back()->with('error', 'Only the originating office or an admin can change confidentiality.');
        }

        $record->is_confidential = ! $record->is_confidential;
        $record->save();

        return back()->with(
            'message',
            $record->is_confidential
                ? 'Record marked as CONFIDENTIAL. Only tagged and authorised viewers can see its details.'
                : 'Confidential flag removed.'
        );
    }
}