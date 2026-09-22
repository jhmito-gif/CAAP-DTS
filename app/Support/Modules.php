<?php

namespace App\Support;

use App\Models\ModuleSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * The optional parts of the system, and whether each is switched on.
 *
 * Turning a module off hides it: its pages, its menu entries and its buttons
 * go, and its routes answer "turned off". It never deletes anything, and it
 * never stops another module working. That second promise is the hard part,
 * because some modules lean on each other:
 *
 *   - E-signatures: a pending signature normally stops a document being passed
 *     on or sent out. With signing off nobody could sign, so that hold is
 *     lifted rather than left to freeze the routing.
 *   - Internal routing: the colleague holding a document is normally the only
 *     one who may send it out. With the panel gone nobody could hand it back,
 *     so that hold is lifted too.
 *   - Document library: record attachments are not library files. They keep
 *     opening, downloading and being read for search from their record.
 *   - Reading documents: with it off, search falls back to names, references
 *     and subjects. Files are still queued as they arrive, so turning it back
 *     on reads what came in meanwhile.
 *
 * Dashboard, Incoming, Outgoing, notifications and the admin panel are the
 * core of the system and cannot be switched off.
 */
class Modules
{
    public const DOCUMENTS = 'documents';

    public const ESIGN = 'esign';

    public const INTERNAL_ROUTING = 'internal_routing';

    public const CHAT = 'chat';

    public const DOCUMENT_READING = 'document_reading';

    private const CACHE_KEY = 'modules.enabled';

    /**
     * What each module is, and what happens elsewhere when it is off -- shown
     * to the admin beside the switch, so nobody turns one off blind.
     *
     * @var array<string, array{label: string, description: string, when_off: string}>
     */
    public const CATALOGUE = [
        self::DOCUMENTS => [
            'label' => 'Document library',
            'description' => 'The Documents explorer: folders, library uploads, sharing, and each file\'s own page.',
            'when_off' => 'Record attachments are unaffected: they still open, download and are searched from their record.',
        ],
        self::ESIGN => [
            'label' => 'E-signatures',
            'description' => 'Signing documents, the signing queue, signature requests when sending, and signature verification.',
            'when_off' => 'A document waiting for a signature can still be passed on and sent out. Existing requests are kept for when it is turned back on.',
        ],
        self::INTERNAL_ROUTING => [
            'label' => 'Internal routing',
            'description' => 'Handing a document to a colleague inside the office, and the trail of who has had it.',
            'when_off' => 'Anyone in the office can send a document out again, whoever last held it. The trail is kept.',
        ],
        self::CHAT => [
            'label' => 'Chat',
            'description' => 'Messages between users, and the files sent in them.',
            'when_off' => 'Nothing else depends on chat. Messages are kept.',
        ],
        self::DOCUMENT_READING => [
            'label' => 'Reading documents for search',
            'description' => 'Reading the text out of uploaded files (OCR for scans) so they can be found by what they say.',
            'when_off' => 'Search still finds files by name, and records by reference and subject. New files are queued, and read once this is back on.',
        ],
    ];

    /** The parts that cannot be switched off. */
    public const CORE = ['Dashboard', 'Incoming', 'Outgoing', 'Notifications', 'Admin panel'];

    /**
     * Held in the container for the rest of the request, so a page asking
     * twenty times reads the cache once. Not a static: that would outlive the
     * request and carry one test's switches into the next.
     */
    private const MEMO = 'modules.states';

    public static function enabled(string $module): bool
    {
        // Anything not in the catalogue is core, and core is always on.
        if (! array_key_exists($module, self::CATALOGUE)) {
            return true;
        }

        return self::states()[$module] ?? true;
    }

    public static function disabled(string $module): bool
    {
        return ! self::enabled($module);
    }

    public static function set(string $module, bool $enabled, ?string $by = null): void
    {
        abort_unless(array_key_exists($module, self::CATALOGUE), 422, 'That is not a module that can be switched off.');

        ModuleSetting::updateOrCreate(['module' => $module], ['enabled' => $enabled, 'updated_by' => $by]);
    }

    public static function forget(): void
    {
        app()->forgetInstance(self::MEMO);
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, bool>
     */
    private static function states(): array
    {
        if (app()->bound(self::MEMO)) {
            return app(self::MEMO);
        }

        $states = Cache::rememberForever(self::CACHE_KEY, function () {
            // Before the migration has run, everything is on.
            if (! Schema::hasTable('module_settings')) {
                return [];
            }

            return ModuleSetting::query()->pluck('enabled', 'module')->map(fn ($on) => (bool) $on)->all();
        });

        app()->instance(self::MEMO, $states);

        return $states;
    }
}
