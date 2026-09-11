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
            $record = Record::with([
                'transactions' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
            ])->findOrFail($id);


            $userOffice = Auth::user()->office;

            // Check if user’s office is part of any related transaction
            $hasAccess = $record->transactions()
                ->where(function ($query) use ($userOffice) {
                    $query->where('destination', $userOffice)
                        ->orWhere('office', $userOffice);
                })
                ->exists();

            // Someone tagged on this record can print it too.
            $isTagged = \App\Models\RecordTagging::where('record_id', $record->id)
                ->where('user_id', Auth::id())
                ->exists();

            // An office granted explicit access can print it as well.
            $isGranted = \App\Models\Access::where('record_id', $record->id)
                ->where('office', $userOffice)
                ->exists();

            // Verify if authenticated user's office matches the record's owner or has access via transactions
            if ($record->owner !== $userOffice && !$hasAccess && !$isTagged && !$isGranted) {
                return redirect()->route('dashboard')
                    ->with('error', 'Unauthorized access to record RAS.');
            }

            // A confidential record's subject/remarks are redacted on the slip
            // for viewers who are not cleared to see its details.
            $masked = $record->isMaskedFor(Auth::user());

            // Token-gated: cleared viewers may still only open the full slip
            // through a short-lived signed link issued after entering the token.
            // Uncleared viewers get the redacted/watermarked slip regardless.
            if (! $masked && $record->requiresToken() && ! request()->hasValidSignature()) {
                $masked = true;
            }

            // Load the Blade view and pass the record
            $pdf = Pdf::loadView('pdfs.record', compact('record', 'masked'))
                ->setPaper('a4', 'portrait');

            // Stream (view) the PDF in the browser
            return $pdf->stream('record-' . $record->id . '.pdf');
        }
    }
}
