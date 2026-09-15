<?php

namespace App\Http\Controllers;

use App\Models\EsignLog;
use App\Models\SignatureRequest;
use App\Support\EsignLogger;
use Illuminate\Support\Facades\Auth;

class SignatureController extends Controller
{
    /**
     * Signing screen; only the assigned signatory may open it.
     */
    public function sign(SignatureRequest $signatureRequest)
    {
        $isSigner = (int) $signatureRequest->signer_id === (int) Auth::id();

        EsignLogger::log(
            $isSigner ? 'sign_page.opened' : 'sign_page.denied',
            $isSigner ? EsignLog::INFO : EsignLog::FAILURE,
            ['request' => $signatureRequest]
        );

        abort_unless($isSigner, 403, 'This signature request is not assigned to you.');

        return view('esign.sign', ['signatureRequest' => $signatureRequest]);
    }

    /**
     * Look up a signature by its verification code or check a PDF's fingerprint.
     */
    public function verify()
    {
        return view('esign.verify');
    }
}
