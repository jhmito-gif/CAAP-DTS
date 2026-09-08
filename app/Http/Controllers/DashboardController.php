<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userOffice = Auth::user()->office;


        /*
        |--------------------------------------------------------------------------
        | Incoming Records
        |--------------------------------------------------------------------------
        */
        $incomingRecords = Transaction::where(
                'destination',
                $userOffice
            )
            ->distinct()
            ->count('record_id');


        /*
        |--------------------------------------------------------------------------
        | Outgoing Records
        |--------------------------------------------------------------------------
        */
        $outgoingRecords = Record::where(
                'origin',
                $userOffice
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Awaiting Receipt
        |--------------------------------------------------------------------------
        */
        $awaitingReceipt = Transaction::where(
                'destination',
                $userOffice
            )
            ->whereNull('date_recieved')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Received Today
        |--------------------------------------------------------------------------
        */
        $receivedToday = Transaction::where(
                'destination',
                $userOffice
            )
            ->whereNotNull('date_recieved')
            ->whereDate('date_recieved', today())
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Activity Today
        |--------------------------------------------------------------------------
        */
        $activityToday = Transaction::whereDate(
                'created_at',
                today()
            )
            ->where(function ($query) use ($userOffice) {

                $query
                    ->where('office', $userOffice)
                    ->orWhere(
                        'destination',
                        $userOffice
                    );

            })
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Urgent Records
        |--------------------------------------------------------------------------
        */
        $urgentQuery = Record::urgent()
            ->forOffice($userOffice);


        $urgentCount = (clone $urgentQuery)
            ->count();


        $urgentRecords = (clone $urgentQuery)
            ->latest('updated_at')
            ->take(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Recent Transactions
        |--------------------------------------------------------------------------
        */
        $recentTransactions = Transaction::with('record')
            ->where(function ($query) use ($userOffice) {

                $query
                    ->where('office', $userOffice)
                    ->orWhere(
                        'destination',
                        $userOffice
                    );

            })
            ->latest('created_at')
            ->take(8)
            ->get();


        return view('dashboard', [
            'incomingRecords'    => $incomingRecords,
            'outgoingRecords'    => $outgoingRecords,
            'awaitingReceipt'    => $awaitingReceipt,
            'receivedToday'      => $receivedToday,
            'activityToday'      => $activityToday,
            'urgentCount'        => $urgentCount,
            'urgentRecords'      => $urgentRecords,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}