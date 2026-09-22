<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentText;
use App\Support\DocumentReader;
use App\Support\FolderAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Uploads that arrive a piece at a time.
 *
 * A large scan sent as one request is at the mercy of PHP's upload limits and
 * of the office's connection: one stall and the whole thing starts again.
 * Here the browser slices the file, sends the pieces one by one, and the last
 * piece assembles them. A dropped connection costs one piece, not the file.
 *
 * Pieces live under storage/app/chunk-uploads/{user}/{upload}, are never
 * served to anyone, and are swept up if abandoned (documents:purge-chunks).
 */
class ChunkedUploadController extends Controller
{
    /** Each piece the browser sends. Small enough for any php.ini worth the name. */
    public const CHUNK_BYTES = 2 * 1024 * 1024;

    /** The largest file that may be assembled from pieces. */
    public const MAX_BYTES = 100 * 1024 * 1024;

    private const ALLOWED = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * Take one piece of a file.
     */
    public function chunk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upload' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{8,64}$/'],
            'index' => ['required', 'integer', 'min:0', 'max:10000'],
            'total' => ['required', 'integer', 'min:1', 'max:10001'],
            'chunk' => ['required', 'file', 'max:' . (int) ((self::CHUNK_BYTES * 1.5) / 1024)],
        ]);

        $directory = $this->directoryFor($validated['upload']);

        Storage::disk('local')->putFileAs(
            $directory,
            $validated['chunk'],
            str_pad((string) $validated['index'], 5, '0', STR_PAD_LEFT),
        );

        return response()->json([
            'received' => (int) $validated['index'] + 1,
            'of' => (int) $validated['total'],
        ]);
    }

    /**
     * Put the pieces together, store the file, and read it straight away so it
     * can be searched by its contents without waiting for the next sweep.
     */
    public function finish(Request $request, DocumentReader $reader): JsonResponse
    {
        $validated = $request->validate([
            'upload' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{8,64}$/'],
            'name' => ['required', 'string', 'max:255'],
            'office' => ['required', 'string', 'max:100'],
            'folder' => ['nullable', 'integer'],
            'category' => ['nullable', 'integer', 'exists:document_categories,id'],
        ]);

        $user = Auth::user();

        // Optional fields are simply absent when the browser sends nothing.
        $folder = ($validated['folder'] ?? null) ? DocumentFolder::find($validated['folder']) : null;

        if (! $this->mayUpload($validated['office'], $folder)) {
            $this->discard($validated['upload']);

            return response()->json(['message' => 'You cannot add files here.'], 403);
        }

        $extension = strtolower(pathinfo($validated['name'], PATHINFO_EXTENSION));

        if (! isset(self::ALLOWED[$extension])) {
            $this->discard($validated['upload']);

            return response()->json(['message' => 'That kind of file cannot be uploaded.'], 422);
        }

        $assembled = $this->assemble($validated['upload'], $extension);

        if (! $assembled) {
            return response()->json(['message' => 'The upload was incomplete. Please try again.'], 422);
        }

        // Trust the bytes, not the name: the extension must match what is inside.
        $mime = (string) (mime_content_type($assembled) ?: '');

        if (! $this->mimeMatches($mime, $extension)) {
            @unlink($assembled);

            return response()->json(['message' => 'That file is not what its name says it is.'], 422);
        }

        $document = Document::storeEncrypted(
            new UploadedFile($assembled, $validated['name'], $mime, null, true),
            $user,
            [
                'title' => $validated['name'],
                'folder_id' => $folder?->id,
                'document_category_id' => $validated['category'] ?? null,
            ],
        );

        @unlink($assembled);

        // storeEncrypted files it under the uploader's own office; an admin may
        // be standing in another office's library.
        if ($document->office !== $validated['office']) {
            $document->update(['office' => $validated['office']]);
        }

        // Read it now, rather than waiting for the scheduled sweep. With reading
        // switched off it is still queued (by the model), and read once it is on.
        $read = null;

        if ($reader->readable($mime) && \App\Support\Modules::enabled(\App\Support\Modules::DOCUMENT_READING)) {
            $row = $reader->read(DocumentText::queue('document', $document->id, $document->sha256));

            $read = [
                'status' => $row->status,
                'method' => $row->method,
                'pages' => $row->pages,
                'characters' => $row->characters,
            ];
        }

        return response()->json([
            'id' => $document->id,
            'name' => $document->title,
            'read' => $read,
        ]);
    }

    private function mayUpload(string $office, ?DocumentFolder $folder): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($folder) {
            return $folder->office === $office && app(FolderAccess::class)->allows($user, $folder, 'edit');
        }

        return $user->isAdmin() || $user->office === $office;
    }

    /**
     * Join the pieces in order into one temporary file, and clear them away.
     */
    private function assemble(string $upload, string $extension): ?string
    {
        $disk = Storage::disk('local');
        $directory = $this->directoryFor($upload);
        $pieces = collect($disk->files($directory))->sort()->values();

        if ($pieces->isEmpty()) {
            return null;
        }

        $target = tempnam(sys_get_temp_dir(), 'dts-upload-') . '.' . $extension;
        $handle = fopen($target, 'wb');
        $size = 0;

        foreach ($pieces as $piece) {
            $stream = $disk->readStream($piece);

            if (! $stream) {
                continue;
            }

            $size += stream_copy_to_stream($stream, $handle);
            fclose($stream);

            if ($size > self::MAX_BYTES) {
                fclose($handle);
                @unlink($target);
                $this->discard($upload);

                return null;
            }
        }

        fclose($handle);
        $this->discard($upload);

        return $size > 0 ? $target : null;
    }

    private function mimeMatches(string $mime, string $extension): bool
    {
        $expected = self::ALLOWED[$extension] ?? null;

        if ($mime === $expected) {
            return true;
        }

        // Office files are zip archives underneath, and are often reported so.
        return in_array($extension, ['docx', 'xlsx'], true)
            && in_array($mime, ['application/zip', 'application/octet-stream'], true);
    }

    private function directoryFor(string $upload): string
    {
        return 'chunk-uploads/' . Auth::id() . '/' . Str::of($upload)->replaceMatches('/[^A-Za-z0-9-]/', '');
    }

    private function discard(string $upload): void
    {
        Storage::disk('local')->deleteDirectory($this->directoryFor($upload));
    }
}
