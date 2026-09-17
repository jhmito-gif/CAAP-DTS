<?php

use App\Livewire\OutgoingTransaction;
use App\Models\Attachment;
use App\Models\Record;
use App\Models\RecordTagging;
use App\Models\SignatureRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\SignatureRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * A confidential record owned by ITD, with one attached file whose name would
 * give the contents away.
 */
function confidentialRecordWithFile(bool $withToken = true): Attachment
{
    Storage::fake('local');

    $record = Record::create([
        'reference' => 'ITD-2026-0101',
        'subject' => 'Complaint against a ranking officer',
        'created_by' => 'ITD Staff',
        'origin' => 'ITD',
        'owner' => 'ITD',
        'is_confidential' => true,
    ]);

    if ($withToken) {
        $record->setConfidentialToken('open-sesame');
        $record->save();
    }

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'For Action',
        'destination' => 'ODG',
        'office' => 'ITD',
        'forwarded_by' => 'ITD Staff',
    ]);

    return Attachment::create([
        'record_id' => $record->id,
        'original_name' => 'Complaint vs Capt Dela Cruz.pdf',
        'path' => 'attachments/x.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 1024,
        'uploaded_by' => 'ITD Staff',
        'is_encrypted' => true,
    ]);
}

it('hides a confidential file name from an office that is not cleared', function () {
    $attachment = confidentialRecordWithFile();
    $outsider = User::factory()->create(['office' => 'ODG']);

    expect($attachment->nameIsHiddenFrom($outsider))->toBeTrue()
        ->and($attachment->displayNameFor($outsider))->toBe('Confidential file')
        ->and($attachment->displayNameFor($outsider))->not->toContain('Dela Cruz');
});

it('hides the name from a cleared viewer until the access token is entered', function () {
    $attachment = confidentialRecordWithFile();
    $owner = User::factory()->create(['office' => 'ITD']);

    expect($attachment->displayNameFor($owner, unlocked: false))->toBe('Confidential file')
        ->and($attachment->displayNameFor($owner, unlocked: true))->toBe('Complaint vs Capt Dela Cruz.pdf');
});

it('names an ordinary file to anyone who can reach the record', function () {
    Storage::fake('local');

    $record = Record::create([
        'reference' => 'ITD-2026-0102',
        'subject' => 'Network upgrade',
        'created_by' => 'ITD Staff',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    $attachment = Attachment::create([
        'record_id' => $record->id,
        'original_name' => 'memo.pdf',
        'path' => 'attachments/y.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 10,
        'uploaded_by' => 'ITD Staff',
        'is_encrypted' => true,
    ]);

    expect($attachment->displayNameFor(User::factory()->create(['office' => 'ODG'])))->toBe('memo.pdf');
});

it('keeps the name off the record page while the record is locked', function () {
    $attachment = confidentialRecordWithFile();

    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(OutgoingTransaction::class, ['recordId' => $attachment->record_id])
        ->assertDontSee('Complaint vs Capt Dela Cruz.pdf')
        ->assertSee('Confidential file')
        ->set('unlockToken', 'open-sesame')
        ->call('unlockConfidential')
        ->assertSee('Complaint vs Capt Dela Cruz.pdf');
});

it('does not name a confidential document in a notification to someone not cleared', function () {
    $attachment = confidentialRecordWithFile();
    $outsider = User::factory()->create(['office' => 'ODG', 'name' => 'Outside Person']);

    $request = SignatureRequest::create([
        'attachment_id' => $attachment->id,
        'record_id' => $attachment->record_id,
        'signer_id' => $outsider->id,
    ]);

    $payload = (new SignatureRequested($request, 'ITD Staff'))->toArray($outsider);

    expect($payload['message'])->toContain('a confidential document')
        ->and($payload['message'])->not->toContain('Dela Cruz')
        ->and($payload['subject'])->toBe('Confidential — hidden from you');

    // Once tagged as a cleared viewer -- as requesting a signature does -- the
    // signatory sees what they are being asked to sign.
    RecordTagging::create([
        'record_id' => $attachment->record_id,
        'user_id' => $outsider->id,
        'office' => $outsider->office,
        'tagged_by' => 'ITD Staff',
    ]);

    $payload = (new SignatureRequested($request->fresh(), 'ITD Staff'))->toArray($outsider->fresh());

    expect($payload['message'])->toContain('Complaint vs Capt Dela Cruz.pdf')
        ->and($payload['subject'])->toBe('Complaint against a ranking officer');
});
