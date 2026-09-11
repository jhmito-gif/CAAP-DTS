<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Stream an attachment to any user allowed to see its record.
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        $record = $attachment->record;
        $user = Auth::user();

        abort_unless($record && $record->isAccessibleBy($user), 403, 'Unauthorized access to attachment.');

        // Confidential files are only served to cleared viewers.
        abort_if(
            $attachment->isConfidential() && ! $record->canViewConfidentialDetails($user),
            403,
            'This file is confidential.'
        );

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404, 'File not found.');

        return $disk->download($attachment->path, $attachment->original_name);
    }

    /**
     * Inline view (e.g. open a PDF/image in a new tab).
     */
    public function view(Attachment $attachment): StreamedResponse
    {
        $record = $attachment->record;
        $user = Auth::user();

        abort_unless($record && $record->isAccessibleBy($user), 403, 'Unauthorized access to attachment.');

        // Confidential files are only served to cleared viewers.
        abort_if(
            $attachment->isConfidential() && ! $record->canViewConfidentialDetails($user),
            403,
            'This file is confidential.'
        );

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404, 'File not found.');

        return $disk->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
        ]);
    }
}
