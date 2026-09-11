{{-- Chat: PM + group messaging, slide-over from the nav. Polls so new
     messages surface without a reload; faster while open. --}}
<div
    x-data="{
        open: @entangle('open'),
        muted: false,
        _ctx: null,
        init() { try { this.muted = localStorage.getItem('chat-muted') === '1' } catch (e) {} },
        toggleMute() { this.muted = ! this.muted; try { localStorage.setItem('chat-muted', this.muted ? '1' : '0') } catch (e) {} },
        ping() {
            if (this.muted) return;
            try {
                const AC = window.AudioContext || window.webkitAudioContext;
                if (! AC) return;
                const ctx = this._ctx || (this._ctx = new AC());
                if (ctx.state === 'suspended') { ctx.resume(); }
                const t = ctx.currentTime;
                const o = ctx.createOscillator();
                const g = ctx.createGain();
                o.connect(g); g.connect(ctx.destination);
                o.type = 'triangle';
                // Two-note 'ding-dong', loud and clear.
                o.frequency.setValueAtTime(988, t);          // B5
                o.frequency.setValueAtTime(1319, t + 0.13);   // E6
                g.gain.setValueAtTime(0.0001, t);
                g.gain.exponentialRampToValueAtTime(0.75, t + 0.02);
                g.gain.exponentialRampToValueAtTime(0.35, t + 0.13);
                g.gain.exponentialRampToValueAtTime(0.55, t + 0.15);
                g.gain.exponentialRampToValueAtTime(0.0001, t + 0.55);
                o.start(t); o.stop(t + 0.57);
            } catch (e) {}
        },
    }"
    @chat-ping.window="ping()"
    wire:poll.{{ $open ? '6s' : '15s' }}="pollChat"
>
    {{-- ============================================================= --}}
    {{-- TRIGGER (chat bubble + unread badge) --}}
    {{-- ============================================================= --}}
    <button
        type="button"
        wire:click="openChat"
        aria-label="{{ $unreadCount > 0 ? $unreadCount . ' unread messages' : 'Messages' }}"
        class="group relative inline-flex size-10 items-center justify-center rounded-full text-gray-500 dark:text-gray-400 transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        :class="open ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : ''"
    >
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute right-1 top-1 flex size-4 items-center justify-center">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex size-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
            </span>
        @endif
    </button>

    {{-- ============================================================= --}}
    {{-- SLIDE-OVER --}}
    {{-- ============================================================= --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-data="{ lb: { open: false, src: '', name: '', dl: '' } }"
            class="fixed inset-0 z-[60]"
            aria-modal="true"
            role="dialog"
        >
            {{-- Backdrop --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="$wire.closeChat()"
                class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"
            ></div>

            {{-- Panel --}}
            <div
                x-show="open"
                x-transition:enter="transform transition ease-out duration-250"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                @keydown.escape.window="$wire.closeChat()"
                class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white dark:!bg-gray-900 shadow-2xl ring-1 ring-black/5"
            >
                {{-- Header --}}
                <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 px-4 py-3">
                    @if ($view !== 'list')
                        <button type="button" wire:click="backToList" class="inline-flex size-8 items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Back">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        </button>
                    @endif

                    <h2 class="min-w-0 flex-1 truncate text-base font-semibold text-gray-800 dark:text-gray-100">
                        @if ($view === 'thread' && $active)
                            {{ $active->titleFor(auth()->user()) }}
                            @if ($active->isGroup())
                                <span class="ml-1 rounded-full bg-indigo-50 dark:bg-indigo-900/40 px-2 py-0.5 text-[10px] font-medium text-indigo-600 dark:text-indigo-300 align-middle">Group</span>
                            @endif
                        @elseif ($view === 'new-pm')
                            New message
                        @elseif ($view === 'new-group')
                            New group
                        @else
                            Messages
                        @endif
                    </h2>

                    @if ($view === 'list')
                        <button type="button" wire:click="$set('view', 'new-pm')" title="New message" class="inline-flex size-8 items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="New message">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM19.5 21h-15A2.25 2.25 0 012.25 18.75V6.75A2.25 2.25 0 014.5 4.5h3" /></svg>
                        </button>
                        <button type="button" wire:click="$set('view', 'new-group')" title="New group" class="inline-flex size-8 items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="New group">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        </button>
                    @endif

                    <button type="button" @click="toggleMute()" :title="muted ? 'Sound off — click to unmute' : 'Sound on — click to mute'" class="inline-flex size-8 items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Toggle chat sound">
                        <svg x-show="! muted" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" /></svg>
                        <svg x-show="muted" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" /></svg>
                    </button>

                    <button type="button" wire:click="closeChat" class="inline-flex size-8 items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Close">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if (session('chat-flash'))
                    <div class="border-b border-amber-200 bg-amber-50 dark:bg-amber-900/20 px-4 py-2 text-xs text-amber-800 dark:text-amber-300">{{ session('chat-flash') }}</div>
                @endif

                {{-- ===================== BODY ===================== --}}

                {{-- LIST --}}
                @if ($view === 'list')
                    <div class="flex-1 overflow-y-auto">
                        @forelse ($conversations as $c)
                            @php
                                $last = $c->lastMessage;
                                $lastRead = optional($c->pivot)->last_read_at;
                                $unread = $last && $last->user_id !== auth()->id() && (! $lastRead || $last->created_at->gt($lastRead));
                            @endphp
                            <button type="button" wire:click="openConversation({{ $c->id }})" wire:key="conv-{{ $c->id }}"
                                class="flex w-full items-center gap-3 border-b border-gray-50 dark:border-gray-800/70 px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $c->isGroup() ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-300' }} text-sm font-semibold">
                                    @if ($c->isGroup())
                                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                    @else
                                        {{ strtoupper(mb_substr($c->titleFor(auth()->user()), 0, 1)) }}
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $c->titleFor(auth()->user()) }}</span>
                                        @if ($last)
                                            <span class="shrink-0 text-[11px] text-gray-400 dark:text-gray-500">{{ $last->created_at->diffForHumans(null, true) }}</span>
                                        @endif
                                    </span>
                                    <span class="mt-0.5 flex items-center gap-2">
                                        <span class="truncate text-xs {{ $unread ? 'font-semibold text-gray-700 dark:text-gray-200' : 'text-gray-500 dark:text-gray-400' }}">
                                            @if ($last)
                                                @php
                                                    $mine = $last->user_id === auth()->id();
                                                    if ($last->isDeletedForEveryone()) {
                                                        $preview = $mine ? 'You unsent a message' : 'Unsent a message';
                                                    } elseif ($last->is_token) {
                                                        $preview = '🔑 Access token';
                                                    } elseif (trim($last->body) !== '') {
                                                        $preview = \Illuminate\Support\Str::limit($last->body, 38);
                                                    } elseif ($last->attachments->isNotEmpty()) {
                                                        $preview = '📎 ' . ($last->attachments->count() > 1 ? $last->attachments->count() . ' files' : 'Attachment');
                                                    } else {
                                                        $preview = '';
                                                    }
                                                    $prefix = ($mine && ! $last->isDeletedForEveryone()) ? 'You: ' : '';
                                                @endphp
                                                {{ $prefix . $preview }}
                                            @else
                                                No messages yet
                                            @endif
                                        </span>
                                        @if ($unread)
                                            <span class="ml-auto size-2 shrink-0 rounded-full bg-rose-500"></span>
                                        @endif
                                    </span>
                                </span>
                            </button>
                        @empty
                            <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                                <span class="flex size-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400">
                                    <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                                </span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">No conversations yet.</p>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="$set('view', 'new-pm')" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">New message</button>
                                    <button type="button" wire:click="$set('view', 'new-group')" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">New group</button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                @endif

                {{-- THREAD --}}
                @if ($view === 'thread' && $active)
                    <div class="flex flex-1 flex-col overflow-hidden">
                        <div class="flex-1 space-y-3 overflow-y-auto px-4 py-4" x-data x-init="$el.scrollTop = $el.scrollHeight" x-effect="$el.scrollTop = $el.scrollHeight">
                            @forelse ($messages as $m)
                                @php
                                    $mine = $m->user_id === auth()->id();
                                    $gone = $m->isDeletedForEveryone();
                                    $editing = $editingId === $m->id;
                                    $canEdit = $mine && ! $m->is_token && ! $gone && trim($m->body) !== '';
                                @endphp
                                <div wire:key="msg-{{ $m->id }}" class="group flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                    <div class="flex max-w-[85%] items-center gap-1 {{ $mine ? 'flex-row-reverse' : 'flex-row' }}">
                                        <div class="min-w-0">
                                            @if ($active->isGroup() && ! $mine && ! $gone)
                                                <p class="mb-0.5 px-1 text-[11px] font-medium text-gray-400 dark:text-gray-500">{{ $m->user->name ?? 'User' }}</p>
                                            @endif

                                            @if ($editing)
                                                {{-- Inline editor --}}
                                                <form wire:submit.prevent="saveEdit" class="flex w-64 max-w-full flex-col gap-1">
                                                    <textarea wire:model="editBody" rows="2"
                                                        x-data x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)"
                                                        x-on:keydown.enter.prevent="$wire.saveEdit()" x-on:keydown.escape.prevent="$wire.cancelEdit()"
                                                        class="w-full resize-none rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:!bg-gray-800 text-sm text-gray-800 dark:text-gray-100 focus:border-indigo-400 focus:ring-indigo-400"></textarea>
                                                    <div class="flex items-center gap-3 px-1 {{ $mine ? 'justify-end' : '' }}">
                                                        <button type="button" wire:click="cancelEdit" class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Cancel</button>
                                                        <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Save</button>
                                                    </div>
                                                </form>
                                            @elseif ($gone)
                                                {{-- Unsent tombstone --}}
                                                <div class="inline-flex items-center gap-1.5 rounded-2xl border border-gray-200 dark:border-gray-700 px-3.5 py-2 text-sm italic text-gray-400 dark:text-gray-500">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                    {{ $mine ? 'You unsent a message' : (($active->isGroup() ? (($m->user->name ?? 'Someone') . ' ') : '') . 'removed a message') }}
                                                </div>
                                            @elseif ($m->is_token)
                                                {{-- Burn-after-read access token --}}
                                                <div class="rounded-2xl border border-amber-300 dark:border-amber-600/60 bg-amber-50 dark:bg-amber-900/25 px-3 py-2.5">
                                                    <p class="flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H9v1.5H7.5v1.5H6v1.5H3.75a.75.75 0 01-.75-.75v-1.939a.75.75 0 01.22-.53l7.588-7.588c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                                                        Access token
                                                    </p>
                                                    @if ($m->expired())
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This token expired and is no longer available.</p>
                                                    @else
                                                        <p class="mt-1 text-[11px] text-amber-700/80 dark:text-amber-300/80">Shown once. It is destroyed the moment you reveal it.</p>
                                                        <button type="button" wire:click="revealMessage({{ $m->id }})" class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">
                                                            Reveal &amp; burn
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                @if (trim($m->body) !== '')
                                                    <div class="rounded-2xl px-3.5 py-2 text-sm {{ $mine ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-100' }}">
                                                        <p class="whitespace-pre-wrap break-words">{{ $m->body }}</p>
                                                    </div>
                                                @endif

                                                @if ($m->attachments->isNotEmpty())
                                                    <div class="mt-1 flex flex-col gap-1 {{ $mine ? 'items-end' : 'items-start' }}">
                                                        @foreach ($m->attachments as $att)
                                                            @if ($att->is_image)
                                                                <button type="button" wire:key="att-{{ $att->id }}"
                                                                    @click="lb = { open: true, src: '{{ route('chat-attachments.view', $att) }}', name: @js($att->original_name), dl: '{{ route('chat-attachments.download', $att) }}' }"
                                                                    class="block overflow-hidden rounded-xl ring-1 ring-black/5 transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                                                    <img src="{{ route('chat-attachments.view', $att) }}" alt="{{ $att->original_name }}" class="max-h-48 max-w-[16rem] object-cover" loading="lazy">
                                                                </button>
                                                            @else
                                                                <a href="{{ route('chat-attachments.download', $att) }}" wire:key="att-{{ $att->id }}"
                                                                   class="flex max-w-[16rem] items-center gap-2.5 rounded-xl border px-3 py-2 transition-colors {{ $mine ? 'border-indigo-300/60 bg-indigo-50 dark:border-indigo-500/40 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700/70' }}">
                                                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white dark:bg-gray-900 text-[10px] font-bold text-gray-500 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-700">{{ \Illuminate\Support\Str::limit($att->extension, 4, '') }}</span>
                                                                    <span class="min-w-0">
                                                                        <span class="block truncate text-xs font-medium text-gray-800 dark:text-gray-100">{{ $att->original_name }}</span>
                                                                        <span class="block text-[10px] text-gray-400 dark:text-gray-500">{{ $att->human_size }} · Download</span>
                                                                    </span>
                                                                </a>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif

                                            @unless ($editing)
                                                <p class="mt-0.5 px-1 text-[10px] text-gray-400 dark:text-gray-500 {{ $mine ? 'text-right' : '' }}">
                                                    {{ $m->created_at->timezone('Asia/Manila')->format('g:i A') }}@if ($m->isEdited() && ! $gone) <span class="italic">· edited</span>@endif
                                                </p>
                                            @endunless
                                        </div>

                                        {{-- Actions: a visible ⋯ button toggles inline action icons (no popover, so nothing clips) --}}
                                        @if (! $gone && ! $editing && ! ($m->is_token && ! $mine))
                                            <div class="flex shrink-0 items-center gap-0.5 self-center {{ $mine ? 'flex-row' : 'flex-row-reverse' }}"
                                                x-data="{ act: false }" @click.outside="act = false" @keydown.escape.window="act = false">

                                                {{-- Expanding action icons --}}
                                                <div x-show="act" x-cloak
                                                    x-transition:enter="transition ease-out duration-150"
                                                    x-transition:enter-start="opacity-0 scale-90"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    class="flex items-center gap-0.5">
                                                    @if ($canEdit)
                                                        <button type="button" wire:click="startEdit({{ $m->id }})" @click="act = false" title="Edit" aria-label="Edit message"
                                                            class="inline-flex size-7 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                                        </button>
                                                    @endif

                                                    <button type="button" wire:click="deleteForMe({{ $m->id }})" @click="act = false" title="Remove for you" aria-label="Remove for you"
                                                        class="inline-flex size-7 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88" /></svg>
                                                    </button>

                                                    @if ($mine)
                                                        <button type="button" wire:click="deleteForEveryone({{ $m->id }})" @click="act = false" wire:confirm="Unsend this message for everyone? This cannot be undone." title="Remove for everyone" aria-label="Remove for everyone"
                                                            class="inline-flex size-7 items-center justify-center rounded-full text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400">
                                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                        </button>
                                                    @endif
                                                </div>

                                                {{-- Always-visible trigger --}}
                                                <button type="button" @click="act = ! act" title="Message actions" aria-label="Message actions"
                                                    class="inline-flex size-7 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                                    :class="act ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-200' : ''">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">No messages yet. Say hello 👋</p>
                            @endforelse
                        </div>

                        {{-- Revealed token (once) --}}
                        @if ($revealed !== null)
                            <div class="border-t border-emerald-200 dark:border-emerald-700/60 bg-emerald-50 dark:bg-emerald-900/25 px-4 py-3"
                                 x-data="{ copied: false }">
                                <p class="text-[11px] font-medium text-emerald-700 dark:text-emerald-300">Access token (copy it now — it is gone):</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <code class="flex-1 select-all rounded-lg bg-white dark:!bg-gray-900 px-3 py-2 font-mono text-sm text-emerald-800 dark:text-emerald-200 ring-1 ring-emerald-200 dark:ring-emerald-700">{{ $revealed }}</code>
                                    <button type="button"
                                        @click="navigator.clipboard.writeText(@js($revealed)); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="shrink-0 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                        <span x-show="! copied">Copy</span>
                                        <span x-show="copied" x-cloak>Copied</span>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Composer --}}
                        <div class="border-t border-gray-100 dark:border-gray-800 p-3" x-data="{ tokenMode: false, token: '' }">
                            {{-- Token sender (owners reply to access requests) --}}
                            <div x-show="tokenMode" x-cloak class="mb-2 rounded-lg border border-amber-300 dark:border-amber-600/60 bg-amber-50 dark:bg-amber-900/20 p-2">
                                <p class="mb-1 text-[11px] font-medium text-amber-700 dark:text-amber-300">Send a one-time access token (burns after they read it)</p>
                                <div class="flex items-center gap-2">
                                    <input type="text" x-model="token" placeholder="Paste the record token…" class="flex-1 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-900 text-sm text-gray-800 dark:text-gray-100 focus:border-amber-400 focus:ring-amber-400">
                                    <button type="button"
                                        @click="if (token.trim()) { $wire.sendToken(token); token=''; tokenMode=false }"
                                        class="shrink-0 rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-600">Send token</button>
                                </div>
                            </div>

                            {{-- Queued attachments --}}
                            @if (! empty($files))
                                <div class="mb-2 flex flex-wrap gap-2">
                                    @foreach ($files as $i => $f)
                                        <span wire:key="queued-{{ $i }}" class="flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 py-1 pl-2.5 pr-1 text-xs text-gray-700 dark:text-gray-200">
                                            <svg class="size-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                            <span class="max-w-[9rem] truncate">{{ $f->getClientOriginalName() }}</span>
                                            <button type="button" wire:click="removeFile({{ $i }})" class="inline-flex size-5 items-center justify-center rounded-full text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-100" aria-label="Remove">
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <div wire:loading wire:target="files" class="mb-2 flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                Uploading…
                            </div>

                            @error('files') <p class="mb-2 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            @error('files.*') <p class="mb-2 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                            <form wire:submit="sendMessage" class="flex items-end gap-2">
                                <button type="button" @click="tokenMode = ! tokenMode" title="Send access token" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-amber-500" :class="tokenMode ? 'text-amber-500' : ''">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                </button>

                                <label title="Attach files" class="inline-flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-indigo-500">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                    <input type="file" wire:model="files" multiple class="hidden">
                                </label>

                                <textarea wire:model="body" rows="1" placeholder="Write a message…"
                                    x-data x-on:keydown.enter.prevent="$wire.sendMessage()"
                                    class="max-h-28 min-h-[2.5rem] flex-1 resize-none rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:!bg-gray-800 text-sm text-gray-800 dark:text-gray-100 focus:border-indigo-400 focus:ring-indigo-400"></textarea>
                                <button type="submit" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white transition-colors hover:bg-indigo-700 disabled:opacity-40" wire:loading.attr="disabled" wire:target="files,sendMessage">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                </button>
                            </form>

                            <p class="mt-1.5 flex items-center gap-1 px-1 text-[10px] text-gray-400 dark:text-gray-500">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Files are encrypted and permanently deleted after 15 days.
                            </p>
                        </div>
                    </div>
                @endif

                {{-- NEW PM --}}
                @if ($view === 'new-pm')
                    <div class="flex flex-1 flex-col overflow-hidden">
                        <div class="border-b border-gray-100 dark:border-gray-800 p-3">
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search people…" class="w-full rounded-lg border-gray-200 dark:border-gray-700 bg-gray-50 dark:!bg-gray-800 text-sm text-gray-800 dark:text-gray-100 focus:border-indigo-400 focus:ring-indigo-400">
                        </div>
                        <div class="flex-1 overflow-y-auto">
                            @forelse ($people as $person)
                                <button type="button" wire:click="startPm({{ $person->id }})" wire:key="pm-{{ $person->id }}"
                                    class="flex w-full items-center gap-3 border-b border-gray-50 dark:border-gray-800/70 px-4 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-sm font-semibold text-gray-500 dark:text-gray-300">{{ strtoupper(mb_substr($person->name, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ $person->name }}</span>
                                        <span class="block truncate text-xs text-gray-400 dark:text-gray-500">{{ $person->office }}</span>
                                    </span>
                                </button>
                            @empty
                                <p class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500">No people found.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- NEW GROUP --}}
                @if ($view === 'new-group')
                    <div class="flex flex-1 flex-col overflow-hidden">
                        <div class="space-y-2 border-b border-gray-100 dark:border-gray-800 p-3">
                            <input type="text" wire:model="groupName" placeholder="Group name…" class="w-full rounded-lg border-gray-200 dark:border-gray-700 bg-gray-50 dark:!bg-gray-800 text-sm text-gray-800 dark:text-gray-100 focus:border-indigo-400 focus:ring-indigo-400">
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search members to add…" class="w-full rounded-lg border-gray-200 dark:border-gray-700 bg-gray-50 dark:!bg-gray-800 text-sm text-gray-800 dark:text-gray-100 focus:border-indigo-400 focus:ring-indigo-400">
                        </div>
                        <div class="flex-1 overflow-y-auto">
                            @forelse ($people as $person)
                                <label wire:key="grp-{{ $person->id }}" class="flex cursor-pointer items-center gap-3 border-b border-gray-50 dark:border-gray-800/70 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <input type="checkbox" wire:model="groupMembers" value="{{ $person->id }}" class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-sm font-semibold text-gray-500 dark:text-gray-300">{{ strtoupper(mb_substr($person->name, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ $person->name }}</span>
                                        <span class="block truncate text-xs text-gray-400 dark:text-gray-500">{{ $person->office }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500">No people found.</p>
                            @endforelse
                        </div>
                        <div class="border-t border-gray-100 dark:border-gray-800 p-3">
                            <button type="button" wire:click="createGroup"
                                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-40"
                                @disabled(empty($groupMembers) || trim($groupName) === '')>
                                Create group ({{ count($groupMembers) }})
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Image lightbox: full-screen preview like Messenger / Viber --}}
            <div
                x-show="lb.open"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @keydown.escape.window="lb.open = false"
                class="absolute inset-0 z-[70] flex flex-col bg-black/90"
            >
                <div class="flex items-center justify-between gap-3 px-4 py-3 text-white/90">
                    <span class="min-w-0 truncate text-sm font-medium" x-text="lb.name"></span>
                    <div class="flex items-center gap-1">
                        <a :href="lb.dl" class="inline-flex size-9 items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white" title="Download">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                        </a>
                        <button type="button" @click="lb.open = false" class="inline-flex size-9 items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white" title="Close">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
                <div class="flex flex-1 items-center justify-center overflow-auto p-4" @click.self="lb.open = false">
                    <img :src="lb.src" :alt="lb.name" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl">
                </div>
            </div>
        </div>
    </template>
</div>
