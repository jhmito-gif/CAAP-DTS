<?php

namespace App\Http\Controllers;

use App\Models\ChatAttachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatAttachmentController extends Controller
{
    /**
     * Only members of the attachment's conversation may open it, and only
     * while it still exists (files are purged after their retention window).
     */
    protected function authorizeAccess(ChatAttachment $attachment): void
    {
        $conversation = $attachment->message?->conversation;

        abort_unless(
            $conversation && $conversation->users()->whereKey(Auth::id())->exists(),
            403,
            'Unauthorized access to this file.'
        );

        abort_if($attachment->isExpired(), 410, 'This file has expired and was removed.');

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404, 'File not found.');
    }

    public function view(ChatAttachment $attachment): Response
    {
        $this->authorizeAccess($attachment);

        return response($attachment->contents(), 200, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($attachment->original_name) . '"',
        ]);
    }

    public function download(ChatAttachment $attachment): Response
    {
        $this->authorizeAccess($attachment);

        return response($attachment->contents(), 200, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . addslashes($attachment->original_name) . '"',
        ]);
    }
}
