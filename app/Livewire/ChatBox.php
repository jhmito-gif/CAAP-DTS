<?php

namespace App\Livewire;

use App\Models\ChatAttachment;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageHide;
use App\Models\Record;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatBox extends Component
{
    use WithFileUploads;

    /** Chat files are permanently deleted this many days after upload. */
    public const RETENTION_DAYS = 15;

    public bool $open = false;

    /** 'list' | 'thread' | 'new-pm' | 'new-group' */
    public string $view = 'list';

    public ?int $activeId = null;

    public string $body = '';

    /** Files queued in the composer (temporary uploads, not yet sent). */
    public array $files = [];

    // New conversation inputs
    public string $search = '';
    public string $groupName = '';
    public array $groupMembers = [];

    // Burn-after-read reveal (shown once, then gone)
    public ?string $revealed = null;

    // Inline edit state
    public ?int $editingId = null;
    public string $editBody = '';

    // Highest inbound message id already seen -- used to ring the notification
    // sound only when something genuinely new arrives.
    public int $lastSeenInboundId = 0;

    public function mount(): void
    {
        $this->lastSeenInboundId = $this->maxInboundId();
    }

    public function openChat(): void
    {
        $this->open = true;
        $this->view = 'list';
    }

    public function closeChat(): void
    {
        $this->open = false;
        $this->reset(['view', 'activeId', 'body', 'files', 'search', 'groupName', 'groupMembers', 'revealed', 'editingId', 'editBody']);
        $this->view = 'list';
    }

    public function openConversation(int $id): void
    {
        $conversation = $this->userConversations()->firstWhere('id', $id);

        if (! $conversation) {
            return;
        }

        $this->open = true;
        $this->activeId = $id;
        $this->view = 'thread';
        $this->revealed = null;
        $this->markRead($conversation);
    }

    public function backToList(): void
    {
        $this->view = 'list';
        $this->activeId = null;
        $this->revealed = null;
        $this->cancelEdit();
    }

    protected function userConversations()
    {
        return Auth::user()
            ->conversations()
            ->with(['users', 'lastMessage.attachments'])
            ->get()
            ->sortByDesc(fn ($c) => optional($c->lastMessage)->created_at ?? $c->updated_at)
            ->values();
    }

    protected function markRead(Conversation $conversation): void
    {
        $conversation->users()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);
    }

    /** Validate files as they are added to the composer. */
    public function updatedFiles(): void
    {
        $this->validate([
            'files' => 'max:5',
            'files.*' => 'file|max:10240', // 10 MB each
        ], [
            'files.max' => 'You can attach up to 5 files at a time.',
            'files.*.max' => 'Each file must be 10 MB or smaller.',
        ]);
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function sendMessage(): void
    {
        $this->body = trim($this->body);

        if (($this->body === '' && empty($this->files)) || ! $this->activeId) {
            return;
        }

        if (! empty($this->files)) {
            $this->updatedFiles();
        }

        $conversation = $this->userConversations()->firstWhere('id', $this->activeId);
        if (! $conversation) {
            return;
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
            'body' => $this->body,
        ]);

        foreach ($this->files as $file) {
            $ext = $file->getClientOriginalExtension() ?: 'bin';
            $path = "chat-attachments/{$conversation->id}/" . Str::random(40) . '.' . $ext;

            // Encrypt at rest; purged permanently after the retention window.
            Storage::disk('local')->put($path, Crypt::encryptString($file->get()));

            ChatAttachment::create([
                'message_id' => $message->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'disk' => 'local',
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_encrypted' => true,
                'expires_at' => now()->addDays(self::RETENTION_DAYS),
            ]);
        }

        $conversation->touch();
        $this->reset('body', 'files');
        $this->markRead($conversation);
    }

    /** Messages in a conversation the current user belongs to. */
    protected function visibleMessageQuery()
    {
        return Message::whereHas('conversation.users', fn ($q) => $q->where('users.id', Auth::id()));
    }

    protected function findOwnMessage(?int $id): ?Message
    {
        return $id ? $this->visibleMessageQuery()->where('user_id', Auth::id())->find($id) : null;
    }

    // -- Edit -------------------------------------------------------------

    public function startEdit(int $id): void
    {
        $message = $this->findOwnMessage($id);

        if (! $message || $message->is_token || $message->isDeletedForEveryone()) {
            return;
        }

        $this->editingId = $message->id;
        $this->editBody = $message->body;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editBody = '';
    }

    public function saveEdit(): void
    {
        $message = $this->findOwnMessage($this->editingId);
        $body = trim($this->editBody);

        if ($message && ! $message->is_token && ! $message->isDeletedForEveryone() && $body !== '') {
            $message->update(['body' => $body, 'edited_at' => now()]);
        }

        $this->cancelEdit();
    }

    // -- Delete -----------------------------------------------------------

    /** Unsend for everyone (own messages only): leaves a tombstone. */
    public function deleteForEveryone(int $id): void
    {
        $message = $this->findOwnMessage($id);

        if ($message && ! $message->isDeletedForEveryone()) {
            $message->deleteForEveryone();
        }

        if ($this->editingId === $id) {
            $this->cancelEdit();
        }
    }

    /** Remove for me only (any message I can see): hidden just for this user. */
    public function deleteForMe(int $id): void
    {
        $message = $this->visibleMessageQuery()->find($id);

        if ($message) {
            MessageHide::firstOrCreate(['message_id' => $message->id, 'user_id' => Auth::id()]);
        }
    }

    // -- Notification sound ----------------------------------------------

    protected function maxInboundId(): int
    {
        return (int) Message::whereHas('conversation.users', fn ($q) => $q->where('users.id', Auth::id()))
            ->where('user_id', '!=', Auth::id())
            ->max('id');
    }

    /** Polled: ring the client if a new inbound message has arrived. */
    public function pollChat(): void
    {
        $max = $this->maxInboundId();

        if ($max > $this->lastSeenInboundId) {
            $this->lastSeenInboundId = $max;
            $this->dispatch('chat-ping');
        }
    }

    /**
     * Permanently remove chat files past their retention window. Runs at most
     * once an hour regardless of how many clients are polling, so cleanup keeps
     * happening even without a configured cron.
     */
    public function purgeExpiredAttachments(): void
    {
        if (! Cache::add('chat:purge-attachments:lock', now()->toIso8601String(), now()->addHour())) {
            return;
        }

        ChatAttachment::expired()->limit(500)->get()->each(function (ChatAttachment $attachment) {
            $message = $attachment->message;
            $attachment->delete(); // deleting hook removes the file from disk

            // Drop a now-empty message (file-only, no text, nothing else left).
            if ($message && trim((string) $message->body) === '' && $message->attachments()->count() === 0) {
                $message->delete();
            }
        });
    }

    public function startPm(int $userId): void
    {
        $other = User::find($userId);
        if (! $other || $other->id === Auth::id()) {
            return;
        }

        $conversation = Conversation::findOrCreatePm(Auth::user(), $other);
        $this->search = '';
        $this->openConversation($conversation->id);
    }

    public function createGroup(): void
    {
        $this->groupName = trim($this->groupName);
        $members = collect($this->groupMembers)->map(fn ($id) => (int) $id)->filter()->unique();

        if ($this->groupName === '' || $members->isEmpty()) {
            return;
        }

        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $this->groupName,
            'created_by' => Auth::id(),
        ]);

        $conversation->users()->attach($members->push(Auth::id())->unique()->all());

        $this->reset(['groupName', 'groupMembers', 'search']);
        $this->openConversation($conversation->id);
    }

    /** Reveal a burn-after-read token, then it is gone for everyone. */
    public function revealMessage(int $messageId): void
    {
        $message = Message::where('id', $messageId)
            ->whereHas('conversation.users', fn ($q) => $q->where('users.id', Auth::id()))
            ->first();

        if ($message && $message->is_token) {
            $this->revealed = $message->revealAndBurn();
        }
    }

    /** Send the confidential access token as a burn-after-read message. */
    public function sendToken(string $token): void
    {
        $token = trim($token);
        if ($token === '' || ! $this->activeId) {
            return;
        }

        $conversation = $this->userConversations()->firstWhere('id', $this->activeId);
        if (! $conversation) {
            return;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
            'body' => Crypt::encryptString($token),
            'is_token' => true,
            'expires_at' => now()->addMinutes(30),
        ]);

        $conversation->touch();
    }

    /**
     * External access-request: open (or start) a PM with the record owner and
     * post a request for its access token.
     */
    #[On('request-access-token')]
    public function requestAccessToken(int $recordId): void
    {
        $record = Record::find($recordId);
        if (! $record) {
            return;
        }

        // Prefer the record's creator (who holds the token) -- created_by is
        // stored as the creator's name -- then fall back to the owning office.
        $owner = ($record->created_by ? User::where('name', $record->created_by)->first() : null)
            ?? User::where('office', $record->owner)->orderBy('id')->first();

        if (! $owner || $owner->id === Auth::id()) {
            session()->flash('chat-flash', 'Could not find someone to request access from.');
            return;
        }

        $conversation = Conversation::findOrCreatePm(Auth::user(), $owner);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
            'body' => '🔑 Requesting the access token for confidential record ' . $record->reference . '.',
            'record_id' => $record->id,
        ]);

        $conversation->touch();
        $this->open = true;
        $this->openConversation($conversation->id);
    }

    #[On('chat-updated')]
    public function refresh(): void
    {
        // event hook -> re-render
    }

    public function getUnreadCountProperty(): int
    {
        $count = 0;

        foreach ($this->userConversations() as $c) {
            $lastRead = optional($c->pivot)->last_read_at;
            $last = $c->lastMessage;

            if ($last && $last->user_id !== Auth::id() && (! $lastRead || $last->created_at->gt($lastRead))) {
                $count++;
            }
        }

        return $count;
    }

    public function render()
    {
        $this->purgeExpiredAttachments();

        $conversations = $this->open ? $this->userConversations() : collect();

        $active = ($this->activeId && $this->open)
            ? $conversations->firstWhere('id', $this->activeId)
            : null;

        $messages = $active
            ? $active->messages()
                ->with(['user', 'attachments'])
                ->whereDoesntHave('hides', fn ($q) => $q->where('user_id', Auth::id()))
                ->get()
            : collect();

        // Directory for starting new chats (exclude self).
        $people = ($this->open && in_array($this->view, ['new-pm', 'new-group'], true))
            ? User::where('id', '!=', Auth::id())
                ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')->limit(50)->get()
            : collect();

        return view('livewire.chat-box', [
            'conversations' => $conversations,
            'active' => $active,
            'messages' => $messages,
            'people' => $people,
            'unreadCount' => $this->unreadCount,
        ]);
    }
}
