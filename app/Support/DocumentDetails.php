<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentText;
use App\Models\FolderShortcut;
use App\Models\User;

/**
 * Everything known about one file: where it came from, who uploaded it,
 * whether it has been signed, and whether its contents have been read.
 *
 * Used by the details panel beside the viewer, by the file's own page, and by
 * anything else that needs to describe a file. The same rules as everywhere
 * else decide what is said: a record file needs the record to be open to you,
 * a library file needs its folder to allow you, and a confidential file is not
 * named to anyone not cleared for it.
 */
class DocumentDetails
{
    public function __construct(private FolderAccess $access)
    {
    }

    /**
     * The file a key such as "attachment-14" or "document-3" refers to, or null
     * when it is not this person's to see.
     */
    public function resolve(?User $user, string $key): Attachment|Document|null
    {
        if (! preg_match('/^(attachment|document|shortcut)-(\d+)$/', $key, $parts)) {
            return null;
        }

        [$type, $id] = [$parts[1], (int) $parts[2]];

        if ($type === 'shortcut') {
            $id = (int) (FolderShortcut::find($id)?->attachment_id ?? 0);
            $type = 'attachment';
        }

        if ($type === 'attachment') {
            $attachment = Attachment::with(['record', 'signatures', 'signatureRequests'])->find($id);

            return $attachment && $attachment->record?->isAccessibleBy($user) ? $attachment : null;
        }

        $document = Document::with(['folder', 'category'])->find($id);

        if (! $document) {
            return null;
        }

        $allowed = $document->folder_id
            ? $this->access->allows($user, $document->folder, 'view')
            : $document->isAccessibleBy($user);

        return $allowed ? $document : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(?User $user, Attachment|Document $file): array
    {
        return $file instanceof Attachment
            ? $this->attachment($user, $file)
            : $this->document($file);
    }

    /**
     * @return array<string, mixed>
     */
    private function attachment(?User $user, Attachment $attachment): array
    {
        $record = $attachment->record;
        $locked = (bool) $record?->requiresToken();
        $hidden = $attachment->nameIsHiddenFrom($user, ! $locked);

        $signatures = $attachment->signatures
            ->map(fn ($signature) => [
                'signer' => $signature->signer_name,
                'office' => $signature->signer_office,
                'signed_at' => $signature->signed_at?->format('d M Y g:i A'),
                'code' => $signature->verification_code,
                'pages' => collect($signature->placements ?? [])->pluck('page')->unique()->sort()->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'key' => "attachment-{$attachment->id}",
            'name' => $attachment->displayNameFor($user, ! $locked),
            'kind' => 'Record file',
            'mime' => (string) $attachment->mime_type,
            'view_url' => $locked ? null : route('attachments.view', $attachment->id),
            'facts' => array_values(array_filter([
                ['label' => 'Record', 'value' => $record?->reference],
                ['label' => 'Subject', 'value' => $record?->isMaskedFor($user) ? 'Confidential — hidden from you' : $record?->subject],
                ['label' => 'Origin', 'value' => $record?->origin],
                ['label' => 'Owner', 'value' => $record?->owner],
                ['label' => 'Type', 'value' => $attachment->extension],
                ['label' => 'Size', 'value' => $attachment->human_size],
                ['label' => 'Uploaded by', 'value' => $attachment->uploaded_by],
                ['label' => 'Uploaded', 'value' => $attachment->created_at?->format('d M Y g:i A')],
                $attachment->isConfidential() ? ['label' => 'Handling', 'value' => 'Confidential — view only'] : null,
            ])),
            'signatures' => $hidden ? [] : $signatures,
            'awaiting_signatures' => $attachment->signatureRequests->whereNull('signed_at')->count(),
            'reading' => $this->reading('attachment', $attachment->id, $hidden),
            'record_url' => $record ? route('show-transactions', $record->id) : null,
            'download_url' => ($locked || $attachment->isConfidential()) ? null : route('attachments.download', $attachment->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function document(Document $document): array
    {
        return [
            'key' => "document-{$document->id}",
            'name' => $document->title ?: $document->original_name,
            'kind' => 'Library file',
            'mime' => (string) $document->mime_type,
            'view_url' => route('documents.view', $document->id),
            'facts' => array_values(array_filter([
                ['label' => 'Office', 'value' => $document->office],
                ['label' => 'Folder', 'value' => $document->folder?->path ?: 'All files'],
                ['label' => 'Category', 'value' => $document->category?->name],
                ['label' => 'File name', 'value' => $document->original_name],
                ['label' => 'Type', 'value' => $document->extension],
                ['label' => 'Size', 'value' => $document->human_size],
                ['label' => 'Uploaded by', 'value' => $document->uploaded_by],
                ['label' => 'Uploaded', 'value' => $document->created_at?->format('d M Y g:i A')],
                $document->description ? ['label' => 'Description', 'value' => $document->description] : null,
            ])),
            'signatures' => [],
            'awaiting_signatures' => 0,
            'reading' => $this->reading('document', $document->id, false),
            'record_url' => null,
            'download_url' => route('documents.download', $document->id),
        ];
    }

    /**
     * Whether the file has been read for searching, and how.
     *
     * @return array<string, mixed>
     */
    private function reading(string $type, int $id, bool $hidden): array
    {
        $row = DocumentText::where('source_type', $type)->where('source_id', $id)->first();

        if (! $row) {
            return ['status' => 'not queued', 'summary' => 'Not read yet — it cannot be found by its contents.'];
        }

        return [
            'status' => $row->status,
            'summary' => match ($row->status) {
                'done' => $hidden
                    ? 'Read, but withheld from you.'
                    : sprintf('Read by %s · %d page(s) · %s characters', $row->method, $row->pages, number_format($row->characters)),
                'pending' => 'Waiting to be read.',
                'skipped' => $row->failure ?: 'Nothing to read in this kind of file.',
                default => $row->failure ?: 'Could not be read.',
            },
        ];
    }
}
