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


            // Owner or origin office, routing chain, tagged personnel or granted access.
            if (! $record->isAccessibleBy(Auth::user())) {
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

            // Form footer (CAAP-ODG-CCS-001 r2, "Page X of Y") on every page.
            \App\Support\RasFormFooter::apply($pdf);

            // Stream (view) the PDF in the browser
            return $pdf->stream('record-' . $record->id . '.pdf');
        }
    }
}
