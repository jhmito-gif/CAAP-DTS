{{-- Polls so a tag made elsewhere surfaces without a page reload. --}}
<div
    class="relative"
    wire:poll.15s
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>

    {{-- ============================================================= --}}
    {{-- BELL --}}
    {{-- ============================================================= --}}
    <button
        type="button"
        @click="open = ! open"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="{{ $unreadCount > 0 ? $unreadCount . ' unread notifications' : 'Notifications' }}"
        class="group relative inline-flex size-10 items-center justify-center rounded-full text-gray-500 dark:text-gray-400 transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        :class="open ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : ''"
    >

        <svg
            class="size-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.8"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
            />
        </svg>


        @if ($unreadCount > 0)
            <span class="absolute right-1 top-1 flex size-4 items-center justify-center">

                <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-400 opacity-60"></span>

                <span class="relative inline-flex size-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white ring-2 ring-white">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>

            </span>
        @endif

    </button>



    {{-- ============================================================= --}}
    {{-- DROPDOWN --}}
    {{-- ============================================================= --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        style="display: none;"
        class="absolute end-0 z-50 mt-2 w-[380px] origin-top-right overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-2xl ring-1 ring-black/5"
        role="region"
        aria-label="Notifications"
    >

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-800 px-4 py-3">

            <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">
                Notifications
            </h2>


            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="rounded-lg px-2 py-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 transition hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-700 dark:hover:text-indigo-300"
                >
                    Mark all read
                </button>
            @endif

        </div>


        {{-- List --}}
        <div class="max-h-[420px] divide-y divide-gray-100 dark:divide-gray-800 overflow-y-auto overscroll-contain">

            @forelse ($notifications as $notification)

                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                    $recordId = $data['record_id'] ?? null;
                @endphp

                <div class="flex gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-800 {{ $isUnread ? 'bg-indigo-50/40 dark:bg-indigo-900/30' : '' }}">

                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-full
                            {{ ($data['is_urgent'] ?? false) ? 'bg-red-100 text-red-600 dark:text-red-400' : 'bg-violet-100 text-violet-600 dark:text-violet-400' }}"
                    >
                        <svg
                            class="size-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
                            />
                        </svg>
                    </div>


                    <div class="min-w-0 flex-1">

                        <div class="flex items-start justify-between gap-2">

                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $data['title'] ?? 'Notification' }}
                            </p>

                            @if ($isUnread)
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-indigo-500"></span>
                            @endif

                        </div>


                        <p class="mt-0.5 break-words text-xs text-gray-600 dark:text-gray-300">
                            {{ $data['message'] ?? '' }}
                        </p>


                        @if (filled($data['subject'] ?? null))
                            <p class="mt-1 line-clamp-2 break-words text-xs text-gray-400 dark:text-gray-500">
                                {{ $data['subject'] }}
                            </p>
                        @endif


                        <div class="mt-1.5 flex flex-wrap items-center gap-2">

                            <span class="text-[11px] {{ $isUnread ? 'font-semibold text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>


                            @if ($recordId)
                                <a
                                    href="{{ route('show-transactions', $recordId) }}"
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    class="inline-flex items-center gap-1 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:text-gray-300 transition hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-700 dark:hover:text-indigo-300"
                                >
                                    Open record

                                    <svg
                                        class="size-3"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                                        />
                                    </svg>
                                </a>
                            @endif


                            @if ($isUnread)
                                <button
                                    type="button"
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    class="rounded-md px-1.5 py-0.5 text-[11px] font-semibold text-gray-400 dark:text-gray-500 transition hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    Mark read
                                </button>
                            @endif

                        </div>

                    </div>

                </div>

            @empty

                <div class="px-4 py-10 text-center">

                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500">
                        <svg
                            class="size-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                            />
                        </svg>
                    </div>

                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        No notifications yet
                    </p>

                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        You will be notified here when someone tags you on a document.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>
