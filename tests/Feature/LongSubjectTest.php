<?php

use App\Livewire\CreateIncoming;
use App\Livewire\CreateOutgoing;
use App\Livewire\ManageRecord;
use App\Models\Office;
use App\Models\Record;
use App\Models\Status;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
});

/** A subject of a given length, in the shape real correspondence takes. */
function subjectOf(int $length): string
{
    $text = 'Request for the repair and recalibration of the runway edge lighting circuits at the domestic apron, ';

    return mb_substr(str_repeat($text, (int) ceil($length / mb_strlen($text)) + 1), 0, $length);
}

it('keeps a subject far longer than the old 255 character wall', function () {
    Office::firstOrCreate(['name' => 'ODG'], ['description' => 'Office of the Director General']);
    Status::firstOrCreate(['name' => 'For Action']);
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'name' => 'ITD Staff']));

    $subject = subjectOf(1200);

    Livewire::test(CreateOutgoing::class)
        ->set('office', 'ODG')
        ->set('subject', $subject)
        ->set('remarks', 'For action')
        ->set('status', 'For Action')
        ->call('createRecord')
        ->assertHasNoErrors('subject');

    expect(Record::latest('id')->value('subject'))->toBe($subject);
});

it('keeps a long subject on an incoming record too', function () {
    Office::firstOrCreate(['name' => 'ITD'], ['description' => 'Information Technology Division']);
    Status::firstOrCreate(['name' => 'For Action']);
    $this->actingAs(User::factory()->create(['office' => 'ODG', 'name' => 'ODG Records']));

    $subject = subjectOf(900);

    Livewire::test(CreateIncoming::class)
        ->set('office', 'ITD')
        ->set('originReference', 'ITD-2026-0055')
        ->set('subject', $subject)
        ->set('remarks', 'For action')
        ->set('status', 'For Action')
        ->call('createRecord')
        ->assertHasNoErrors();

    expect(Record::latest('id')->value('subject'))->toBe($subject);
});

it('lets a long subject be edited onto an existing record', function () {
    $user = User::factory()->create(['office' => 'ITD', 'name' => 'ITD Staff']);
    $this->actingAs($user);

    $record = Record::create([
        'reference' => 'ITD-2026-0800',
        'subject' => 'Short subject',
        'created_by' => 'ITD Staff',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);
    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'For Action',
        'destination' => 'ODG',
        'office' => 'ITD',
        'forwarded_by' => 'ITD Staff',
    ]);

    $subject = subjectOf(1500);

    // The routing is still editable while the movement is unreceived, so the
    // form asks for those fields too.
    Office::firstOrCreate(['name' => 'ODG'], ['description' => 'Office of the Director General']);

    Livewire::test(ManageRecord::class, ['recordId' => $record->id])
        ->set('subject', $subject)
        ->set('office', 'ODG')
        ->set('status', 'For Action')
        ->set('remarks', 'For action')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->subject)->toBe($subject);
});

it('says so in the form when a subject is past the limit, instead of failing in the database', function () {
    Office::firstOrCreate(['name' => 'ODG'], ['description' => 'Office of the Director General']);
    Status::firstOrCreate(['name' => 'For Action']);
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'name' => 'ITD Staff']));

    Livewire::test(CreateOutgoing::class)
        ->set('office', 'ODG')
        ->set('subject', subjectOf(Record::SUBJECT_MAX + 1))
        ->set('remarks', 'For action')
        ->set('status', 'For Action')
        ->call('createRecord')
        ->assertHasErrors(['subject' => 'max']);

    expect(Record::count())->toBe(0);
});

it('prints a long subject on the routing slip, sized to stay in its box', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'name' => 'ITD Staff']));

    $record = Record::create([
        'reference' => 'ITD-2026-0801',
        'subject' => subjectOf(700),
        'created_by' => 'ITD Staff',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);
    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'For Action',
        'destination' => 'ODG',
        'office' => 'ITD',
        'forwarded_by' => 'ITD Staff',
    ]);

    $html = view('pdfs.record', [
        'record' => $record->fresh(),
        'transactions' => $record->transactions()->orderBy('id')->get(),
    ])->render();

    expect($html)->toContain('subject-xs')
        ->and($html)->toContain(mb_substr($record->subject, 0, 60));

    // And it still renders as a PDF rather than throwing on the way out.
    expect(strlen(Pdf::loadHTML($html)->output()))->toBeGreaterThan(1000);
});

it('sizes a middling subject down one step, and leaves a short one alone', function () {
    $this->actingAs(User::factory()->create(['office' => 'ITD', 'name' => 'ITD Staff']));

    foreach ([[120, false, false], [400, true, false], [800, false, true]] as $index => [$length, $small, $tiny]) {
        $record = Record::create([
            'reference' => "ITD-2026-081{$index}",
            'subject' => subjectOf($length),
            'created_by' => 'ITD Staff',
            'origin' => 'ITD',
            'owner' => 'ITD',
        ]);
        Transaction::create([
            'record_id' => $record->id,
            'internal_reference' => $record->reference,
            'remarks' => 'For action',
            'status' => 'For Action',
            'destination' => 'ODG',
            'office' => 'ITD',
            'forwarded_by' => 'ITD Staff',
        ]);

        $html = view('pdfs.record', [
            'record' => $record,
            'transactions' => $record->transactions()->orderBy('id')->get(),
        ])->render();

        expect(str_contains($html, 'subject subject-sm'))->toBe($small)
            ->and(str_contains($html, 'subject subject-xs'))->toBe($tiny);
    }
});
