<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Record;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes the audit trail: who did what, to what, and when.
 *
 * Model changes arrive here on their own through App\Models\Concerns\Audited;
 * call log() directly for what is not a model change -- signing in, opening
 * or downloading a file.
 *
 * Never pass secrets (passwords, codes, tokens). Logging must not break the
 * action being logged, so a failure to write is reported and swallowed.
 */
class Audit
{
    private static int $paused = 0;

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes  field => [before, after]
     * @param  array<string, mixed>  $context
     */
    public static function log(string $action, ?Model $subject = null, array $changes = [], array $context = [], ?User $user = null): ?AuditLog
    {
        if (self::$paused > 0) {
            return null;
        }

        try {
            $user ??= Auth::user();
            [$recordId, $recordReference] = self::recordOf($subject);

            return AuditLog::create([
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject ? Str::limit(self::labelOf($subject), 250, '…') : null,
                'record_id' => $recordId,
                'record_reference' => $recordReference,
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_office' => $user?->office,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 500, '') ?: null,
                'changes' => $changes ?: null,
                'context' => array_filter($context, fn ($value) => $value !== null) ?: null,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Do something without writing it to the audit trail -- for automatic
     * bookkeeping nobody acted on (moving a reference counter along).
     */
    public static function quietly(callable $callback): mixed
    {
        self::$paused++;

        try {
            return $callback();
        } finally {
            self::$paused--;
        }
    }

    /** A readable name for what was acted on. */
    private static function labelOf(Model $subject): string
    {
        if (method_exists($subject, 'auditLabel')) {
            return (string) $subject->auditLabel();
        }

        foreach (['reference', 'title', 'name', 'original_name', 'module', 'text', 'office'] as $field) {
            if (filled($subject->getAttribute($field))) {
                return (string) $subject->getAttribute($field);
            }
        }

        return '#' . $subject->getKey();
    }

    /**
     * The record this concerns, so its whole history can be read in one go.
     *
     * @return array{0: ?int, 1: ?string}
     */
    private static function recordOf(?Model $subject): array
    {
        if ($subject instanceof Record) {
            return [$subject->getKey(), $subject->reference];
        }

        $recordId = $subject?->getAttribute('record_id');

        if (! $recordId) {
            return [null, null];
        }

        $reference = $subject->relationLoaded('record')
            ? $subject->getRelation('record')?->reference
            : ($subject->getAttribute('internal_reference') ?: Record::whereKey($recordId)->value('reference'));

        return [(int) $recordId, $reference];
    }
}
