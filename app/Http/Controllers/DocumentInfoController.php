<?php

namespace App\Http\Controllers;

use App\Support\DocumentDetails;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One file, described: as JSON for the panel beside the floating viewer, and
 * as a page of its own for reading it properly.
 *
 * What may be said about a file is decided in one place (DocumentDetails), so
 * both answers obey the same rules.
 */
class DocumentInfoController extends Controller
{
    public function show(Request $request, DocumentDetails $details): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'regex:/^(attachment|document|shortcut)-\d+$/'],
        ]);

        $file = $details->resolve(Auth::user(), $validated['key']);

        if (! $file) {
            return response()->json(['message' => 'That file is not available to you.'], 403);
        }

        return response()->json($details->describe(Auth::user(), $file));
    }

    /**
     * The file's own page: the document filling the screen, its details beside
     * it, and an address that can be kept or shared.
     */
    public function page(string $key, DocumentDetails $details): View
    {
        $file = $details->resolve(Auth::user(), $key);

        abort_unless($file, 403, 'That file is not available to you.');

        $described = $details->describe(Auth::user(), $file);

        abort_unless($described['view_url'], 403, 'This file is locked. Open it from its record.');

        return view('documents.file', ['file' => $described]);
    }
}
