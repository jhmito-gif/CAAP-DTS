<?php

use App\Filament\Resources\AuditLogResource;
use App\Livewire\CreateOutgoing;
use App\Livewire\ManageRecord;
use App\Livewire\TransactionTable;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\DocumentCategory;
use App\Models\Office;
use App\Models\Record;
use App\Models\ReferenceSequence;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

function auditEntry(string $action): ?AuditLog
{
    return AuditLog::where('action', $action)->latest('id')->first();
}

/*
|--------------------------------------------------------------------------
| What gets recorded
|--------------------------------------------------------------------------
*/

it('records a new record, the send, and its files, against the person and office', function () {
    $sender = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $this->actingAs($sender);

    Livewire::test(CreateOutgoing::class)
        ->set('office', 'HR')
        ->set('status', 'Pending')
        ->set('subject', 'Network upgrade')
        ->set('remarks', 'For approval')
        ->set('attachments', [UploadedFile::fake()->image('plan.png')])
        ->set('uploadTypes', [DocumentCategory::value('id')])
        ->call('createRecord')
        ->assertHasNoErrors();

    $record = Record::sole();

    foreach (['record.created', 'transaction.sent', 'attachment.created'] as $action) {
        expect(auditEntry($action))
            ->not->toBeNull()
            ->user_name->toBe('ITD Clerk')
            ->user_office->toBe('ITD')
            ->record_id->toBe($record->id)
            ->record_reference->toBe($record->reference);
    }

    expect(auditEntry('record.created')->changes['subject'])->toBe([null, 'Network upgrade'])
        ->and(auditEntry('transaction.sent')->subject_label)->toBe('ITD → HR')
        ->and(auditEntry('attachment.created')->subject_label)->toBe('plan.png')
        // Storage details are not part of the story.
        ->and(auditEntry('attachment.created')->changes)->not->toHaveKey('path');
});

it('records an edit with what it said before and after', function () {
    Office::create(['name' => 'HR', 'description' => 'Human resources']);
    $creator = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $record = Record::create([
        'reference' => 'ITD-2026-0001', 'subject' => 'Orginal subject',
        'created_by' => 'ITD Clerk', 'origin' => 'ITD', 'owner' => 'ITD',
    ]);
    Transaction::create([
        'record_id' => $record->id, 'internal_reference' => $record->reference,
        'remarks' => 'For action', 'status' => 'Pending',
        'destination' => 'HR', 'office' => 'ITD', 'forwarded_by' => 'ITD Clerk',
    ]);

    $this->actingAs($creator);

    Livewire::test(ManageRecord::class)
        ->call('edit', $record->id)
        ->set('subject', 'Original subject')
        ->call('save')
        ->assertHasNoErrors();

    expect(auditEntry('record.updated'))
        ->changes->toBe(['subject' => ['Orginal subject', 'Original subject']])
        ->user_name->toBe('ITD Clerk');
});

it('records the receiving office receiving it, and its reference ID', function () {
    $record = Record::create([
        'reference' => 'CPO-2026-0001', 'subject' => 'Request',
        'created_by' => 'CPO Clerk', 'origin' => 'CPO', 'owner' => 'CPO',
    ]);
    $movement = Transaction::create([
        'record_id' => $record->id, 'internal_reference' => $record->reference,
        'remarks' => 'For approval', 'status' => 'Pending',
        'destination' => 'ODG', 'office' => 'CPO', 'forwarded_by' => 'CPO Clerk',
    ]);

    $this->actingAs(User::factory()->create(['name' => 'ODG Clerk', 'office' => 'ODG']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id);

    $movement->fresh()->update(['received_reference' => 'ODG-2026-0079']);

    expect(auditEntry('transaction.received'))
        ->user_name->toBe('ODG Clerk')
        ->record_reference->toBe('CPO-2026-0001')
        ->and(auditEntry('transaction.received')->changes)->toHaveKey('recieved_by')
        ->and(auditEntry('transaction.reference_assigned')->changes)->toBe(['received_reference' => [null, 'ODG-2026-0079']]);
});

it('leaves the reference counter moving along out, but records an admin changing it', function () {
    ReferenceSequence::advance('ITD', 'ITD-' . now()->year . '-0005');

    expect(AuditLog::where('subject_type', 'ReferenceSequence')->exists())->toBeFalse();

    $this->actingAs(User::factory()->create(['office' => 'ITD', 'role' => User::ROLE_ADMIN]));
    ReferenceSequence::sole()->update(['next_number' => 500]);

    expect(auditEntry('reference_sequence.updated')->changes)->toBe(['next_number' => [6, 500]]);
});

it('records account changes, but never a password or token', function () {
    $user = User::factory()->create(['office' => 'ITD', 'role' => User::ROLE_USER]);
    $this->actingAs(User::factory()->create(['name' => 'Admin', 'role' => User::ROLE_ADMIN]));

    $user->update(['role' => User::ROLE_ADMIN, 'office' => 'ODG']);
    expect(auditEntry('user.updated')->changes)->toEqual(['role' => ['USER', 'ADMIN'], 'office' => ['ITD', 'ODG']]);

    $user->update(['password' => 'a-new-secret-password']);
    $entry = auditEntry('user.updated');
    expect($entry->changes)->toBe(['password' => ['(set)', '(set)']])
        ->and(json_encode($entry->getAttributes()))->not->toContain('a-new-secret-password')
        ->and(json_encode($entry->getAttributes()))->not->toContain($user->fresh()->password);

    // "Remember me" churn is not an event.
    $count = AuditLog::count();
    $user->forceFill(['remember_token' => 'abc'])->save();
    expect(AuditLog::count())->toBe($count);
});

it('records signing in, signing out, and failed attempts', function () {
    $user = User::factory()->create(['name' => 'ITD Clerk', 'email' => 'clerk@caap.gov.ph', 'office' => 'ITD']);

    $this->post('/login', ['email' => 'clerk@caap.gov.ph', 'password' => 'wrong-password']);
    expect(auditEntry('auth.failed'))
        ->user_name->toBe('ITD Clerk')
        ->context->toBe(['email' => 'clerk@caap.gov.ph'])
        ->and(json_encode(auditEntry('auth.failed')->getAttributes()))->not->toContain('wrong-password');

    $this->post('/login', ['email' => 'clerk@caap.gov.ph', 'password' => 'password']);
    expect(auditEntry('auth.login')?->user_id)->toBe($user->id);

    $this->post('/logout');
    expect(auditEntry('auth.logout')?->user_id)->toBe($user->id);
});

it('records who opened and downloaded a file, and the routing slip', function () {
    $user = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $record = Record::create([
        'reference' => 'ITD-2026-0002', 'subject' => 'Memo',
        'created_by' => 'ITD Clerk', 'origin' => 'ITD', 'owner' => 'ITD',
    ]);
    $file = Attachment::storeEncrypted($record, null, UploadedFile::fake()->createWithContent('memo.pdf', '%PDF-1.4 memo'), 'ITD Clerk');

    $this->actingAs($user);

    $this->get(route('attachments.view', $file))->assertOk();
    $this->get(route('attachments.download', $file))->assertOk();
    $this->get(route('records-pdf', $record->id))->assertOk();

    expect(auditEntry('attachment.viewed'))->subject_label->toBe('memo.pdf')->record_reference->toBe('ITD-2026-0002')
        ->and(auditEntry('attachment.downloaded'))->user_name->toBe('ITD Clerk')
        ->and(auditEntry('record.slip_opened'))->record_id->toBe($record->id);
});

/*
|--------------------------------------------------------------------------
| The log itself
|--------------------------------------------------------------------------
*/

it('cannot be changed or deleted', function () {
    Office::create(['name' => 'Audit Test Office', 'description' => 'For the audit test']);
    $entry = AuditLog::latest('id')->first();

    expect(fn () => $entry->update(['user_name' => 'Someone else']))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);
});

it('shows the audit log to admins only, read-only', function () {
    $this->actingAs(User::factory()->create(['name' => 'Admin Person', 'role' => User::ROLE_ADMIN]));
    Office::create(['name' => 'Audit Test Office', 'description' => 'For the audit test']);

    $this->get(AuditLogResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Office created')
        ->assertSee('Audit Test Office')
        ->assertSee('Admin Person');

    expect(AuditLogResource::canCreate())->toBeFalse()
        ->and(AuditLogResource::canDeleteAny())->toBeFalse();

    $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
        ->get(AuditLogResource::getUrl('index'))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Nothing is deleted from the admin panel
|--------------------------------------------------------------------------
*/

it('offers no delete anywhere in the admin setup lists', function (string $resource) {
    expect($resource::canDeleteAny())->toBeFalse()
        ->and($resource::canDelete(new ($resource::getModel())))->toBeFalse();
})->with([
    'records' => \App\Filament\Resources\RecordResource::class,
    'users' => \App\Filament\Resources\UserResource::class,
    'offices' => \App\Filament\Resources\OfficeResource::class,
    'statuses' => \App\Filament\Resources\StatusResource::class,
    'document types' => \App\Filament\Resources\DocumentCategoryResource::class,
    'access grants' => \App\Filament\Resources\AccessResource::class,
    'remark templates' => \App\Filament\Resources\RemarkTemplateResource::class,
    'reference sequences' => \App\Filament\Resources\ReferenceSequenceResource::class,
]);

it('lets no one delete their own account', function () {
    expect(\Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())->toBeFalse();
});
