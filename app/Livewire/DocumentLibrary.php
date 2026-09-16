<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Office;
use App\Models\User;
use App\Support\DocumentLibraryQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Document library: browse, search and filter every file a user may open
 * (routed attachments and library uploads), preview and download them, upload
 * files into the office library and organise them by category.
 */
class DocumentLibrary extends Component
{
    use WithFileUploads;
    use WithPagination;

    private const FILTERS = ['search', 'office', 'category', 'type', 'source', 'signed', 'from', 'to'];

    public string $search = '';

    public string $office = '';

    public string $category = '';

    public string $type = '';

    public string $source = '';

    public string $signed = '';

    public string $from = '';

    public string $to = '';

    public int $perPage = 25;

    /** Upload form. */
    public $uploads = [];

    public string $uploadTitle = '';

    public string $uploadCategory = '';

    public string $uploadDescription = '';

    /** Edit form: a library upload (title, category, description) or a routed file (category only). */
    #[Locked]
    public string $editingSource = '';

    #[Locked]
    public int $editingId = 0;

    public string $editTitle = '';

    public string $editCategory = '';

    public string $editDescription = '';

    public function updated(string $property): void
    {
        if (in_array($property, [...self::FILTERS, 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(self::FILTERS);
        $this->resetPage();
    }

    // Not "upload"/"removeUpload": Livewire reserves those names for $wire's
    // own file-upload helpers, so the browser would never reach these actions.
    public function discardUpload(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
    }

    public function saveUploads(): void
    {
        $user = Auth::user();

        // Errors from a previous attempt (e.g. a rejected file) no longer apply.
        $this->resetErrorBag();

        if (blank($user->office)) {
            $this->addError('uploads', 'Your account is not assigned to an office.');

            return;
        }

        $this->validate([
            'uploads' => 'required|array|min:1|max:10',
            'uploads.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
            'uploadTitle' => 'nullable|string|max:255',
            'uploadCategory' => 'nullable|integer|exists:document_categories,id',
            'uploadDescription' => 'nullable|string|max:2000',
        ], [], [
            'uploads' => 'files',
            'uploads.*' => 'file',
            'uploadTitle' => 'title',
            'uploadCategory' => 'category',
            'uploadDescription' => 'description',
        ]);

        $count = count($this->uploads);

        foreach ($this->uploads as $file) {
            $title = $count === 1 && filled($this->uploadTitle)
                ? trim($this->uploadTitle)
                : (pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: $file->getClientOriginalName());

            Document::storeEncrypted($file, $user, [
                'title' => Str::limit($title, 250, ''),
                'document_category_id' => filled($this->uploadCategory) ? (int) $this->uploadCategory : null,
                'description' => filled($this->uploadDescription) ? trim($this->uploadDescription) : null,
            ]);
        }

        $this->reset(['uploads', 'uploadTitle', 'uploadCategory', 'uploadDescription']);
        $this->resetPage();

        $this->dispatch('close-document-upload');
        $this->banner($count === 1
            ? "Document uploaded to the {$user->office} library."
            : "{$count} documents uploaded to the {$user->office} library.");
    }

    public function editItem(string $source, int $id): void
    {
        $item = $this->manageableItem($source, $id);

        if (! $item) {
            $this->banner('You cannot edit this file.', 'danger');

            return;
        }

        $this->resetErrorBag();
        $this->editingSource = $source;
        $this->editingId = $id;
        $this->editCategory = (string) ($item->document_category_id ?? '');
        $this->editTitle = $item instanceof Document ? $item->title : $item->original_name;
        $this->editDescription = $item instanceof Document ? (string) $item->description : '';

        $this->dispatch('open-document-edit');
    }

    public function saveEdit(): void
    {
        $item = $this->manageableItem($this->editingSource, $this->editingId);

        if (! $item) {
            $this->banner('You cannot edit this file.', 'danger');

            return;
        }

        $rules = ['editCategory' => 'nullable|integer|exists:document_categories,id'];

        if ($item instanceof Document) {
            $rules += [
                'editTitle' => 'required|string|max:255',
                'editDescription' => 'nullable|string|max:2000',
            ];
        }

        $this->validate($rules, [], [
            'editCategory' => 'category',
            'editTitle' => 'title',
            'editDescription' => 'description',
        ]);

        $attributes = ['document_category_id' => filled($this->editCategory) ? (int) $this->editCategory : null];

        if ($item instanceof Document) {
            $attributes += [
                'title' => trim($this->editTitle),
                'description' => filled($this->editDescription) ? trim($this->editDescription) : null,
            ];
        }

        $item->update($attributes);

        $this->reset(['editingSource', 'editingId', 'editTitle', 'editCategory', 'editDescription']);

        $this->dispatch('close-document-edit');
        $this->banner('Document details saved.');
    }

    public function deleteDocument(int $id): void
    {
        $document = $this->manageableItem('document', $id);

        if (! $document) {
            $this->banner('You cannot delete this document.', 'danger');

            return;
        }

        $document->delete();

        $this->banner('Document deleted.');
    }

    /**
     * Library uploads: their office or an admin. Routed files: the office that
     * manages the record's attachments (owner/origin) or an admin.
     */
    private function manageableItem(string $source, int $id): Attachment|Document|null
    {
        $user = Auth::user();

        if ($source === 'document') {
            $document = Document::find($id);

            return $document?->canBeManagedBy($user) ? $document : null;
        }

        if ($source === 'attachment') {
            $attachment = Attachment::with('record')->find($id);

            return $attachment?->record?->canManageAttachments($user) ? $attachment : null;
        }

        return null;
    }

    private function banner(string $message, string $style = 'success'): void
    {
        $this->dispatch('banner-message', style: $style, message: $message);
    }

    /**
     * @return array<string, string>
     */
    private function filters(): array
    {
        return collect(self::FILTERS)->mapWithKeys(fn (string $filter) => [$filter => $this->{$filter}])->all();
    }

    /**
     * One library row as the view needs it: labels, links and what the user may do.
     *
     * @param  Collection<int, string>  $categories
     */
    private function present(object $row, User $user, Collection $categories): object
    {
        $isAttachment = $row->source === 'attachment';
        $confidential = (bool) $row->is_confidential;
        $tokenRequired = (bool) $row->requires_token;
        $mime = (string) $row->mime_type;

        $recordUrl = null;

        if ($isAttachment) {
            $recordUrl = $row->record_owner === $user->office
                ? route('outgoing-transactions', $row->record_id)
                : route('show-transactions', $row->record_id);
        }

        $viewUrl = $isAttachment ? route('attachments.view', $row->id) : route('documents.view', $row->id);
        $downloadUrl = $isAttachment ? route('attachments.download', $row->id) : route('documents.download', $row->id);

        return (object) [
            'key' => "{$row->source}-{$row->id}",
            'source' => $row->source,
            'id' => (int) $row->id,
            'title' => $row->title,
            'original_name' => $row->original_name,
            'extension' => strtoupper(pathinfo((string) $row->original_name, PATHINFO_EXTENSION) ?: 'FILE'),
            'size' => Document::formatBytes((int) $row->size),
            'kind' => $mime === 'application/pdf' ? 'pdf' : (str_starts_with($mime, 'image/') ? 'image' : 'other'),
            'office' => $row->office,
            'category' => $categories[$row->document_category_id] ?? null,
            'reference' => $row->reference,
            'subject' => $row->subject,
            'description' => $row->description,
            'record_url' => $recordUrl,
            'confidential' => $confidential,
            'token_required' => $tokenRequired,
            'signed' => $row->signed_at !== null,
            'uploaded_by' => $row->uploaded_by,
            'uploaded_at' => Carbon::parse($row->created_at),
            // Token-gated files open from the record page after the token is entered.
            'view_url' => $tokenRequired ? null : $viewUrl,
            // Confidential files are view-only.
            'download_url' => ($tokenRequired || $confidential) ? null : $downloadUrl,
            'can_manage' => $user->isAdmin() || ($isAttachment
                ? in_array($user->office, [$row->record_owner, $row->record_origin], true)
                : $row->office === $user->office),
        ];
    }

    public function render(DocumentLibraryQuery $library)
    {
        $user = Auth::user();
        $categories = DocumentCategory::orderBy('name')->pluck('name', 'id');
        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;

        $rows = $library->paginate($user, $this->filters(), $perPage)
            ->through(fn (object $row) => $this->present($row, $user, $categories));

        return view('livewire.document-library', [
            'rows' => $rows,
            'categories' => $categories,
            'offices' => Office::orderBy('name')->pluck('name'),
            'types' => DocumentLibraryQuery::TYPES,
            'sources' => DocumentLibraryQuery::SOURCES,
            'hasFilters' => collect(self::FILTERS)->contains(fn (string $filter) => $this->{$filter} !== ''),
        ]);
    }
}
