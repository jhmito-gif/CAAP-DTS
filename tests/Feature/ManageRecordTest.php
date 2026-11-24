<?php

use App\Livewire\ManageRecord;
use App\Models\Attachment;
use App\Models\Office;
use App\Models\Record;
use App\Models\Status;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['ITD', 'HR', 'Finance'] as $name) {
        Office::create(['name' => $name, 'description' => "{$name} office"]);
    }

    Status::create(['name' => 'Pending']);
    Status::create(['name' => 'For Action']);
});

function outgoingRecordCreatedBy(User $creator, array $firstTransaction = []): Record
{
    $record = Record::create([
        'reference' => 'ITD-2026-0001',
        'subject' => 'Original subject',
        'created_by' => $creator->name,
        'origin' => $creator->office,
        'owner' => $creator->office,
    ]);

    Transaction::create($firstTransaction + [
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'Original remarks',
        'status' => 'Pending',
        'destination' => 'HR',
        'office' => $creator->office,
        'forwarded_by' => $creator->name,
    ]);

    return $record;
}

it('lets the creator edit the subject and first routing entry, and add files, before it is received', function () {
    Storage::fake('local');

    $creator = User::factory()->create(['name' => 'Creator Person', 'office' => 'ITD']);
    $record = outgoingRecordCreatedBy($creator);
    $oldFile = Attachment::storeEncrypted($record, $record->firstTransaction(), UploadedFile::fake()->image('old.png'), $creator->name);

    $this->actingAs($creator);

    Livewire::test(ManageRecord::class)
        ->call('edit', $record->id)
        ->assertDispatched('open-edit-record-modal')
        ->assertSet('subject', 'Original subject')
        ->assertSet('office', 'HR')
        ->set('subject', 'Corrected subject')
        ->set('office', 'Finance')
        ->set('status', 'For Action')
        ->set('remarks', 'Corrected remarks')
        ->set('newAttachments', [UploadedFile::fake()->image('new.png')])
        ->set('uploadTypes', [\App\Models\DocumentCategory::value('id')])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('record-updated');

    $record->refresh();
    $first = $record->firstTransaction();

    expect($record->subject)->toBe('Corrected subject')
        ->and($record->reference)->toBe('ITD-2026-0001')
        ->and($first->destination)->toBe('Finance')
        ->and($first->status)->toBe('For Action')
        ->and($first->remarks)->toBe('Corrected remarks')
        // The file already there stays; the new one joins it.
        ->and(Attachment::find($oldFile->id))->not->toBeNull()
        ->and($record->attachments()->pluck('original_name')->sort()->values()->all())->toBe(['new.png', 'old.png']);

    Storage::disk('local')->assertExists($oldFile->path);
});

it('locks the record for its creator once another office has received it', function () {
    $creator = User::factory()->create(['name' => 'Creator Person', 'office' => 'ITD']);
    $record = outgoingRecordCreatedBy($creator, ['recieved_by' => 'HR Person', 'date_recieved' => now()]);

    expect($creator->can('update', $record))->toBeFalse()
        ->and($creator->can('delete', $record))->toBeFalse();

    $this->actingAs($creator);

    Livewire::test(ManageRecord::class)
        ->call('edit', $record->id)
        ->assertNotDispatched('open-edit-record-modal')
        ->assertDispatched('banner-message')
        // Even a tampered request cannot save.
        ->set('recordId', $record->id)
        ->set('subject', 'Sneaky edit')
        ->call('save');

    expect($record->fresh())->not->toBeNull()
        ->and($record->fresh()->subject)->toBe('Original subject');
});

it('does not let anyone other than the creator edit a record', function () {
    $creator = User::factory()->create(['name' => 'Creator Person', 'office' => 'ITD']);
    $colleague = User::factory()->create(['name' => 'Colleague Person', 'office' => 'ITD']);
    $record = outgoingRecordCreatedBy($creator);

    expect($colleague->can('update', $record))->toBeFalse()
        ->and($colleague->can('delete', $record))->toBeFalse();

    $this->actingAs($colleague);

    Livewire::test(ManageRecord::class)
        ->call('edit', $record->id)
        ->assertNotDispatched('open-edit-record-modal');

    expect(Record::find($record->id))->not->toBeNull();
});

it('lets nobody delete a record, admins included', function () {
    Storage::fake('local');

    $creator = User::factory()->create(['name' => 'Creator Person', 'office' => 'ITD']);
    $admin = User::factory()->create(['name' => 'Admin Person', 'office' => 'ITD', 'role' => User::ROLE_ADMIN]);
    $record = outgoingRecordCreatedBy($creator);
    $file = Attachment::storeEncrypted($record, $record->firstTransaction(), UploadedFile::fake()->image('slip.png'), $creator->name);

    expect($creator->can('delete', $record))->toBeFalse()
        ->and($admin->can('delete', $record))->toBeFalse()
        ->and(method_exists(ManageRecord::class, 'delete'))->toBeFalse()
        // Not even from code.
        ->and(fn () => $record->delete())->toThrow(LogicException::class)
        ->and(fn () => $file->delete())->toThrow(LogicException::class);

    expect(Record::find($record->id))->not->toBeNull()
        ->and(Transaction::where('record_id', $record->id)->exists())->toBeTrue()
        ->and(Attachment::find($file->id))->not->toBeNull();

    Storage::disk('local')->assertExists($file->path);
});

it('corrects the origin reference of an incoming record and its routing snapshots', function () {
    $creator = User::factory()->create(['name' => 'Creator Person', 'office' => 'ITD']);

    $record = Record::create([
        'reference' => 'ITD-2026-0002',
        'origin_reference' => 'FIN-TYPO',
        'subject' => 'Incoming document',
        'created_by' => $creator->name,
        'origin' => 'Finance',
        'owner' => 'ITD',
    ]);

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'origin_reference' => 'FIN-TYPO',
        'remarks' => 'Received',
        'status' => 'Pending',
        'destination' => 'ITD',
        'office' => 'Finance',
        'forwarded_by' => $creator->name,
    ]);

    $this->actingAs($creator);

    Livewire::test(ManageRecord::class)
        ->call('edit', $record->id)
        ->set('originReference', 'FIN-2026-0042')
        ->call('save')
        ->assertHasNoErrors();

    $transaction = Transaction::where('record_id', $record->id)->first();

    expect($record->fresh()->origin_reference)->toBe('FIN-2026-0042')
        ->and($transaction->origin_reference)->toBe('FIN-2026-0042')
        ->and($transaction->destination)->toBe('ITD');
});
