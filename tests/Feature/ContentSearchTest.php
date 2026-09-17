<?php

use App\Livewire\DocumentExplorer;
use App\Models\Attachment;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DocumentReader;
use App\Support\DocumentSearch;
use App\Models\DocumentText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** A record with one attachment, read so its contents can be searched. */
function recordSaying(string $body, bool $confidential = false, string $reference = 'ITD-2026-0500'): Record
{
    $record = Record::create([
        'reference' => $reference,
        'subject' => 'Routine matter',
        'created_by' => 'ITD Staff',
        'origin' => 'ITD',
        'owner' => 'ITD',
        'is_confidential' => $confidential,
    ]);

    $transaction = Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'For Action',
        'destination' => 'ODG',
        'office' => 'ITD',
        'forwarded_by' => 'ITD Staff',
    ]);

    $path = sys_get_temp_dir() . '/' . uniqid('rec-', true) . '.pdf';
    file_put_contents($path, Pdf::loadHTML("<p>{$body}</p>")->setPaper('a4')->output());

    $attachment = Attachment::storeEncrypted(
        $record,
        $transaction,
        new UploadedFile($path, 'attached.pdf', 'application/pdf', null, true),
        'ITD Staff',
    );

    @unlink($path);

    app(DocumentReader::class)->read(
        DocumentText::where('source_type', 'attachment')->where('source_id', $attachment->id)->firstOrFail()
    );

    return $record;
}

it('finds a record by words inside its attachment', function () {
    Storage::fake('local');

    $wanted = recordSaying('Replacement of the airfield lighting at Iloilo Airport.', false, 'ITD-2026-0501');
    recordSaying('Procurement of office chairs and filing cabinets.', false, 'ITD-2026-0502');

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    $found = Record::search('airfield lighting')->pluck('reference');

    expect($found)->toContain('ITD-2026-0501')
        ->and($found)->not->toContain('ITD-2026-0502');
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('still finds records the old way, by reference and subject', function () {
    Storage::fake('local');

    Record::create([
        'reference' => 'ITD-2026-0600', 'subject' => 'Network upgrade', 'created_by' => 'x',
        'origin' => 'ITD', 'owner' => 'ITD',
    ]);

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    expect(Record::search('ITD-2026-0600')->count())->toBe(1)
        ->and(Record::search('Network upgrade')->count())->toBe(1);
});

it('does not let the contents of a confidential record be searched by someone not cleared', function () {
    Storage::fake('local');

    recordSaying('The complaint concerns unauthorised overtime claims.', true, 'ITD-2026-0700');

    // ODG is in the routing, so they can see the record exists -- but nothing in it.
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    expect(Record::search('overtime')->count())->toBe(0);

    // The owning office, and anyone tagged, can.
    $this->actingAs(User::factory()->create(['office' => 'ITD']));
    expect(Record::search('overtime')->count())->toBe(1);
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('lets a tagged viewer search a confidential record\'s contents', function () {
    Storage::fake('local');

    $record = recordSaying('The complaint concerns unauthorised overtime claims.', true, 'ITD-2026-0800');
    $viewer = User::factory()->create(['office' => 'ODG']);

    RecordTagging::create([
        'record_id' => $record->id,
        'user_id' => $viewer->id,
        'office' => $viewer->office,
        'tagged_by' => 'ITD Staff',
    ]);

    $this->actingAs($viewer);

    expect(Record::search('overtime')->count())->toBe(1);
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('searches names and contents together, ranking a name match first', function () {
    Storage::fake('local');

    $user = User::factory()->create(['office' => 'ITD']);

    // One file is named for the words; another merely mentions them.
    $named = \App\Models\Document::storeEncrypted(
        (function () {
            $path = sys_get_temp_dir() . '/' . uniqid('named-', true) . '.pdf';
            file_put_contents($path, Pdf::loadHTML('<p>Nothing of note.</p>')->setPaper('a4')->output());

            return new UploadedFile($path, 'Airfield lighting plan.pdf', 'application/pdf', null, true);
        })(),
        $user,
        ['title' => 'Airfield lighting plan.pdf'],
    );

    $mentions = \App\Models\Document::storeEncrypted(
        (function () {
            $path = sys_get_temp_dir() . '/' . uniqid('mentions-', true) . '.pdf';
            file_put_contents($path, Pdf::loadHTML('<p>The airfield lighting was discussed at length.</p>')->setPaper('a4')->output());

            return new UploadedFile($path, 'minutes.pdf', 'application/pdf', null, true);
        })(),
        $user,
        ['title' => 'minutes.pdf'],
    );

    foreach ([$named, $mentions] as $document) {
        app(DocumentReader::class)->read(DocumentText::where('source_type', 'document')->where('source_id', $document->id)->firstOrFail());
    }

    $hits = app(DocumentSearch::class)->find($user, 'airfield lighting');

    expect($hits)->toHaveCount(2)
        ->and($hits->first()->name)->toBe('Airfield lighting plan.pdf')
        ->and($hits->first()->matched)->toBe('name')
        ->and($hits->last()->matched)->toBe('contents');
})->skip(fn () => ! trim(shell_exec('node -v 2>&1') ?? ''), 'Node is not available');

it('searches everywhere by default in the explorer', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(DocumentExplorer::class)
        ->assertSet('scope', 'everywhere')
        ->assertSee('Search names and contents');
});
