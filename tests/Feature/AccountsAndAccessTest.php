<?php

use App\Filament\Resources\AccessResource\Pages\EditAccess;
use App\Filament\Resources\AccessResource\Pages\ListAccesses;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Livewire\OutgoingTransaction;
use App\Livewire\TagPeople;
use App\Livewire\TransactionTable;
use App\Models\Access;
use App\Models\AuditLog;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['name' => 'Admin Person', 'office' => 'ITD', 'role' => User::ROLE_ADMIN]);
}

/** A confidential ITD record sent to ODG, locked with "open-sesame". */
function confidentialItdRecord(): Record
{
    $record = new Record([
        'reference' => 'ITD-2026-0401',
        'subject' => 'Personnel complaint',
        'created_by' => 'ITD Clerk',
        'origin' => 'ITD',
        'owner' => 'ITD',
        'is_confidential' => true,
    ]);
    $record->setConfidentialToken('open-sesame');
    $record->save();

    Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For action',
        'status' => 'Pending',
        'destination' => 'ODG',
        'office' => 'ITD',
        'forwarded_by' => 'ITD Clerk',
    ]);

    return $record;
}

/*
|--------------------------------------------------------------------------
| Revoking record access
|--------------------------------------------------------------------------
*/

it('lets an admin revoke an access grant, and logs who did', function () {
    $record = confidentialItdRecord();
    $legal = User::factory()->create(['office' => 'LEGAL']);
    $grant = Access::create(['record_id' => $record->id, 'office' => 'LEGAL']);

    expect($record->canViewConfidentialDetails($legal))->toBeTrue();

    $this->actingAs(adminUser());

    Livewire::test(ListAccesses::class)
        ->assertTableActionVisible('revoke', $grant)
        ->callTableAction('revoke', $grant)
        ->assertNotified();

    expect(Access::find($grant->id))->toBeNull()
        ->and($record->fresh()->canViewConfidentialDetails($legal))->toBeFalse();

    $entry = AuditLog::where('action', 'access.deleted')->sole();

    expect($entry->label())->toBe('Record access revoked')
        ->and($entry->user_name)->toBe('Admin Person')
        ->and($entry->record_reference)->toBe('ITD-2026-0401')
        ->and($entry->changes['office'])->toBe(['LEGAL', null]);
});

it('also revokes from the grant\'s own page, then goes back to the list', function () {
    $record = confidentialItdRecord();
    $grant = Access::create(['record_id' => $record->id, 'office' => 'LEGAL']);

    $this->actingAs(adminUser());

    Livewire::test(EditAccess::class, ['record' => $grant->getRouteKey()])
        ->callAction('revoke')
        ->assertRedirect();

    expect(Access::find($grant->id))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Deactivating accounts
|--------------------------------------------------------------------------
*/

it('lets an admin deactivate someone who left, and bring them back', function () {
    $leaver = User::factory()->create(['name' => 'Juan Leaver', 'office' => 'ITD']);
    $admin = adminUser();
    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('reactivate', $leaver)
        ->callTableAction('deactivate', $leaver)
        ->assertNotified();

    expect($leaver->fresh()->isDeactivated())->toBeTrue()
        ->and(AuditLog::where('action', 'user.deactivated')->sole())
        ->user_name->toBe('Admin Person')
        ->subject_label->toBe('Juan Leaver');

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('deactivate', $leaver)
        ->callTableAction('reactivate', $leaver);

    expect($leaver->fresh()->isDeactivated())->toBeFalse()
        ->and(AuditLog::where('action', 'user.reactivated')->exists())->toBeTrue()
        // Nothing of theirs was removed along the way.
        ->and(User::find($leaver->id))->not->toBeNull();
});

it('does not let an admin deactivate their own account', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    Livewire::test(ListUsers::class)->assertTableActionHidden('deactivate', $admin);
    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])->assertActionHidden('deactivate');
});

it('refuses to sign in a deactivated account, saying so only to someone with the password', function () {
    $leaver = User::factory()->create(['name' => 'Juan Leaver', 'email' => 'juan@caap.gov.ph', 'office' => 'ITD']);
    $leaver->deactivate();

    // Wrong password: the ordinary error, nothing about the account.
    $this->post('/login', ['email' => 'juan@caap.gov.ph', 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);
    $this->assertGuest();

    // Right password: told it is deactivated, still not signed in.
    $this->post('/login', ['email' => 'juan@caap.gov.ph', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'This account has been deactivated. Ask an admin if you need it back.']);
    $this->assertGuest();

    expect(AuditLog::where('action', 'auth.blocked')->sole()->user_id)->toBe($leaver->id)
        ->and(AuditLog::where('action', 'auth.failed')->sole()->user_name)->toBe('Juan Leaver')
        ->and(AuditLog::where('action', 'auth.login')->exists())->toBeFalse();
});

it('still signs in an active account', function () {
    User::factory()->create(['email' => 'clerk@caap.gov.ph', 'office' => 'ITD']);

    $this->post('/login', ['email' => 'clerk@caap.gov.ph', 'password' => 'password']);

    $this->assertAuthenticated();
});

it('signs out someone deactivated while they were signed in', function () {
    $leaver = User::factory()->create(['office' => 'ITD']);

    $this->actingAs($leaver)->get('/dashboard')->assertOk();

    $leaver->deactivate();

    $this->get('/dashboard')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    // The session's sign-in is gone. (The test keeps the sanctum guard's copy
    // of the user between requests; a real next request starts without it.)
    $this->assertGuest('web');
    expect(AuditLog::where('action', 'auth.logout')->sole()->user_id)->toBe($leaver->id);
});

it('keeps a deactivated admin out of the admin panel', function () {
    $admin = adminUser();
    $admin->deactivate();

    expect($admin->fresh()->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});

it('stops a deactivated account\'s API tokens', function () {
    $leaver = User::factory()->create(['office' => 'ITD']);
    $token = $leaver->createToken('script')->plainTextToken;

    $this->withToken($token)->getJson('/api/user')->assertOk();

    $leaver->deactivate();
    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
});

it('drops deactivated people from the people lists', function () {
    $record = confidentialItdRecord();
    $this->actingAs(User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']));

    User::factory()->create(['name' => 'Still Here', 'office' => 'ITD']);
    $leaver = User::factory()->create(['name' => 'Long Gone', 'office' => 'ITD']);
    $leaver->deactivate();

    Livewire::test(TagPeople::class, ['recordId' => $record->id])
        ->call('openModal')
        ->set('office', 'ITD')
        ->assertSee('Still Here')
        ->assertDontSee('Long Gone');
});

/*
|--------------------------------------------------------------------------
| Who looked at a confidential record
|--------------------------------------------------------------------------
*/

it('logs who opened a confidential record\'s page, and who unlocked it', function () {
    $record = confidentialItdRecord();
    $this->actingAs(User::factory()->create(['name' => 'ITD Clerk', 'office' => 'ITD']));

    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->set('unlockToken', 'wrong-guess')
        ->call('unlockConfidential')
        ->set('unlockToken', 'open-sesame')
        ->call('unlockConfidential')
        ->assertSet('confidentialUnlocked', true);

    expect(AuditLog::where('action', 'record.viewed')->sole())
        ->user_name->toBe('ITD Clerk')
        ->record_id->toBe($record->id)
        ->context->toBeNull()
        ->and(AuditLog::where('action', 'record.unlock_failed')->sole()->user_name)->toBe('ITD Clerk')
        ->and(AuditLog::where('action', 'record.unlocked')->sole()->user_name)->toBe('ITD Clerk')
        // The token typed is never kept.
        ->and(AuditLog::pluck('context')->toJson())->not->toContain('wrong-guess');
});

it('logs a masked view too, from an office that only sees the record exists', function () {
    $record = confidentialItdRecord();
    $this->actingAs(User::factory()->create(['name' => 'ODG Clerk', 'office' => 'ODG']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id]);

    expect(AuditLog::where('action', 'record.viewed')->sole())
        ->user_name->toBe('ODG Clerk')
        ->context->toBe(['masked' => true]);
});

it('does not log views of ordinary records', function () {
    $record = confidentialItdRecord();
    $record->forceFill(['is_confidential' => false])->save();
    $this->actingAs(User::factory()->create(['office' => 'ITD']));

    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id]);

    expect(AuditLog::where('action', 'record.viewed')->exists())->toBeFalse();
});
