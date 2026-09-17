<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\FolderShortcut;
use App\Models\Record;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * What the explorer shows at one place in the tree.
 *
 * A place is addressed by a short string, the way a path addresses a folder:
 *
 *   library            an office's root
 *   library:12         one of its folders
 *   records            the years that office has records for
 *   records:2026       the records logged in that year
 *   record:80471       one record's files
 *   recent             everything the user may open, newest first
 *
 * Folders hold library uploads. Record attachments are never moved into them:
 * they stay on their record and are filed here as shortcuts, so routing and
 * the audit trail are untouched.
 */
class DocumentExplorerQuery
{
    public function __construct(private FolderAccess $access)
    {
    }

    /**
     * Everything at one place: the entries to list and the trail above them.
     *
     * @return array{entries: Collection<int, object>, trail: array<int, array{label: string, location: string}>, folder: ?DocumentFolder, office: string}
     */
    public function browse(User $user, string $location, string $office, string $sort = 'name', string $direction = 'asc'): array
    {
        [$kind, $argument] = $this->parse($location);

        $result = match ($kind) {
            'records' => $this->records($user, $office, $argument),
            'record' => $this->recordFiles($user, (int) $argument),
            'recent' => $this->recent($user, $office),
            default => $this->library($user, $office, $argument === null ? null : (int) $argument),
        };

        $result['entries'] = $this->sortEntries($result['entries'], $sort, $direction);
        $result['office'] = $office;

        return $result;
    }

    /**
     * The folder tree of one office, as a flat list the sidebar can indent.
     *
     * @return Collection<int, object>
     */
    public function tree(string $office, ?User $user = null): Collection
    {
        $folders = DocumentFolder::treeFor($office)
            ->filter(fn (DocumentFolder $folder) => $this->access->allows($user, $folder, 'view'))
            ->values();

        $documentCounts = Document::query()
            ->where('office', $office)
            ->whereNotNull('folder_id')
            ->selectRaw('folder_id, COUNT(*) as total')
            ->groupBy('folder_id')
            ->pluck('total', 'folder_id');

        $shortcutCounts = FolderShortcut::query()
            ->whereIn('folder_id', $folders->pluck('id'))
            ->selectRaw('folder_id, COUNT(*) as total')
            ->groupBy('folder_id')
            ->pluck('total', 'folder_id');

        return $folders->map(fn (DocumentFolder $folder) => (object) [
            'id' => $folder->id,
            'name' => $folder->name,
            'parent_id' => $folder->parent_id,
            'location' => "library:{$folder->id}",
            'depth' => substr_count($folder->path, '/') - 1,
            'files' => (int) ($documentCounts[$folder->id] ?? 0) + (int) ($shortcutCounts[$folder->id] ?? 0),
        ]);
    }

    /**
     * @return array{entries: Collection<int, object>, trail: array<int, array{label: string, location: string}>, folder: ?DocumentFolder}
     */
    private function library(User $user, string $office, ?int $folderId): array
    {
        $folder = $folderId ? DocumentFolder::where('office', $office)->find($folderId) : null;

        // A folder nobody granted you is not there as far as you are concerned.
        if ($folder && ! $this->access->allows($user, $folder, 'view')) {
            return [
                'entries' => collect(),
                'trail' => [['label' => "{$office} Library", 'location' => 'library']],
                'folder' => null,
                'denied' => true,
            ];
        }

        $folders = DocumentFolder::query()
            ->where('office', $office)
            ->where('parent_id', $folder?->id)
            ->orderBy('name')
            ->get()
            ->filter(fn (DocumentFolder $child) => $this->access->allows($user, $child, 'view'))
            ->map(fn (DocumentFolder $child) => $this->folderEntry($child));

        $documents = Document::query()
            ->with('category')
            ->where('office', $office)
            ->where('folder_id', $folder?->id)
            ->get()
            ->map(fn (Document $document) => $this->documentEntry($document));

        // Shortcuts to record files filed in this folder (never at the root).
        $shortcuts = $folder
            ? FolderShortcut::query()
                ->with('attachment.record')
                ->where('folder_id', $folder->id)
                ->get()
                ->filter(fn (FolderShortcut $shortcut) => $shortcut->attachment
                    && $shortcut->attachment->record?->isAccessibleBy($user))
                ->map(fn (FolderShortcut $shortcut) => $this->attachmentEntry($shortcut->attachment, $user, $shortcut->id))
            : collect();

        $trail = [['label' => "{$office} Library", 'location' => 'library']];

        foreach ($folder?->breadcrumb() ?? [] as $crumb) {
            $trail[] = ['label' => $crumb['name'], 'location' => "library:{$crumb['id']}"];
        }

        return [
            'entries' => $folders->concat($documents)->concat($shortcuts)->values(),
            'trail' => $trail,
            'folder' => $folder,
        ];
    }

    /**
     * Years, or the records logged in one year. Records read as folders here:
     * they are opened, not moved.
     */
    private function records(User $user, string $office, ?string $year): array
    {
        $accessible = $this->accessibleRecords($user);

        if ($year === null) {
            $years = $accessible
                ->map(fn (Record $record) => $record->created_at?->format('Y') ?: '—')
                ->unique()
                ->sortDesc()
                ->values()
                ->map(fn (string $value) => (object) [
                    'type' => 'folder',
                    'id' => 0,
                    'key' => "year-{$value}",
                    'name' => $value,
                    'location' => "records:{$value}",
                    'meta' => 'Records',
                    'size' => null,
                    'modified' => null,
                    'can_move' => false,
                ]);

            return [
                'entries' => $years,
                'trail' => [['label' => 'Records', 'location' => 'records']],
                'folder' => null,
            ];
        }

        $entries = $accessible
            ->filter(fn (Record $record) => ($record->created_at?->format('Y') ?: '—') === $year)
            ->map(fn (Record $record) => (object) [
                'type' => 'folder',
                'id' => $record->id,
                'key' => "record-{$record->id}",
                'name' => $record->reference ?: "Record {$record->id}",
                'location' => "record:{$record->id}",
                'meta' => $record->isMaskedFor($user) ? 'Confidential — hidden from you' : ($record->subject ?: ''),
                'size' => null,
                'modified' => $record->created_at,
                'can_move' => false,
            ])
            ->values();

        return [
            'entries' => $entries,
            'trail' => [
                ['label' => 'Records', 'location' => 'records'],
                ['label' => $year, 'location' => "records:{$year}"],
            ],
            'folder' => null,
        ];
    }

    private function recordFiles(User $user, int $recordId): array
    {
        $record = Record::with('attachments')->find($recordId);

        if (! $record || ! $record->isAccessibleBy($user)) {
            return ['entries' => collect(), 'trail' => [['label' => 'Records', 'location' => 'records']], 'folder' => null];
        }

        $entries = $record->attachments
            ->map(fn (Attachment $attachment) => $this->attachmentEntry($attachment, $user))
            ->values();

        return [
            'entries' => $entries,
            'trail' => [
                ['label' => 'Records', 'location' => 'records'],
                ['label' => $record->created_at?->format('Y') ?: '—', 'location' => 'records:' . ($record->created_at?->format('Y') ?: '—')],
                ['label' => $record->reference ?: "Record {$record->id}", 'location' => "record:{$record->id}"],
            ],
            'folder' => null,
        ];
    }

    /** The 60 newest files this user may open, wherever they live. */
    private function recent(User $user, string $office): array
    {
        $hidden = $this->access->hiddenFolderIds($user, $office);

        $documents = Document::query()
            ->where('office', $office)
            ->when($hidden, fn ($query) => $query->whereNotIn('folder_id', $hidden))
            ->latest('created_at')
            ->limit(60)
            ->get()
            ->map(fn (Document $document) => $this->documentEntry($document));

        $attachments = Attachment::query()
            ->with('record')
            ->latest('created_at')
            ->limit(200)
            ->get()
            ->filter(fn (Attachment $attachment) => $attachment->record?->isAccessibleBy($user))
            ->take(60)
            ->map(fn (Attachment $attachment) => $this->attachmentEntry($attachment, $user));

        $entries = $documents->concat($attachments)
            ->sortByDesc(fn (object $entry) => $entry->modified)
            ->take(60)
            ->values();

        return [
            'entries' => $entries,
            'trail' => [['label' => 'Recent', 'location' => 'recent']],
            'folder' => null,
        ];
    }

    /**
     * @return Collection<int, Record>
     */
    private function accessibleRecords(User $user): Collection
    {
        return Record::query()
            ->latest('created_at')
            ->limit(500)
            ->get()
            ->filter(fn (Record $record) => $record->isAccessibleBy($user))
            ->values();
    }

    private function folderEntry(DocumentFolder $folder): object
    {
        $shared = $folder->policies()->count();

        return (object) [
            'type' => 'folder',
            'id' => $folder->id,
            'key' => "folder-{$folder->id}",
            'name' => $folder->name,
            'location' => "library:{$folder->id}",
            'restricted' => (bool) $folder->is_restricted,
            'meta' => match (true) {
                $folder->is_restricted && $shared > 0 => 'Closed · shared with ' . $shared,
                (bool) $folder->is_restricted => 'Closed folder',
                $shared > 0 => 'Shared with ' . $shared,
                default => 'Folder',
            },
            'size' => null,
            'modified' => $folder->updated_at,
            'can_move' => true,
        ];
    }

    private function documentEntry(Document $document): object
    {
        return (object) [
            'type' => 'file',
            'source' => 'document',
            'id' => $document->id,
            'key' => "document-{$document->id}",
            'name' => $document->title ?: $document->original_name,
            'location' => null,
            'meta' => $document->category?->name ?: 'Library upload',
            'extension' => $document->extension,
            'kind' => $this->kind((string) $document->mime_type),
            'size' => (int) $document->size,
            'modified' => $document->created_at,
            'view_url' => route('documents.view', $document->id),
            'download_url' => route('documents.download', $document->id),
            'record_url' => null,
            'confidential' => false,
            'can_move' => true,
        ];
    }

    private function attachmentEntry(Attachment $attachment, User $user, ?int $shortcutId = null): object
    {
        $record = $attachment->record;
        $locked = $record?->requiresToken() ?? false;
        $hidden = $attachment->nameIsHiddenFrom($user, ! $locked);

        return (object) [
            'type' => 'file',
            'source' => $shortcutId ? 'shortcut' : 'attachment',
            'id' => $attachment->id,
            'shortcut_id' => $shortcutId,
            'key' => ($shortcutId ? "shortcut-{$shortcutId}" : "attachment-{$attachment->id}"),
            'name' => $attachment->displayNameFor($user, ! $locked),
            'location' => null,
            'meta' => $record?->reference ?: 'Record file',
            'extension' => $attachment->extension,
            'kind' => $this->kind((string) $attachment->mime_type),
            'size' => (int) $attachment->size,
            'modified' => $attachment->created_at,
            'view_url' => $locked ? null : route('attachments.view', $attachment->id),
            'download_url' => ($locked || $attachment->isConfidential()) ? null : route('attachments.download', $attachment->id),
            'record_url' => $record ? route('show-transactions', $record->id) : null,
            'confidential' => $attachment->isConfidential(),
            'name_hidden' => $hidden,
            // The file belongs to its record; only the shortcut can be moved.
            'can_move' => false,
        ];
    }

    private function kind(string $mime): string
    {
        return match (true) {
            $mime === 'application/pdf' => 'pdf',
            str_starts_with($mime, 'image/') => 'image',
            default => 'other',
        };
    }

    /**
     * @param  Collection<int, object>  $entries
     * @return Collection<int, object>
     */
    private function sortEntries(Collection $entries, string $sort, string $direction): Collection
    {
        $key = match ($sort) {
            'size' => fn (object $entry) => $entry->size ?? -1,
            'modified' => fn (object $entry) => $entry->modified?->timestamp ?? 0,
            'type' => fn (object $entry) => $entry->extension ?? '',
            default => fn (object $entry) => mb_strtolower($entry->name),
        };

        // Folders first, the way a file explorer lists them.
        $sorted = $direction === 'desc'
            ? $entries->sortByDesc($key)
            : $entries->sortBy($key);

        return $sorted
            ->sortBy(fn (object $entry) => $entry->type === 'folder' ? 0 : 1)
            ->values();
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function parse(string $location): array
    {
        [$kind, $argument] = array_pad(explode(':', $location, 2), 2, null);

        return [$kind ?: 'library', $argument];
    }
}
