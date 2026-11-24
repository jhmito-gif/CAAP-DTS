<?php

use App\Livewire\ManageRecord;
use App\Livewire\OutgoingTransaction;
use App\Models\AuditLog;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** A confidential ITD record, locked with "old-token", made by the given person. */
function lockedRecordBy(User $creator, bool $confidential = true): Record
{
    $record = new Record([
        'reference' => 'ITD-2026-0301',
        'subject' => 'Personnel complaint',
        'created_by' => $creator->name,
        'origin' => 'ITD',
        'owner' => 'ITD',
        'is_confidential' => $confidential,
    ]);

    if ($confidential) {
        $record->setConfidentialToken('old-token');
    }

    $record->save();

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'Pending',
        'destination' => 'ODG',
        'office' => 'ITD',
        'forwarded_by' => $creator->name,
    ]);

    return $record->fresh();
}

function resetTokenAs(User $user, Record $record, string $new = 'new-token', ?string $confirm = null, string $password = 'password')
{
    test()->actingAs($user);

    return Livewire::test(ManageRecord::class)
        ->call('startTokenReset', $record->id)
        ->set('newToken', $new)
        ->set('newToken_confirmation', $confirm ?? $new)
        ->set('accountPassword', $password)
        ->call('resetToken');
}

it('lets the creator reset a forgotten access token with their own password', function () {
    $creator = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $record = lockedRecordBy($creator);

    resetTokenAs($creator, $record)
        ->assertHasNoErrors()
        ->assertDispatched('close-reset-token-modal');

    $record->refresh();

    expect($record->checkConfidentialToken('old-token'))->toBeFalse()
        ->and($record->checkConfidentialToken('new-token'))->toBeTrue();

    $entry = AuditLog::where('action', 'record.token_reset')->sole();

    expect($entry)
        ->user_name->toBe('ITD Clerk')
        ->record_id->toBe($record->id)
        ->changes->toBe(['confidential_token' => ['(set)', '(set)']])
        ->and($entry->label())->toBe('Access token reset')
        // Neither the token nor the password is ever written down.
        ->and(json_encode($entry->getAttributes()))->not->toContain('new-token')
        ->and(json_encode($entry->getAttributes()))->not->toContain('password"');
});

it('logs sending a confidential record as one creation, not a token reset', function () {
    $this->actingAs(User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']));

    Livewire::test(\App\Livewire\CreateOutgoing::class)
        ->set('office', 'ODG')
        ->set('status', 'Pending')
        ->set('subject', 'Personnel complaint')
        ->set('remarks', 'For action')
        ->set('isConfidential', true)
        ->set('confidentialToken', 'first-token')
        ->call('createRecord')
        ->assertHasNoErrors();

    expect(Record::sole()->checkConfidentialToken('first-token'))->toBeTrue()
        ->and(AuditLog::where('action', 'record.token_reset')->exists())->toBeFalse()
        ->and(AuditLog::where('action', 'record.updated')->exists())->toBeFalse()
        ->and(AuditLog::where('action', 'record.created')->sole()->changes['confidential_token'])->toBe([null, '(set)']);
});

it('lets an admin reset it, also after another office received the record', function () {
    $creator = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $admin = User::factory()->create(['name' => 'Admin Person', 'office' => 'ITD', 'role' => User::ROLE_ADMIN]);
    $record = lockedRecordBy($creator);
    Transaction::where('record_id', $record->id)->update(['date_recieved' => now(), 'recieved_by' => 'ODG Clerk']);

    // The creator can no longer edit it, but can still reset its token.
    expect($creator->can('update', $record))->toBeFalse()
        ->and($creator->can('resetAccessToken', $record))->toBeTrue();

    resetTokenAs($admin, $record)->assertHasNoErrors();

    expect($record->fresh()->checkConfidentialToken('new-token'))->toBeTrue()
        ->and(AuditLog::where('action', 'record.token_reset')->sole()->user_name)->toBe('Admin Person');
});

it('does not let a colleague in the same office, or another office, reset it', function (string $office) {
    $creator = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $record = lockedRecordBy($creator);
    $someoneElse = User::factory()->create(['office' => $office]);

    expect($someoneElse->can('resetAccessToken', $record))->toBeFalse();

    resetTokenAs($someoneElse, $record)
        ->assertNotDispatched('open-reset-token-modal')
        ->assertDispatched('banner-message');

    expect($record->fresh()->checkConfidentialToken('old-token'))->toBeTrue()
        ->and(AuditLog::where('action', 'record.token_reset')->exists())->toBeFalse();
})->with(['same office' => 'ITD', 'other office' => 'ODG']);

it('keeps the old token when the password or the confirmation is wrong', function () {
    $creator = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $record = lockedRecordBy($creator);

    resetTokenAs($creator, $record, password: 'not-my-password')->assertHasErrors(['accountPassword']);
    resetTokenAs($creator, $record, confirm: 'something-else')->assertHasErrors(['newToken']);
    resetTokenAs($creator, $record, new: 'abc')->assertHasErrors(['newToken']);

    expect($record->fresh()->checkConfidentialToken('old-token'))->toBeTrue();
});

it('stops guessing the account password after five tries', function () {
    $creator = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $record = lockedRecordBy($creator);
    RateLimiter::clear('reset-access-token:' . $creator->id);

    foreach (range(1, 5) as $try) {
        resetTokenAs($creator, $record, password: "guess-{$try}")->assertHasErrors(['accountPassword']);
    }

    // Even the right password waits now.
    resetTokenAs($creator, $record)->assertHasErrors(['accountPassword']);

    expect($record->fresh()->checkConfidentialToken('old-token'))->toBeTrue();
});

it('has nothing to reset on a record that is not confidential', function () {
    $creator = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $record = lockedRecordBy($creator, confidential: false);

    expect($creator->can('resetAccessToken', $record))->toBeFalse();

    resetTokenAs($creator, $record)->assertNotDispatched('open-reset-token-modal');

    expect($record->fresh()->confidential_token)->toBeNull();
});

it('offers the reset on the record page only to those who may use it', function () {
    $creator = User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']);
    $record = lockedRecordBy($creator);

    $this->actingAs($creator);
    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->assertSee('Reset access token')
        ->assertSee('Forgot it? Reset the access token');

    $this->actingAs(User::factory()->create(['office' => 'ITD']));
    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->assertDontSee('Reset access token')
        ->assertDontSee('Forgot it? Reset the access token');
});
