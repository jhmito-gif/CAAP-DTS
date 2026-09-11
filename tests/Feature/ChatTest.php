<?php

use App\Livewire\ChatBox;
use App\Models\ChatAttachment;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates a one-to-one conversation only once', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $first = Conversation::findOrCreatePm($a, $b);
    $second = Conversation::findOrCreatePm($b, $a);

    expect($first->id)->toBe($second->id);
    expect($first->users()->count())->toBe(2);
    expect($first->titleFor($a))->toBe($b->name);
});

it('sends a message and clears the composer', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $conversation = Conversation::findOrCreatePm($a, $b);

    Livewire::actingAs($a)
        ->test(ChatBox::class)
        ->call('openConversation', $conversation->id)
        ->set('body', 'Hello there')
        ->call('sendMessage')
        ->assertSet('body', '');

    expect($conversation->messages()->count())->toBe(1);
    expect($conversation->messages()->first()->body)->toBe('Hello there');
});

it('counts unread conversations for the recipient only', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $conversation = Conversation::findOrCreatePm($a, $b);

    Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $b->id,
        'body' => 'ping',
    ]);

    // Recipient sees it as unread.
    Livewire::actingAs($a)->test(ChatBox::class)->assertSet('open', false)
        ->assertViewHas('unreadCount', 1);

    // Sender does not.
    Livewire::actingAs($b)->test(ChatBox::class)->assertViewHas('unreadCount', 0);

    // Opening it marks it read.
    Livewire::actingAs($a)->test(ChatBox::class)->call('openConversation', $conversation->id);
    Livewire::actingAs($a)->test(ChatBox::class)->assertViewHas('unreadCount', 0);
});

it('creates a group with the selected members plus the creator', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $c = User::factory()->create();

    Livewire::actingAs($a)
        ->test(ChatBox::class)
        ->set('groupName', 'Ops Team')
        ->set('groupMembers', [$b->id, $c->id])
        ->call('createGroup')
        ->assertSet('view', 'thread');

    $group = Conversation::where('type', 'group')->first();
    expect($group)->not->toBeNull();
    expect($group->name)->toBe('Ops Team');
    expect($group->users()->count())->toBe(3);
});

it('burns an access token after it is revealed once', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $conversation = Conversation::findOrCreatePm($a, $b);

    // b sends a token to a
    Livewire::actingAs($b)
        ->test(ChatBox::class)
        ->call('openConversation', $conversation->id)
        ->call('sendToken', 'secret-42');

    $token = $conversation->messages()->where('is_token', true)->first();
    expect($token->body)->not->toContain('secret-42'); // encrypted at rest

    // a reveals it -> plaintext once, then gone
    Livewire::actingAs($a)
        ->test(ChatBox::class)
        ->call('openConversation', $conversation->id)
        ->call('revealMessage', $token->id)
        ->assertSet('revealed', 'secret-42');

    expect(Message::find($token->id))->toBeNull();
});

it('does not let an outsider reveal a token', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = Conversation::findOrCreatePm($a, $b);

    $token = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $b->id,
        'body' => Crypt::encryptString('nope'),
        'is_token' => true,
    ]);

    Livewire::actingAs($outsider)
        ->test(ChatBox::class)
        ->call('revealMessage', $token->id)
        ->assertSet('revealed', null);

    expect(Message::find($token->id))->not->toBeNull();
});

it('opens a request-access PM with the record creator', function () {
    $owner = User::factory()->create(['name' => 'Owner One', 'office' => 'ITD']);
    $requester = User::factory()->create(['office' => 'FID']);

    $record = Record::create([
        'reference' => 'ITD-2026-4242',
        'subject' => 'Confidential memo',
        'created_by' => 'Owner One',
        'origin' => 'ITD',
        'owner' => 'ITD',
        'is_confidential' => true,
    ]);

    Livewire::actingAs($requester)
        ->test(ChatBox::class)
        ->call('requestAccessToken', $record->id)
        ->assertSet('open', true)
        ->assertSet('view', 'thread');

    $conversation = Conversation::where('type', 'pm')
        ->whereHas('users', fn ($q) => $q->where('users.id', $owner->id))
        ->whereHas('users', fn ($q) => $q->where('users.id', $requester->id))
        ->first();

    expect($conversation)->not->toBeNull();

    $message = $conversation->messages()->first();
    expect($message->body)->toContain('Requesting the access token');
    expect($message->record_id)->toBe($record->id);
});

it('attaches an encrypted file that expires in 15 days', function () {
    Storage::fake('local');
    $a = User::factory()->create();
    $b = User::factory()->create();
    $conversation = Conversation::findOrCreatePm($a, $b);

    Livewire::actingAs($a)
        ->test(ChatBox::class)
        ->call('openConversation', $conversation->id)
        ->set('files', [UploadedFile::fake()->create('doc.pdf', 120, 'application/pdf')])
        ->call('sendMessage')
        ->assertSet('files', []);

    $att = ChatAttachment::first();
    expect($att)->not->toBeNull();
    expect($att->original_name)->toBe('doc.pdf');
    expect($att->is_encrypted)->toBeTrue();
    expect($att->expires_at->isBetween(now()->addDays(14), now()->addDays(16)))->toBeTrue();

    Storage::disk('local')->assertExists($att->path);

    // Bytes on disk are encrypted, not the raw upload.
    $raw = Storage::disk('local')->get($att->path);
    expect($raw)->not->toBe($att->contents());
    expect(Crypt::decryptString($raw))->toBe($att->contents());
});

it('rejects files larger than 10 MB', function () {
    Storage::fake('local');
    $a = User::factory()->create();
    $b = User::factory()->create();
    $conversation = Conversation::findOrCreatePm($a, $b);

    Livewire::actingAs($a)
        ->test(ChatBox::class)
        ->call('openConversation', $conversation->id)
        ->set('files', [UploadedFile::fake()->create('huge.zip', 11000)]) // 11 MB
        ->assertHasErrors(['files.*']);

    expect(ChatAttachment::count())->toBe(0);
});

it('permanently purges expired attachments and drops empty file-only messages', function () {
    Storage::fake('local');
    $a = User::factory()->create();
    $b = User::factory()->create();
    $conversation = Conversation::findOrCreatePm($a, $b);

    // File-only message with an already-expired attachment.
    $emptyMsg = Message::create(['conversation_id' => $conversation->id, 'user_id' => $a->id, 'body' => '']);
    Storage::disk('local')->put('chat-attachments/x/gone.bin', Crypt::encryptString('data'));
    $expired = ChatAttachment::create([
        'message_id' => $emptyMsg->id, 'original_name' => 'gone.bin',
        'path' => 'chat-attachments/x/gone.bin', 'disk' => 'local', 'mime_type' => 'application/octet-stream',
        'size' => 4, 'is_encrypted' => true, 'expires_at' => now()->subDay(),
    ]);

    // Message with text keeps its bubble; only the file goes.
    $textMsg = Message::create(['conversation_id' => $conversation->id, 'user_id' => $a->id, 'body' => 'see attached']);
    Storage::disk('local')->put('chat-attachments/x/old.bin', Crypt::encryptString('data'));
    $expired2 = ChatAttachment::create([
        'message_id' => $textMsg->id, 'original_name' => 'old.bin',
        'path' => 'chat-attachments/x/old.bin', 'disk' => 'local', 'mime_type' => 'application/octet-stream',
        'size' => 4, 'is_encrypted' => true, 'expires_at' => now()->subDay(),
    ]);

    // A fresh file survives.
    $freshMsg = Message::create(['conversation_id' => $conversation->id, 'user_id' => $a->id, 'body' => '']);
    Storage::disk('local')->put('chat-attachments/x/fresh.bin', Crypt::encryptString('data'));
    $fresh = ChatAttachment::create([
        'message_id' => $freshMsg->id, 'original_name' => 'fresh.bin',
        'path' => 'chat-attachments/x/fresh.bin', 'disk' => 'local', 'mime_type' => 'application/octet-stream',
        'size' => 4, 'is_encrypted' => true, 'expires_at' => now()->addDays(10),
    ]);

    Artisan::call('chat:purge-attachments');

    // Expired files and their bytes are gone.
    expect(ChatAttachment::find($expired->id))->toBeNull();
    expect(ChatAttachment::find($expired2->id))->toBeNull();
    Storage::disk('local')->assertMissing('chat-attachments/x/gone.bin');
    Storage::disk('local')->assertMissing('chat-attachments/x/old.bin');

    // Empty file-only message removed; text message kept; fresh file kept.
    expect(Message::find($emptyMsg->id))->toBeNull();
    expect(Message::find($textMsg->id))->not->toBeNull();
    expect(ChatAttachment::find($fresh->id))->not->toBeNull();
    Storage::disk('local')->assertExists('chat-attachments/x/fresh.bin');
});

it('serves a chat file to a member but forbids outsiders', function () {
    Storage::fake('local');
    $a = User::factory()->create();
    $b = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = Conversation::findOrCreatePm($a, $b);

    $msg = Message::create(['conversation_id' => $conversation->id, 'user_id' => $a->id, 'body' => '']);
    Storage::disk('local')->put('chat-attachments/x/pic.png', Crypt::encryptString('imgbytes'));
    $att = ChatAttachment::create([
        'message_id' => $msg->id, 'original_name' => 'pic.png',
        'path' => 'chat-attachments/x/pic.png', 'disk' => 'local', 'mime_type' => 'image/png',
        'size' => 8, 'is_encrypted' => true, 'expires_at' => now()->addDays(10),
    ]);

    $this->actingAs($b)->get(route('chat-attachments.view', $att))
        ->assertOk()->assertSee('imgbytes');

    $this->actingAs($outsider)->get(route('chat-attachments.view', $att))
        ->assertForbidden();
});

it('returns 410 for an expired chat file still referenced by a link', function () {
    Storage::fake('local');
    $a = User::factory()->create();
    $b = User::factory()->create();
    $conversation = Conversation::findOrCreatePm($a, $b);

    $msg = Message::create(['conversation_id' => $conversation->id, 'user_id' => $a->id, 'body' => '']);
    Storage::disk('local')->put('chat-attachments/x/exp.png', Crypt::encryptString('x'));
    $att = ChatAttachment::create([
        'message_id' => $msg->id, 'original_name' => 'exp.png',
        'path' => 'chat-attachments/x/exp.png', 'disk' => 'local', 'mime_type' => 'image/png',
        'size' => 1, 'is_encrypted' => true, 'expires_at' => now()->subMinute(),
    ]);

    $this->actingAs($a)->get(route('chat-attachments.view', $att))->assertStatus(410);
});
