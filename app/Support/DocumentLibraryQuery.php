<?php

namespace App\Support;

use App\Models\Access;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * The document library as one list: files attached to routed records plus
 * files uploaded straight into an office's library.
 *
 * Routed files follow the record access rules (own office, routing chain,
 * tagged, granted; admins see everything) and confidential ones only reach
 * cleared viewers, never the routing chain. Library uploads are visible to
 * their office and admins.
 */
class DocumentLibraryQuery
{
    public const TYPES = [
        'pdf' => 'PDF',
        'image' => 'Images',
        'word' => 'Word documents',
        'spreadsheet' => 'Spreadsheets',
        'other' => 'Other files',
    ];

    public const SOURCES = [
        'routed' => 'Routed records',
        'library' => 'Library uploads',
    ];

    private const WORD_MIMES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const SPREADSHEET_MIMES = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * @param  array{search?: string, office?: string, category?: string, type?: string, source?: string, signed?: string, from?: string, to?: string}  $filters
     */
    public function paginate(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $parts = [];

        if (($filters['source'] ?? '') !== 'library') {
            $parts[] = $this->routedFiles($user, $filters);
        }

        // Library uploads are never e-signed, so "signed" leaves only routed files.
        if (($filters['source'] ?? '') !== 'routed' && ($filters['signed'] ?? '') !== 'signed') {
            $parts[] = $this->libraryFiles($user, $filters);
        }

        if ($parts === []) {
            $parts[] = $this->routedFiles($user, $filters)->whereRaw('1 = 0');
        }

        $union = array_shift($parts);

        foreach ($parts as $part) {
            $union->unionAll($part);
        }

        return DB::query()
            ->fromSub($union, 'library')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function routedFiles(User $user, array $filters): QueryBuilder
    {
        $query = Attachment::query()
            ->join('records', 'records.id', '=', 'attachments.record_id')
            ->leftJoin('transactions as uploaded_in', 'uploaded_in.id', '=', 'attachments.transaction_id')
            ->select([
                DB::raw("'attachment' as source"),
                'attachments.id',
                'attachments.original_name as title',
                'attachments.original_name',
                'attachments.mime_type',
                'attachments.size',
                DB::raw('COALESCE(uploaded_in.office, records.owner) as office'),
                'attachments.document_category_id',
                'records.id as record_id',
                'records.reference',
                'records.subject',
                'records.owner as record_owner',
                'records.origin as record_origin',
                DB::raw('NULL as description'),
                DB::raw('CASE WHEN attachments.is_confidential = 1 OR records.is_confidential = 1 THEN 1 ELSE 0 END as is_confidential'),
                DB::raw('CASE WHEN records.is_confidential = 1 AND records.confidential_token IS NOT NULL THEN 1 ELSE 0 END as requires_token'),
                'attachments.signing_completed_at as signed_at',
                'attachments.uploaded_by',
                'attachments.created_at',
            ]);

        if (in_array(SoftDeletes::class, class_uses_recursive(Record::class), true)) {
            $query->whereNull('records.deleted_at');
        }

        if (! $user->isAdmin()) {
            $this->restrictToAccessibleRecords($query, $user);
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(fn (Builder $q) => $q
                ->where('attachments.original_name', 'like', "%{$search}%")
                ->orWhere('records.reference', 'like', "%{$search}%")
                ->orWhere('records.subject', 'like', "%{$search}%"));
        }

        if (filled($filters['office'] ?? null)) {
            $query->whereRaw('COALESCE(uploaded_in.office, records.owner) = ?', [$filters['office']]);
        }

        match ($filters['signed'] ?? '') {
            'signed' => $query->whereNotNull('attachments.signing_completed_at'),
            'unsigned' => $query->whereNull('attachments.signing_completed_at'),
            default => null,
        };

        $this->applySharedFilters($query, 'attachments', $filters);

        return $query->toBase();
    }

    private function libraryFiles(User $user, array $filters): QueryBuilder
    {
        $query = Document::query()->select([
            DB::raw("'document' as source"),
            'documents.id',
            'documents.title',
            'documents.original_name',
            'documents.mime_type',
            'documents.size',
            'documents.office',
            'documents.document_category_id',
            DB::raw('NULL as record_id'),
            DB::raw('NULL as reference'),
            DB::raw('NULL as subject'),
            DB::raw('NULL as record_owner'),
            DB::raw('NULL as record_origin'),
            'documents.description',
            DB::raw('0 as is_confidential'),
            DB::raw('0 as requires_token'),
            DB::raw('NULL as signed_at'),
            'documents.uploaded_by',
            'documents.created_at',
        ]);

        if (! $user->isAdmin()) {
            $query->where('documents.office', (string) $user->office);

            // Folders the explorer would not show them are not listed here
            // either -- a closed folder is closed wherever it is looked at.
            $hidden = app(FolderAccess::class)->hiddenFolderIds($user, (string) $user->office);

            if ($hidden) {
                $query->where(fn (Builder $q) => $q
                    ->whereNull('documents.folder_id')
                    ->orWhereNotIn('documents.folder_id', $hidden));
            }
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(fn (Builder $q) => $q
                ->where('documents.title', 'like', "%{$search}%")
                ->orWhere('documents.original_name', 'like', "%{$search}%")
                ->orWhere('documents.description', 'like', "%{$search}%"));
        }

        if (filled($filters['office'] ?? null)) {
            $query->where('documents.office', $filters['office']);
        }

        $this->applySharedFilters($query, 'documents', $filters);

        return $query->toBase();
    }

    /**
     * Same rules as Record::isAccessibleBy() and canViewConfidentialDetails(),
     * as subqueries so the whole list stays one query.
     */
    private function restrictToAccessibleRecords(Builder $query, User $user): void
    {
        $office = (string) $user->office;

        $tagged = fn () => RecordTagging::query()->select('record_id')->where('user_id', $user->id);
        $granted = fn () => Access::query()->select('record_id')->whereNotNull('record_id')->where('office', $office);
        $routed = fn () => Transaction::query()
            ->select('record_id')
            ->whereNotNull('record_id')
            ->where(fn ($q) => $q->where('destination', $office)->orWhere('office', $office));

        $query
            ->where(fn (Builder $q) => $q
                ->where('records.owner', $office)
                ->orWhere('records.origin', $office)
                ->orWhereIn('records.id', $routed())
                ->orWhereIn('records.id', $tagged())
                ->orWhereIn('records.id', $granted()))
            // Confidential files: the owning office, tagged personnel and granted offices only.
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $q) => $q->where('attachments.is_confidential', false)->where('records.is_confidential', false))
                ->orWhere('records.owner', $office)
                ->orWhere('records.origin', $office)
                ->orWhereIn('records.id', $tagged())
                ->orWhereIn('records.id', $granted()));
    }

    private function applySharedFilters(Builder $query, string $table, array $filters): void
    {
        $category = (string) ($filters['category'] ?? '');

        if ($category === 'none') {
            $query->whereNull("{$table}.document_category_id");
        } elseif ($category !== '') {
            $query->where("{$table}.document_category_id", (int) $category);
        }

        $mime = "{$table}.mime_type";

        match ($filters['type'] ?? '') {
            'pdf' => $query->where($mime, 'application/pdf'),
            'image' => $query->where($mime, 'like', 'image/%'),
            'word' => $query->whereIn($mime, self::WORD_MIMES),
            'spreadsheet' => $query->whereIn($mime, self::SPREADSHEET_MIMES),
            'other' => $query->where(fn (Builder $q) => $q
                ->whereNull($mime)
                ->orWhere(fn (Builder $q) => $q
                    ->where($mime, '!=', 'application/pdf')
                    ->where($mime, 'not like', 'image/%')
                    ->whereNotIn($mime, [...self::WORD_MIMES, ...self::SPREADSHEET_MIMES]))),
            default => null,
        };

        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            $date = (string) ($filters[$key] ?? '');

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $query->whereDate("{$table}.created_at", $operator, $date);
            }
        }
    }
}
