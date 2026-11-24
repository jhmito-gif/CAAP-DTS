<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One entry in the append-only audit trail. Written by App\Support\Audit;
 * the application never updates or deletes rows.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /** Named actions; anything else reads as "<thing> created/updated/deleted". */
    public const ACTIONS = [
        'auth.login' => 'Signed in',
        'auth.logout' => 'Signed out',
        'auth.failed' => 'Failed sign-in',

        'record.created' => 'Record created',
        'record.updated' => 'Record edited',
        'record.slip_opened' => 'Routing slip opened',
        'record.token_reset' => 'Access token reset',

        'transaction.sent' => 'Document sent',
        'transaction.received' => 'Document received',
        'transaction.reference_assigned' => 'Reference ID assigned',
        'transaction.updated' => 'Routing entry edited',

        'attachment.created' => 'File attached to record',
        'attachment.updated' => 'Record file changed',
        'attachment.viewed' => 'Record file opened',
        'attachment.downloaded' => 'Record file downloaded',

        'document.created' => 'File uploaded to library',
        'document.updated' => 'Library file changed',
        'document.viewed' => 'Library file opened',
        'document.downloaded' => 'Library file downloaded',

        'document_folder.created' => 'Folder created',
        'document_folder.updated' => 'Folder renamed or moved',
        'folder_policy.created' => 'Folder shared',
        'folder_policy.updated' => 'Folder sharing changed',
        'folder_policy.deleted' => 'Folder sharing removed',
        'folder_shortcut.created' => 'Record file filed in folder',
        'folder_shortcut.updated' => 'Filed record file moved',

        'access.created' => 'Record access granted',
        'access.updated' => 'Record access changed',
        'access.deleted' => 'Record access removed',
        'record_tagging.created' => 'Person tagged on record',
        'record_tagging.deleted' => 'Person untagged from record',
        'internal_routing.created' => 'Handed to a colleague',
        'internal_routing.updated' => 'Handoff accepted or changed',

        'user.created' => 'User created',
        'user.updated' => 'User changed',
        'office.created' => 'Office created',
        'office.updated' => 'Office changed',
        'status.created' => 'Status created',
        'status.updated' => 'Status changed',
        'document_category.created' => 'Document type created',
        'document_category.updated' => 'Document type changed',
        'remark_template.created' => 'Remark template created',
        'remark_template.updated' => 'Remark template changed',
        'remark_template.deleted' => 'Remark template deleted',
        'reference_sequence.created' => 'Reference sequence created',
        'reference_sequence.updated' => 'Reference sequence changed',
        'module_setting.created' => 'Module switched',
        'module_setting.updated' => 'Module switched',
    ];

    protected $fillable = [
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'record_id',
        'record_reference',
        'user_id',
        'user_name',
        'user_office',
        'ip_address',
        'user_agent',
        'changes',
        'context',
    ];

    protected $casts = [
        'changes' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function label(): string
    {
        if (isset(self::ACTIONS[$this->action])) {
            return self::ACTIONS[$this->action];
        }

        [$thing, $verb] = array_pad(explode('.', $this->action, 2), 2, '');

        return str($thing)->replace('_', ' ')->ucfirst() . ' ' . $verb;
    }

    /** "subject: Old → New" lines, for the admin viewer. */
    public function changeLines(): array
    {
        return collect($this->changes ?? [])
            ->map(function ($pair, string $field) {
                [$before, $after] = array_pad((array) $pair, 2, null);

                return str($field)->replace('_', ' ') . ': ' . self::show($before) . ' → ' . self::show($after);
            })
            ->values()
            ->all();
    }

    private static function show(mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '—',
            is_bool($value) => $value ? 'yes' : 'no',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit logs are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit logs are append-only.'));
    }
}
