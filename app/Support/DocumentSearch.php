<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\DocumentText;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Finds documents by what they say, not only by what they are called.
 *
 * Matching is done against the text pulled out of each file (DocumentReader).
 * Every hit is then put through the same access rules as everywhere else: a
 * record file must be one the searcher could open, a library file must be in a
 * folder they may see, and a confidential file is named only to those cleared
 * for it. Nothing is found that could not have been browsed to.
 */
class DocumentSearch
{
    public function __construct(private FolderAccess $access)
    {
    }

    /**
     * @return Collection<int, object>
     */
    public function find(User $user, string $needle, int $limit = 50): Collection
    {
        $needle = trim($needle);

        if (mb_strlen($needle) < 2) {
            return collect();
        }

        // With reading switched off, only names are searched.
        $matches = Modules::enabled(Modules::DOCUMENT_READING)
            ? $this->matchingTexts($needle, $limit * 4)
            : collect();

        // Names as well as contents: one search box, both kinds of match, with
        // a name match ranked above a passing mention in the text.
        $byName = $this->namedLike($needle, $limit * 2);

        $attachments = $this->attachmentHits($user, $matches, $needle, $byName['attachments']);
        $documents = $this->documentHits($user, $matches, $needle, $byName['documents']);

        return $attachments->concat($documents)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Files whose name matches, so the search box finds "memo.pdf" as readily
     * as a phrase inside it.
     *
     * @return array{attachments: Collection<int, int>, documents: Collection<int, int>}
     */
    private function namedLike(string $needle, int $limit): array
    {
        $like = '%' . $needle . '%';

        return [
            'attachments' => Attachment::query()
                ->where('original_name', 'like', $like)
                ->limit($limit)
                ->pluck('id'),
            'documents' => Document::query()
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('original_name', 'like', $like))
                ->limit($limit)
                ->pluck('id'),
        ];
    }

    /**
     * Rows whose text matches, newest-read first.
     *
     * @return Collection<int, DocumentText>
     */
    private function matchingTexts(string $needle, int $limit): Collection
    {
        $query = DocumentText::query()->where('status', 'done');

        if (DB::connection()->getDriverName() === 'mysql') {
            // The full-text index does the work; the score orders the results.
            $query
                ->selectRaw('*, MATCH(text) AGAINST (? IN NATURAL LANGUAGE MODE) as relevance', [$needle])
                ->whereRaw('MATCH(text) AGAINST (? IN NATURAL LANGUAGE MODE)', [$needle])
                ->orderByDesc('relevance');
        } else {
            // SQLite (tests) has no such index.
            $query->where('text', 'like', '%' . $needle . '%');
        }

        return $query->limit($limit)->get();
    }

    /**
     * @param  Collection<int, DocumentText>  $matches
     * @return Collection<int, object>
     */
    private function attachmentHits(User $user, Collection $matches, string $needle, Collection $named): Collection
    {
        $ids = $matches->where('source_type', 'attachment')->pluck('source_id')->concat($named)->unique();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Attachment::with('record')
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn (Attachment $attachment) => $attachment->record?->isAccessibleBy($user))
            ->map(function (Attachment $attachment) use ($matches, $needle, $user, $named) {
                $text = $matches->firstWhere(fn (DocumentText $row) => $row->source_type === 'attachment' && $row->source_id === $attachment->id);
                $record = $attachment->record;
                $locked = $record?->requiresToken() ?? false;
                $hidden = $attachment->nameIsHiddenFrom($user, ! $locked);
                $byName = $named->contains($attachment->id);

                return (object) [
                    'key' => "attachment-{$attachment->id}",
                    'name' => $attachment->displayNameFor($user, ! $locked),
                    'where' => $record?->reference ?: 'Record file',
                    // A confidential file is not quoted back to someone who may
                    // not even know its name.
                    'snippet' => $hidden
                        ? 'Confidential — hidden from you'
                        : ($text?->snippet($needle) ?: ($byName ? 'Matched the file name.' : null)),
                    'matched' => $text ? ($byName ? 'name and contents' : 'contents') : 'name',
                    'kind' => str_starts_with((string) $attachment->mime_type, 'image/') ? 'image' : 'pdf',
                    'view_url' => $locked ? null : route('attachments.view', $attachment->id),
                    'record_url' => $record ? route('show-transactions', $record->id) : null,
                    'score' => $this->score($text, $byName),
                    'name_hidden' => $hidden,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, DocumentText>  $matches
     * @return Collection<int, object>
     */
    private function documentHits(User $user, Collection $matches, string $needle, Collection $named): Collection
    {
        $ids = $matches->where('source_type', 'document')->pluck('source_id')->concat($named)->unique();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Document::with('folder')
            ->whereIn('id', $ids)
            ->get()
            ->filter(function (Document $document) use ($user) {
                return $document->folder_id
                    ? $this->access->allows($user, $document->folder, 'view')
                    : $document->isAccessibleBy($user);
            })
            ->map(function (Document $document) use ($matches, $needle, $named) {
                $text = $matches->firstWhere(fn (DocumentText $row) => $row->source_type === 'document' && $row->source_id === $document->id);
                $byName = $named->contains($document->id);

                return (object) [
                    'key' => "document-{$document->id}",
                    'name' => $document->title ?: $document->original_name,
                    'where' => $document->office . ($document->folder?->path ?? ''),
                    'snippet' => $text?->snippet($needle) ?: ($byName ? 'Matched the file name.' : null),
                    'matched' => $text ? ($byName ? 'name and contents' : 'contents') : 'name',
                    'kind' => str_starts_with((string) $document->mime_type, 'image/') ? 'image' : 'pdf',
                    'view_url' => route('documents.view', $document->id),
                    'record_url' => null,
                    'score' => $this->score($text, $byName),
                    'name_hidden' => false,
                ];
            })
            ->values();
    }

    /**
     * A name match outranks a mention in the text; matching both outranks
     * either. SQLite has no relevance figure, so a match counts as 1.
     */
    private function score(?DocumentText $text, bool $byName): float
    {
        return ($byName ? 10.0 : 0.0) + ($text ? (float) ($text->relevance ?? 1) : 0.0);
    }
}
