<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentFolder;
use App\Models\DocumentText;
use App\Models\FolderPolicy;
use App\Models\FolderShortcut;
use App\Models\Office;
use App\Models\User;
use App\Support\DocumentExplorerQuery;
use App\Support\DocumentOrganiser;
use App\Support\DocumentReader;
use App\Support\DocumentSearch;
use App\Support\FolderAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The document explorer: a folder tree on the left, the contents of one place
 * on the right, and the organising actions a file explorer is expected to have
 * -- new folder, rename, delete, move by dragging, drop files in to upload.
 *
 * Reading rules are unchanged: office scope on library uploads, record access
 * and the confidential rules on record files. Organising is limited to an
 * office's document managers and system admins (DocumentOrganiser).
 */
class DocumentExplorer extends Component
{
    /** Where we are: "library", "library:12", "records", "records:2026", "record:80471", "recent". */
    public string $location = 'library';

    /** Whose library is being browsed; admins may look at another office's. */
    public string $office = '';

    /** 'details' or 'tiles'. */
    public string $view = 'details';

    public string $sort = 'name';

    public string $direction = 'asc';

    /** Filters the current place by name. */
    public string $search = '';

    /**
     * 'everywhere' searches names and what the files say, wherever they are;
     * 'folder' just filters the names in front of you.
     */
    public string $scope = 'everywhere';

    /** Keys of the selected entries ("folder-3", "document-9", "attachment-14"). */
    public array $selected = [];

    public string $newFolderName = '';

    #[Locked]
    public string $renamingKey = '';

    public string $renameValue = '';

    /** The folder whose sharing is open, and the grant being added to it. */
    #[Locked]
    public int $sharingFolderId = 0;

    public string $shareSubjectType = 'office';

    public string $shareSubject = '';

    public string $shareLevel = 'view';

    public function mount(): void
    {
        $this->office = (string) (Auth::user()?->office ?? '');
    }

    /*
    |--------------------------------------------------------------------------
    | Moving about
    |--------------------------------------------------------------------------
    */
    public function open(string $location): void
    {
        $this->location = $location;
        $this->selected = [];
        $this->renamingKey = '';
        $this->search = '';
    }

    public function switchOffice(string $office): void
    {
        if (Auth::user()?->isAdmin() || $office === Auth::user()?->office) {
            $this->office = $office;
            $this->open('library');
        }
    }

    public function setSort(string $sort): void
    {
        $this->direction = $this->sort === $sort && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $sort;
    }

    public function select(string $key, bool $add = false): void
    {
        if (! $add) {
            $this->selected = [$key];

            return;
        }

        $this->selected = in_array($key, $this->selected, true)
            ? array_values(array_diff($this->selected, [$key]))
            : [...$this->selected, $key];
    }

    /*
    |--------------------------------------------------------------------------
    | Organising
    |--------------------------------------------------------------------------
    */
    /**
     * Make a folder here and open it for naming. Called with no name it takes
     * "New folder" (then "New folder (2)"…), as a file explorer does.
     */
    public function createFolder(): void
    {
        if ($blocker = $this->organiseBlocker()) {
            $this->banner($blocker, 'danger');

            return;
        }

        $parent = $this->currentFolder();
        $wanted = trim($this->newFolderName);

        if ($wanted !== '') {
            $this->validate(['newFolderName' => 'string|max:120'], [], ['newFolderName' => 'folder name']);

            if ($this->nameTaken($wanted, $parent?->id)) {
                $this->addError('newFolderName', 'A folder of that name is already here.');

                return;
            }
        }

        $name = $wanted !== '' ? $wanted : $this->untakenName('New folder', $parent?->id);

        $folder = DocumentFolder::create([
            'office' => $this->office,
            'parent_id' => $parent?->id,
            'name' => $name,
            'created_by_id' => Auth::id(),
            'created_by' => Auth::user()?->name,
        ]);

        $this->newFolderName = '';

        // Named in place, so the next thing typed is its name.
        if ($wanted === '') {
            $this->renamingKey = "folder-{$folder->id}";
            $this->renameValue = $folder->name;
            $this->selected = [$this->renamingKey];
        } else {
            $this->banner("Folder \"{$folder->name}\" created.");
        }
    }

    /** "New folder", then "New folder (2)" and so on. */
    private function untakenName(string $base, ?int $parentId): string
    {
        $name = $base;

        for ($suffix = 2; $this->nameTaken($name, $parentId); $suffix++) {
            $name = "{$base} ({$suffix})";
        }

        return $name;
    }

    public function startRename(string $key): void
    {
        $entry = $this->entryFor($key);

        if (! $entry) {
            return;
        }

        if ($blocker = $this->organiseBlocker()) {
            $this->banner($blocker, 'danger');

            return;
        }

        $this->renamingKey = $key;
        $this->renameValue = $entry->name;
    }

    public function saveRename(): void
    {
        if ($this->renamingKey === '') {
            return;
        }

        if ($blocker = $this->organiseBlocker()) {
            $this->banner($blocker, 'danger');
            $this->renamingKey = '';

            return;
        }

        $this->validate(['renameValue' => 'required|string|max:255'], [], ['renameValue' => 'name']);

        [$type, $id] = $this->splitKey($this->renamingKey);
        $name = trim($this->renameValue);

        if ($type === 'folder') {
            $folder = DocumentFolder::where('office', $this->office)->find($id);

            if ($folder) {
                if ($this->nameTaken($name, $folder->parent_id, $folder->id)) {
                    $this->addError('renameValue', 'A folder of that name is already here.');

                    return;
                }

                $folder->rename($name);
            }
        } elseif ($type === 'document') {
            Document::where('office', $this->office)->find($id)?->update(['title' => $name]);
        } else {
            // Record files keep the name they were filed under.
            $this->banner('A record file keeps the name it was sent with.', 'danger');
        }

        $this->renamingKey = '';
        $this->renameValue = '';
    }

    public function cancelRename(): void
    {
        $this->renamingKey = '';
        $this->renameValue = '';
    }

    /**
     * Move the dragged entries into a folder. A null target means the library
     * root; record files cannot move, so they are filed as shortcuts instead.
     */
    public function moveInto(?int $folderId, array $keys = []): void
    {
        if ($blocker = $this->organiseBlocker()) {
            $this->banner($blocker, 'danger');

            return;
        }

        $target = $folderId ? DocumentFolder::where('office', $this->office)->find($folderId) : null;

        if ($folderId && ! $target) {
            return;
        }

        $moved = 0;
        $filed = 0;

        foreach ($keys ?: $this->selected as $key) {
            [$type, $id] = $this->splitKey($key);

            if ($type === 'folder') {
                $folder = DocumentFolder::where('office', $this->office)->find($id);

                if (! $folder || $folder->wouldContain($target)) {
                    $this->banner('A folder cannot be moved inside itself.', 'danger');

                    continue;
                }

                if ($this->nameTaken($folder->name, $target?->id, $folder->id)) {
                    $this->banner("\"{$folder->name}\" is already in that folder.", 'danger');

                    continue;
                }

                $folder->moveTo($target);
                $moved++;
            } elseif ($type === 'document') {
                Document::where('office', $this->office)->find($id)?->update(['folder_id' => $target?->id]);
                $moved++;
            } elseif ($type === 'shortcut') {
                if ($target) {
                    FolderShortcut::find($id)?->update(['folder_id' => $target->id]);
                    $moved++;
                }
            } elseif ($type === 'attachment' && $target) {
                // A record file stays on its record; file a pointer to it.
                $attachment = Attachment::with('record')->find($id);

                if ($attachment?->record?->isAccessibleBy(Auth::user())) {
                    FolderShortcut::firstOrCreate(
                        ['folder_id' => $target->id, 'attachment_id' => $attachment->id],
                        ['created_by_id' => Auth::id(), 'created_by' => Auth::user()?->name],
                    );
                    $filed++;
                }
            }
        }

        $this->selected = [];

        $parts = array_filter([
            $moved ? $moved . ' ' . str('item')->plural($moved) . ' moved' : null,
            $filed ? $filed . ' record ' . str('file')->plural($filed) . ' filed here' : null,
        ]);

        if ($parts) {
            $this->banner(ucfirst(implode(', ', $parts)) . '.');
        }
    }

    /**
     * Delete the selected entries. Folders must be empty; record files are
     * never deleted from here -- only the shortcut pointing at them.
     */
    public function deleteSelected(): void
    {
        $canOrganise = $this->mayOrganiseHere();
        $removed = 0;

        foreach ($this->selected as $key) {
            [$type, $id] = $this->splitKey($key);

            // Folders and filed links are part of the tree's shape.
            if (in_array($type, ['folder', 'shortcut'], true) && ! $canOrganise) {
                $this->banner($this->organiseBlocker() ?? 'You cannot change this folder.', 'danger');

                continue;
            }

            if ($type === 'folder') {
                $folder = DocumentFolder::where('office', $this->office)->find($id);

                if (! $folder) {
                    continue;
                }

                $hasChildren = $folder->children()->exists()
                    || $folder->documents()->exists()
                    || $folder->shortcuts()->exists();

                if ($hasChildren) {
                    $this->banner("\"{$folder->name}\" still has things in it.", 'danger');

                    continue;
                }

                $folder->delete();
                $removed++;
            } elseif ($type === 'document') {
                $document = Document::where('office', $this->office)->find($id);

                if ($document?->canBeManagedBy(Auth::user())) {
                    $document->delete();
                    $removed++;
                }
            } elseif ($type === 'shortcut') {
                FolderShortcut::find($id)?->delete();
                $removed++;
            } else {
                $this->banner('A record file can only be removed from its record.', 'danger');
            }
        }

        $this->selected = [];

        if ($removed) {
            $this->banner($removed . ' ' . str('item')->plural($removed) . ' deleted.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Sharing
    |--------------------------------------------------------------------------
    | Who, besides the owning office, may see and use a folder. Grants flow
    | down the tree; a closed folder shows nothing without one.
    */
    public function shareFolder(int $folderId): void
    {
        $folder = DocumentFolder::where('office', $this->office)->find($folderId);

        if (! $folder || ! app(FolderAccess::class)->allows(Auth::user(), $folder, 'manage')) {
            $this->banner('Only the people who look after a folder can share it.', 'danger');

            return;
        }

        $this->sharingFolderId = $folder->id;
        $this->shareSubjectType = 'office';
        $this->shareSubject = '';
        $this->shareLevel = 'view';
        $this->dispatch('open-sharing');
    }

    public function closeSharing(): void
    {
        $this->sharingFolderId = 0;
        $this->resetErrorBag();
    }

    public function addPolicy(): void
    {
        $folder = $this->sharingFolder();

        if (! $folder) {
            return;
        }

        $this->validate([
            'shareSubjectType' => 'required|in:' . implode(',', FolderPolicy::SUBJECTS),
            'shareLevel' => 'required|in:' . implode(',', FolderPolicy::LEVELS),
            'shareSubject' => [
                $this->shareSubjectType === 'everyone' ? 'nullable' : 'required',
                'string',
                'max:191',
            ],
        ], [], [
            'shareSubjectType' => 'who',
            'shareSubject' => 'name',
            'shareLevel' => 'level',
        ]);

        $subject = $this->shareSubjectType === 'everyone' ? null : trim($this->shareSubject);

        if ($this->shareSubjectType === 'office' && ! Office::where('name', $subject)->exists()) {
            $this->addError('shareSubject', 'There is no office by that name.');

            return;
        }

        if ($this->shareSubjectType === 'user' && ! User::whereKey($subject)->exists()) {
            $this->addError('shareSubject', 'There is no such person.');

            return;
        }

        FolderPolicy::updateOrCreate(
            ['folder_id' => $folder->id, 'subject_type' => $this->shareSubjectType, 'subject' => $subject],
            ['level' => $this->shareLevel, 'granted_by_id' => Auth::id(), 'granted_by' => Auth::user()?->name],
        );

        $this->shareSubject = '';
        $this->banner("\"{$folder->name}\" shared.");
    }

    public function removePolicy(int $policyId): void
    {
        $folder = $this->sharingFolder();

        if ($folder) {
            FolderPolicy::where('folder_id', $folder->id)->whereKey($policyId)->delete();
        }
    }

    public function toggleRestricted(): void
    {
        $folder = $this->sharingFolder();

        if (! $folder) {
            return;
        }

        $folder->update(['is_restricted' => ! $folder->is_restricted]);

        $this->banner($folder->is_restricted
            ? "\"{$folder->name}\" is now closed: only those it is shared with can see inside."
            : "\"{$folder->name}\" is open to {$folder->office} again.");
    }

    private function sharingFolder(): ?DocumentFolder
    {
        if (! $this->sharingFolderId) {
            return null;
        }

        $folder = DocumentFolder::where('office', $this->office)->find($this->sharingFolderId);

        return $folder && app(FolderAccess::class)->allows(Auth::user(), $folder, 'manage') ? $folder : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    /**
     * Office members (and admins) may add files to that office's library, and
     * anyone granted "edit" on the folder they are standing in.
     */
    private function mayUpload(): bool
    {
        $user = Auth::user();

        if (! $user || blank($this->office)) {
            return false;
        }

        $folder = $this->currentFolder();

        if ($folder) {
            return app(FolderAccess::class)->allows($user, $folder, 'edit');
        }

        return $user->isAdmin() || $user->office === $this->office;
    }

    /**
     * May this person reshape the folder they are in? Office managers and
     * admins always; others only where they were granted "manage".
     */
    private function mayOrganiseHere(): bool
    {
        $user = Auth::user();

        if (DocumentOrganiser::mayOrganise($user, $this->office)) {
            return true;
        }

        $folder = $this->currentFolder();

        return $folder ? app(FolderAccess::class)->allows($user, $folder, 'manage') : false;
    }

    /** Why this person cannot reshape this folder -- null when they can. */
    private function organiseBlocker(): ?string
    {
        if ($this->mayOrganiseHere()) {
            return null;
        }

        return DocumentOrganiser::blocker(Auth::user(), $this->office)
            ?? 'This folder is looked after by someone else.';
    }

    private function currentFolder(): ?DocumentFolder
    {
        [$kind, $argument] = array_pad(explode(':', $this->location, 2), 2, null);

        return $kind === 'library' && $argument
            ? DocumentFolder::where('office', $this->office)->find((int) $argument)
            : null;
    }

    private function nameTaken(string $name, ?int $parentId, ?int $ignoreId = null): bool
    {
        return DocumentFolder::query()
            ->where('office', $this->office)
            ->where('parent_id', $parentId)
            ->where('name', trim($name))
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function splitKey(string $key): array
    {
        [$type, $id] = array_pad(explode('-', $key, 2), 2, 0);

        return [$type, (int) $id];
    }

    private function entryFor(string $key): ?object
    {
        return $this->entries()->firstWhere('key', $key);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function entries(): \Illuminate\Support\Collection
    {
        return collect($this->browse()['entries']);
    }

    private function browse(): array
    {
        return app(DocumentExplorerQuery::class)
            ->browse(Auth::user(), $this->location, $this->office, $this->sort, $this->direction);
    }

    private function banner(string $message, string $style = 'success'): void
    {
        $this->dispatch('banner-message', style: $style, message: $message);
    }

    /**
     * Read one file now rather than waiting for the scheduled run. Kept to a
     * single file: a scan takes a second or two a page.
     */
    public function readNow(string $key): void
    {
        [$type, $id] = $this->splitKey($key);

        $source = match ($type) {
            'attachment', 'shortcut' => $type === 'shortcut'
                ? FolderShortcut::find($id)?->attachment
                : Attachment::find($id),
            'document' => Document::find($id),
            default => null,
        };

        if (! $source) {
            return;
        }

        // Only what this person could open anyway.
        $allowed = $source instanceof Document
            ? ($source->folder_id
                ? app(FolderAccess::class)->allows(Auth::user(), $source->folder, 'view')
                : $source->isAccessibleBy(Auth::user()))
            : (bool) $source->record?->isAccessibleBy(Auth::user());

        if (! $allowed) {
            $this->banner('You cannot open that file.', 'danger');

            return;
        }

        $row = DocumentText::queue($source instanceof Document ? 'document' : 'attachment', $source->id);
        $read = app(DocumentReader::class)->read($row->fill(['status' => 'pending']));

        $this->banner(match ($read->status) {
            'done' => sprintf(
                'Read %d page(s) by %s — %s can now be searched by its contents.',
                $read->pages,
                $read->method,
                $source instanceof Document ? ($source->title ?: $source->original_name) : $source->original_name,
            ),
            'skipped' => $read->failure ?: 'There is nothing to read in that file.',
            default => $read->failure ?: 'That file could not be read.',
        }, $read->status === 'done' ? 'success' : 'danger');
    }

    /**
     * Queue everything never read, so it can be searched by its contents. The
     * reading itself runs on the schedule; the floating progress bar follows
     * it from any page.
     */
    public function readEverything(): void
    {
        if (! Auth::user()?->isAdmin() && ! Auth::user()?->manages_documents) {
            $this->banner('Only document managers can start a full read.', 'danger');

            return;
        }

        $queued = app(DocumentReader::class)->queueEverything();

        $this->banner($queued === 0
            ? 'Everything has been read already.'
            : "{$queued} file(s) queued. They will be read in the background — the progress bar follows it.");
    }

    public function render()
    {
        $user = Auth::user();
        $needle = trim($this->search);
        $searchingEverywhere = $this->scope === 'everywhere' && mb_strlen($needle) >= 2;

        $result = $this->browse();
        $entries = collect($result['entries']);
        $found = $searchingEverywhere
            ? app(DocumentSearch::class)->find($user, $needle)
            : collect();

        if ($needle !== '' && ! $searchingEverywhere) {
            $lowered = mb_strtolower($needle);
            $entries = $entries->filter(fn (object $entry) => str_contains(mb_strtolower($entry->name), $lowered))->values();
        }

        $sharing = $this->sharingFolder();

        return view('livewire.document-explorer', [
            'entries' => $entries,
            'found' => $found,
            'searching' => $searchingEverywhere,
            // How much is still waiting to be read, so a thin search result
            // can say why.
            'unread' => $searchingEverywhere ? DocumentText::whereIn('status', ['pending', 'failed'])->count() : 0,
            'canStartReading' => (bool) ($user?->isAdmin() || $user?->manages_documents),
            'trail' => $result['trail'],
            'folder' => $result['folder'],
            'denied' => (bool) ($result['denied'] ?? false),
            'tree' => app(DocumentExplorerQuery::class)->tree($this->office, $user),
            'offices' => $user?->isAdmin() ? Office::orderBy('name')->pluck('name') : collect([$this->office]),
            'categories' => DocumentCategory::orderBy('name')->pluck('name', 'id'),
            'canOrganise' => $this->mayOrganiseHere(),
            'canUpload' => $this->mayUpload(),
            'sharing' => $sharing,
            'sharingPolicies' => $sharing ? $sharing->policies()->orderBy('subject_type')->get() : collect(),
            'officeOptions' => Office::orderBy('name')->pluck('name'),
            'peopleOptions' => $sharing
                ? User::orderBy('name')->get(['id', 'name', 'office'])
                : collect(),
        ]);
    }
}
