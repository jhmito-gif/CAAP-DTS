<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Document library page and the files uploaded straight into it.
 * (Routed files are served by AttachmentController.)
 */
class DocumentController extends Controller
{
    public function index(): View
    {
        return view('documents.index');
    }

    public function view(Document $document): Response
    {
        $this->authorizeAccess($document);

        return $this->serve($document, 'inline');
    }

    public function download(Document $document): Response
    {
        $this->authorizeAccess($document);

        return $this->serve($document, 'attachment');
    }

    private function authorizeAccess(Document $document): void
    {
        abort_unless($document->isAccessibleBy(Auth::user()), 403, 'You cannot open this document.');
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404, 'File not found.');
    }

    private function serve(Document $document, string $disposition): Response
    {
        return response($document->contents(), 200, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => $disposition . '; filename="' . addslashes($document->original_name) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
