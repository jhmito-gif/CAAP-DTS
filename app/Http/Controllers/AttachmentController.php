<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\EsignLog;
use App\Support\EsignLogger;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /**
     * Confirm the current user may access this attachment at all, aborting
     * with the right status otherwise.
     */
    protected function authorizeAccess(Attachment $attachment): void
    {
        $record = $attachment->record;
        $user = Auth::user();

        // Admins see every office's files in the document library.
        abort_unless($record && ($record->isAccessibleBy($user) || $user?->isAdmin()), 403, 'Unauthorized access to attachment.');

        // Confidential files are only served to cleared viewers.
        abort_if(
            $attachment->isConfidential() && ! $record->canViewConfidentialDetails($user),
            403,
            'This file is confidential.'
        );

        // Token-gated: a cleared viewer must reach the file through a
        // short-lived signed link issued after entering the record's token.
        abort_if(
            $record->requiresToken() && ! request()->hasValidSignature(),
            403,
            'A valid access token is required to open this file.'
        );

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404, 'File not found.');
    }

    /**
     * Download a file. Confidential files are view-only and cannot be
     * downloaded; everything else is served (decrypted) as an attachment.
     */
    public function download(Attachment $attachment): Response
    {
        $this->authorizeAccess($attachment);

        abort_if($attachment->isConfidential(), 403, 'Confidential files are view-only and cannot be downloaded.');

        $this->logSignatureDocumentAccess($attachment, 'document.downloaded');

        return response($attachment->contents(), 200, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . addslashes($attachment->original_name) . '"',
        ]);
    }

    /**
     * Inline view (e.g. preview a PDF/image in the document viewer), decrypting
     * the file on the fly. This is the only way to open a confidential file.
     */
    public function view(Attachment $attachment): Response
    {
        $this->authorizeAccess($attachment);

        $this->logSignatureDocumentAccess($attachment, 'document.viewed');

        return response($attachment->contents(), 200, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($attachment->original_name) . '"',
        ]);
    }

    /**
     * Documents sent for signature have their access recorded in the e-sign log.
     */
    private function logSignatureDocumentAccess(Attachment $attachment, string $event): void
    {
        if (! $attachment->signatureRequests()->exists()) {
            return;
        }

        EsignLogger::log($event, EsignLog::INFO, ['attachment' => $attachment], [
            'signed_versions' => $attachment->signatures()->count(),
        ]);
    }
}
