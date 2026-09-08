<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Record;

class PdfController extends Controller
{
    public function RASPDF($id){    
        {
            // Get the record with its related transactions
            $record = Record::with('transactions')->findOrFail($id);


            $userOffice = Auth::user()->office;

            // Check if user’s office is part of any related transaction
            $hasAccess = $record->transactions()
                ->where(function ($query) use ($userOffice) {
                    $query->where('destination', $userOffice)
                        ->orWhere('office', $userOffice);
                })
                ->exists();

            // Verify if authenticated user's office matches the record's owner or has access via transactions
            if ($record->owner !== $userOffice && !$hasAccess) {
                return redirect()->route('dashboard')
                    ->with('error', 'Unauthorized access to record RAS.');
            }

            // Load the Blade view and pass the record
            $pdf = Pdf::loadView('pdfs.record', compact('record'));

            // Stream (view) the PDF in the browser
            return $pdf->stream('record-' . $record->id . '.pdf');
        }
    }
}
