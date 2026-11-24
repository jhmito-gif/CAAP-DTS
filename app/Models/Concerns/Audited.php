<?php

namespace App\Models\Concerns;

use App\Support\Audit;
use Illuminate\Support\Str;

/**
 * Writes every create, change and delete of the model to the audit trail,
 * with what each field was before and after. Because it hooks the model, it
 * catches every way in: the screens, the admin panel and background jobs.
 * (Bulk query-builder updates skip model events and are not caught.)
 *
 * A model can rename its actions (auditAction), leave out bookkeeping fields
 * (auditExcept) and name itself (auditLabel).
 */
trait Audited
{
    protected static function bootAudited(): void
    {
        static::created(fn ($model) => $model->writeAudit('created'));
        static::updated(fn ($model) => $model->writeAudit('updated'));
        static::deleted(fn ($model) => $model->writeAudit('deleted'));
    }

    public function writeAudit(string $event): void
    {
        $skip = array_merge(['created_at', 'updated_at', 'remember_token'], $this->auditExcept());

        $fields = match ($event) {
            'updated' => array_keys($this->getChanges()),
            default => array_keys($this->getAttributes()),
        };

        $changes = [];

        foreach (array_diff($fields, $skip) as $field) {
            $before = $event === 'created' ? null : $this->getOriginal($field);
            $after = $event === 'deleted' ? null : $this->getAttribute($field);

            if ($event === 'created' && ($after === null || $after === '')) {
                continue;
            }

            $changes[$field] = [$this->auditValue($field, $before), $this->auditValue($field, $after)];
        }

        // Only bookkeeping moved: nothing anyone would want to read about.
        if ($event === 'updated' && $changes === []) {
            return;
        }

        Audit::log($this->auditAction($event), $this, $changes);
    }

    /** "record.created", "document_folder.updated", ... */
    protected function auditAction(string $event): string
    {
        return Str::snake(class_basename($this)) . '.' . $event;
    }

    /** Fields not worth recording at all, on top of the timestamps. */
    protected function auditExcept(): array
    {
        return [];
    }

    /** Secrets are recorded as having changed, never by value. */
    private function auditValue(string $field, mixed $value): mixed
    {
        $secret = in_array($field, ['password', 'two_factor_secret', 'two_factor_recovery_codes', 'confidential_token'], true);

        if ($secret) {
            return $value === null ? null : '(set)';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return is_scalar($value) || $value === null ? $value : json_decode(json_encode($value), true);
    }
}
