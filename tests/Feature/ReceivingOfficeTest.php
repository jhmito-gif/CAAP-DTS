<?php

use App\Livewire\IncomingTable;
use App\Livewire\OutgoingTable;
use App\Livewire\OutgoingTransaction;
use App\Livewire\TransactionTable;
use App\Models\Record;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** CPO created a record and sent it to ODG; ODG has not received it yet. */
function sentFromCpoToOdg(): array
{
    $record = Record::create([
        'reference' => 'CPO-2026-2507',
        'subject' => 'Request for approval',
        'created_by' => 'CPO Clerk',
        'origin' => 'CPO',
        'owner' => 'CPO',
    ]);

    $movement = Transaction::create([
        'record_id' => $record->id,
        'internal_reference' => $record->reference,
        'remarks' => 'For approval',
        'status' => 'Pending',
        'destination' => 'ODG',
        'office' => 'CPO',
        'forwarded_by' => 'CPO Clerk',
    ]);

    return [$record, $movement];
}

it('will not let the sending office mark its own movement as received', function () {
    [$record, $movement] = sentFromCpoToOdg();

    $this->actingAs(User::factory()->create(['name' => 'CPO Clerk', 'office' => 'CPO']));

    // No button on either record page...
    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->assertDontSee("markAsReceived({$movement->id})", false);

    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->assertDontSee("markAsReceived({$movement->id})", false);

    // ...and calling the action directly is refused.
    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id)
        ->assertDispatched('banner-message', style: 'danger', message: 'Only ODG can mark this as received.');

    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id)
        ->assertDispatched('banner-message', style: 'danger');

    expect($movement->fresh()->date_recieved)->toBeNull()
        ->and($movement->fresh()->recieved_by)->toBeNull();
});

it('lets the office the document was sent to receive it', function () {
    [$record, $movement] = sentFromCpoToOdg();

    $this->actingAs(User::factory()->create(['name' => 'ODG Clerk', 'office' => 'ODG']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->assertSee("markAsReceived({$movement->id})", false)
        ->call('markAsReceived', $movement->id);

    expect($movement->fresh()->recieved_by)->toBe('ODG Clerk');
});

it('does not let an admin from another office receive it for them', function () {
    [$record, $movement] = sentFromCpoToOdg();

    // An admin who sits in the sending office -- the case that unlocked
    // "Send" for the receiving office before it had the document.
    $this->actingAs(User::factory()->create(['name' => 'CPO Admin', 'office' => 'CPO', 'role' => User::ROLE_ADMIN]));

    expect($movement->isReceivableBy(auth()->user()))->toBeFalse();

    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->assertDontSee("markAsReceived({$movement->id})", false)
        ->call('markAsReceived', $movement->id)
        ->assertDispatched('banner-message', style: 'danger', message: 'Only ODG can mark this as received.');

    expect($movement->fresh()->date_recieved)->toBeNull();
});

it('lets an admin who belongs to the receiving office receive it', function () {
    [$record, $movement] = sentFromCpoToOdg();

    $this->actingAs(User::factory()->create(['name' => 'ODG Admin', 'office' => 'ODG', 'role' => User::ROLE_ADMIN]));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id);

    expect($movement->fresh()->recieved_by)->toBe('ODG Admin')
        ->and($movement->fresh()->isReceivableBy(auth()->user()))->toBeFalse();
});

it('does not let the receiving office send it on before receiving it', function () {
    [$record, $movement] = sentFromCpoToOdg();

    $this->actingAs(User::factory()->create(['name' => 'ODG Clerk', 'office' => 'ODG']));

    // No Send button, and a crafted request is refused too.
    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->assertViewHas('showSendButton', false)
        ->set('office', 'HR')
        ->set('status', 'Pending')
        ->set('remarks', 'Forwarding')
        ->call('sendTransaction');

    expect(Transaction::where('record_id', $record->id)->count())->toBe(1);

    // Once received, it goes on.
    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id)
        ->set('office', 'HR')
        ->set('status', 'Pending')
        ->set('remarks', 'Forwarding')
        ->call('sendTransaction');

    expect(Transaction::where('record_id', $record->id)->count())->toBe(2);
});

it('lets the sending office send the same document to several offices at once', function (string $page) {
    [$record] = sentFromCpoToOdg();

    // ODG has not received its copy; CPO still sends one to HR and to Legal.
    $this->actingAs(User::factory()->create(['name' => 'CPO Clerk', 'office' => 'CPO']));

    $component = Livewire::test($page, ['recordId' => $record->id])->assertViewHas('showSendButton', true);

    foreach (['HR', 'LEGAL'] as $office) {
        $component->set('office', $office)->set('status', 'Pending')->set('remarks', 'For comment')->call('sendTransaction');
    }

    expect(Transaction::where('record_id', $record->id)->whereNull('date_recieved')->pluck('destination')->sort()->values()->all())
        ->toBe(['HR', 'LEGAL', 'ODG']);
})->with(['owner page' => OutgoingTransaction::class, 'record page' => TransactionTable::class]);

it('lets an office that received it send it on in parallel too', function () {
    [$record, $movement] = sentFromCpoToOdg();
    $movement->update(['recieved_by' => 'ODG Clerk', 'date_recieved' => now()]);

    $this->actingAs(User::factory()->create(['name' => 'ODG Clerk', 'office' => 'ODG']));

    $component = Livewire::test(TransactionTable::class, ['recordId' => $record->id]);

    foreach (['HR', 'LEGAL'] as $office) {
        $component->set('office', $office)->set('status', 'Pending')->set('remarks', 'For action')->call('sendTransaction');
    }

    $component->assertViewHas('showSendButton', true);

    expect(Transaction::where('record_id', $record->id)->where('office', 'ODG')->count())->toBe(2);
});

it('does not send a second copy to an office still waiting for the first', function () {
    [$record] = sentFromCpoToOdg();

    $this->actingAs(User::factory()->create(['name' => 'CPO Clerk', 'office' => 'CPO']));

    Livewire::test(OutgoingTransaction::class, ['recordId' => $record->id])
        ->set('office', 'ODG')->set('status', 'Pending')->set('remarks', 'Again')
        ->call('sendTransaction');

    expect(Transaction::where('record_id', $record->id)->count())->toBe(1)
        ->and(\App\Support\DocumentHandling::sendBlocker($record, auth()->user(), 'ODG'))
        ->toBe('ODG has not yet received the copy already sent to it.')
        ->and(\App\Support\DocumentHandling::sendBlocker($record, auth()->user(), 'HR'))->toBeNull();
});

it('keeps the time a document was received, not just the day', function () {
    [$record, $movement] = sentFromCpoToOdg();
    $this->travelTo(now()->setTime(15, 42, 10));

    $this->actingAs(User::factory()->create(['name' => 'ODG Clerk', 'office' => 'ODG']));

    Livewire::test(TransactionTable::class, ['recordId' => $record->id])
        ->call('markAsReceived', $movement->id);

    expect(\Carbon\Carbon::parse($movement->fresh()->date_recieved)->format('H:i:s'))->toBe('15:42:10')
        ->and($movement->fresh()->receivedAtLabel())->toEndWith('3:42 PM');

    // The routing slip prints it.
    $this->get(route('records-pdf', $record->id))->assertOk();
});

it('shows an older receipt, saved without a time, as the day alone', function () {
    [, $movement] = sentFromCpoToOdg();
    $movement->forceFill(['date_recieved' => '2026-11-20 00:00:00'])->save();

    expect($movement->fresh()->receivedAtLabel())->toBe('November 20, 2026')
        ->and($movement->fresh()->receivedAtLabel('j M Y g:i A', 'j M Y'))->toBe('20 Nov 2026');
});

it('restores the time of receipts made since the audit log went live', function () {
    [, $movement] = sentFromCpoToOdg();
    $older = Transaction::create([
        'record_id' => $movement->record_id, 'internal_reference' => 'CPO-2026-2507',
        'remarks' => 'Earlier', 'status' => 'Pending', 'destination' => 'HR', 'office' => 'ODG', 'forwarded_by' => 'ODG Clerk',
    ]);

    // As the date-only column left them.
    \Illuminate\Support\Facades\DB::table('transactions')->where('id', $movement->id)->update(['date_recieved' => '2026-11-24 00:00:00']);
    \Illuminate\Support\Facades\DB::table('transactions')->where('id', $older->id)->update(['date_recieved' => '2026-11-10 00:00:00']);
    \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
        'action' => 'transaction.received', 'subject_type' => 'Transaction', 'subject_id' => $movement->id,
        'created_at' => '2026-11-24 12:40:05',
    ]);

    (require database_path('migrations/2026_11_24_030000_keep_time_of_receipt_on_transactions.php'))->up();

    expect((string) \Illuminate\Support\Facades\DB::table('transactions')->where('id', $movement->id)->value('date_recieved'))->toBe('2026-11-24 12:40:05')
        // No audit entry: the day stays, with no time made up.
        ->and((string) \Illuminate\Support\Facades\DB::table('transactions')->where('id', $older->id)->value('date_recieved'))->toStartWith('2026-11-10 00:00:00');
});

it('shows routing in the incoming list as sender first, then receiver', function () {
    [, $movement] = sentFromCpoToOdg();
    $movement->update(['recieved_by' => 'ODG Clerk', 'date_recieved' => now()]);

    $this->actingAs(User::factory()->create(['name' => 'ODG Clerk', 'office' => 'ODG']));

    Livewire::test(IncomingTable::class)
        ->assertSeeInOrder(['Sent by CPO Clerk', 'Received by ODG Clerk'], false);
});

it('offers the page size the lists open with, and ignores sizes it does not offer', function (string $table) {
    $this->actingAs(User::factory()->create(['office' => 'ODG']));

    Livewire::test($table)
        ->assertSee('<option value="10">10</option>', false)
        ->set('perPage', 100000)
        ->assertViewHas('data', fn ($data) => $data->perPage() === 10)
        ->set('perPage', 50)
        ->assertViewHas('data', fn ($data) => $data->perPage() === 50);
})->with(['incoming' => IncomingTable::class, 'outgoing' => OutgoingTable::class]);
