@php
    $input = 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 shadow-sm transition placeholder:text-gray-400 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200';
    $error = 'mt-1.5 text-xs font-medium text-red-600 dark:text-red-400';
@endphp

<div>

    {{-- ============================================================= --}}
    {{-- EDIT RECORD MODAL --}}
    {{-- ============================================================= --}}
    <div
        wire:ignore.self
        x-data="{ open: false }"
        x-on:open-edit-record-modal.window="open = true"
        x-on:close-edit-record-modal.window="open = false"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editRecordModalLabel"
    >

        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition.opacity
            @click="open = false"
            class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
        ></div>

        {{-- Panel --}}
        <div
            x-show="open"
            x-transition
            @click.stop
            class="relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl"
        >

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-6 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <h5 id="editRecordModalLabel" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Edit Record
                        </h5>

                        @if ($record)
                            <p class="truncate text-xs text-gray-400 dark:text-gray-500">
                                {{ $record->reference }} &middot; {{ $isIncoming ? 'Incoming from ' . $record->origin : 'Outgoing' }}
                                &middot; the reference number can't be changed
                            </p>
                        @endif
                    </div>
                </div>

                <button
                    type="button"
                    @click="open = false"
                    class="rounded-lg p-1.5 text-gray-400 dark:text-gray-500 transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300"
                    aria-label="Close"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>


            {{-- Body --}}
            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">

                @if ($record)

                    {{-- Subject --}}
                    <div>
                        <label for="editSubject" class="{{ $label }}">
                            Subject <span class="text-red-500 dark:text-red-400">*</span>
                        </label>

                        <textarea wire:model="subject" id="editSubject" rows="3" class="{{ $input }} resize-none"></textarea>

                        @error('subject')
                            <p class="{{ $error }}">{{ $message }}</p>
                        @enderror
                    </div>


                    {{-- Origin reference (incoming records only) --}}
                    @if ($isIncoming)
                        <div>
                            <label for="editOriginReference" class="{{ $label }}">
                                Origin Reference ID
                            </label>

                            <input
                                type="text"
                                wire:model="originReference"
                                id="editOriginReference"
                                placeholder="The sending office's reference number"
                                class="{{ $input }}"
                            >

                            @error('originReference')
                                <p class="{{ $error }}">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif


                    {{-- First routing entry --}}
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">

                        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                            First routing entry
                        </p>

                        @if (! $firstTransaction)

                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                This record has no routing entry.
                            </p>

                        @elseif (! $canEditRouting)

                            <p class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <svg class="size-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                Already received by {{ $firstTransaction->destination }}{{ $firstTransaction->recieved_by ? ' (' . $firstTransaction->recieved_by . ')' : '' }}, so it can no longer be changed.
                            </p>

                            <dl class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-3">
                                <div><dt class="text-gray-400 dark:text-gray-500">Destination</dt><dd class="font-medium text-gray-700 dark:text-gray-200">{{ $firstTransaction->destination }}</dd></div>
                                <div><dt class="text-gray-400 dark:text-gray-500">Status</dt><dd class="font-medium text-gray-700 dark:text-gray-200">{{ $firstTransaction->status }}</dd></div>
                                <div><dt class="text-gray-400 dark:text-gray-500">Remarks</dt><dd class="break-words font-medium text-gray-700 dark:text-gray-200">{{ $firstTransaction->remarks }}</dd></div>
                            </dl>

                        @else

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                                @unless ($isIncoming)
                                    <div>
                                        <label for="editOffice" class="{{ $label }}">
                                            Destination Office <span class="text-red-500 dark:text-red-400">*</span>
                                        </label>

                                        <select wire:model="office" id="editOffice" class="{{ $input }}">
                                            <option value="">-- Select Office --</option>
                                            @foreach ($officeOptions as $name)
                                                <option value="{{ $name }}">{{ $name }}</option>
                                            @endforeach
                                        </select>

                                        @error('office')
                                            <p class="{{ $error }}">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endunless

                                <div class="{{ $isIncoming ? 'sm:col-span-2' : '' }}">
                                    <label for="editStatus" class="{{ $label }}">
                                        Status <span class="text-red-500 dark:text-red-400">*</span>
                                    </label>

                                    <select wire:model="status" id="editStatus" class="{{ $input }}">
                                        <option value="">-- Select Status --</option>
                                        @foreach ($statusOptions as $name)
                                            <option value="{{ $name }}">{{ $name }}</option>
                                        @endforeach
                                    </select>

                                    @error('status')
                                        <p class="{{ $error }}">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>

                            <div class="mt-4">
                                <x-remarks-field model="remarks" id="editRemarks" label="Remarks" :required="true" :rows="3" accent="blue" />
                            </div>

                        @endif

                    </div>


                    {{-- Attachments --}}
                    <div>
                        <label class="{{ $label }}">
                            Attachments
                        </label>

                        @if ($record->attachments->isNotEmpty())
                            <ul class="mb-2 space-y-1.5">
                                {{-- Files stay with the record: new ones can be added, none removed. --}}
                                @foreach ($record->attachments as $file)
                                    <li
                                        wire:key="edit-attachment-{{ $file->id }}"
                                        class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800 px-3 py-2"
                                    >
                                        <div class="flex min-w-0 items-center gap-2.5">
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-gray-100 dark:bg-gray-900 text-[10px] font-bold text-gray-500 dark:text-gray-400">
                                                {{ $file->extension }}
                                            </span>

                                            <div class="min-w-0">
                                                <p class="truncate text-xs font-semibold text-gray-800 dark:text-gray-100">
                                                    {{ $file->displayNameFor(auth()->user()) }}
                                                </p>
                                                <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                                    {{ $file->human_size }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <label
                            for="editAttachments"
                            class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 px-4 py-4 text-center transition hover:border-sky-400 hover:bg-sky-50/40 dark:hover:border-sky-600 dark:hover:bg-sky-900/20"
                        >
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                Add files
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                PDF, Word, Excel or images &middot; up to 10&nbsp;MB each
                            </span>

                            <input
                                id="editAttachments"
                                type="file"
                                wire:model="newAttachments"
                                multiple
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                class="hidden"
                            >
                        </label>

                        <div wire:loading wire:target="newAttachments" class="mt-2 text-xs font-medium text-sky-600 dark:text-sky-400">
                            Uploading&hellip;
                        </div>

                        @if (! empty($newAttachments))
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($newAttachments as $index => $file)
                                    <li wire:key="new-file-{{ $file->getFilename() }}" class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 rounded-lg border px-3 py-2 {{ $errors->has("uploadTypes.$index") ? 'border-red-300 dark:border-red-700' : 'border-sky-200 dark:border-sky-800' }} bg-sky-50/50 dark:bg-sky-900/20">
                                        <p class="min-w-0 flex-1 truncate text-xs font-semibold text-gray-800 dark:text-gray-100">
                                            {{ $file->getClientOriginalName() }}
                                            <span class="font-normal text-sky-600 dark:text-sky-400">&middot; new</span>
                                        </p>

                                        {{-- What kind of document this file is, for filtering on the Documents page --}}
                                        <select
                                            wire:model="uploadTypes.{{ $index }}"
                                            aria-label="Document type for {{ $file->getClientOriginalName() }}"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 py-1 pl-2 pr-7 text-xs text-gray-700 dark:text-gray-200 focus:border-sky-500 focus:ring-sky-500 sm:w-40"
                                        >
                                            <option value="">Document type…</option>
                                            @foreach ($documentTypes as $typeId => $typeName)
                                                <option value="{{ $typeId }}">{{ $typeName }}</option>
                                            @endforeach
                                        </select>

                                        <button
                                            type="button"
                                            wire:click="removeNewAttachment({{ $index }})"
                                            class="shrink-0 rounded-md p-1 text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                            aria-label="Remove file"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>

                                        @error("uploadTypes.$index")
                                            <p class="w-full text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @error('newAttachments.*')
                            <p class="{{ $error }}">{{ $message }}</p>
                        @enderror
                    </div>

                @endif

            </div>


            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900 px-6 py-4">

                <button
                    type="button"
                    @click="open = false"
                    class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 shadow-sm transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-800 dark:hover:text-gray-100"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save,newAttachments"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="save">Save changes</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>

            </div>

        </div>
    </div>


    {{-- ============================================================= --}}
    {{-- RESET ACCESS TOKEN MODAL (confidential records) --}}
    {{-- ============================================================= --}}
    <div
        wire:ignore.self
        x-data="{ open: false }"
        x-on:open-reset-token-modal.window="open = true"
        x-on:close-reset-token-modal.window="open = false"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="resetTokenModalLabel"
    >

        <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

        <form
            x-show="open"
            x-transition
            @click.stop
            wire:submit.prevent="resetToken"
            class="relative flex w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl"
        >

            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-6 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H9v1.5H7.5v1.5H6a1.5 1.5 0 0 1-1.5-1.5v-1.629c0-.398.158-.78.44-1.062l5.616-5.615a6 6 0 1 1 8.909-4.494Z" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <h5 id="resetTokenModalLabel" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $tokenRecord?->requiresToken() === false ? 'Set access token' : 'Reset access token' }}
                        </h5>

                        @if ($tokenRecord)
                            <p class="truncate text-xs text-gray-400 dark:text-gray-500">{{ $tokenRecord->reference }} &middot; confidential</p>
                        @endif
                    </div>
                </div>

                <button type="button" @click="open = false" class="rounded-lg p-1.5 text-gray-400 dark:text-gray-500 transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4 px-6 py-5">

                <p class="rounded-lg border border-rose-200 dark:border-rose-800 bg-rose-50/60 dark:bg-rose-900/20 px-3 py-2 text-xs text-rose-800 dark:text-rose-200">
                    The old token stops working right away. Cleared viewers will need the new one, so share it only with them. The reset is kept in the audit log.
                </p>

                <div>
                    <label for="newAccessToken" class="{{ $label }}">New access token <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input id="newAccessToken" type="password" wire:model="newToken" autocomplete="new-password" placeholder="At least 4 characters" class="{{ $input }}">
                    @error('newToken')
                        <p class="{{ $error }}">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="newAccessTokenConfirmation" class="{{ $label }}">Type it again <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input id="newAccessTokenConfirmation" type="password" wire:model="newToken_confirmation" autocomplete="new-password" class="{{ $input }}">
                </div>

                <div>
                    <label for="resetTokenAccountPassword" class="{{ $label }}">Your account password <span class="text-red-500 dark:text-red-400">*</span></label>
                    <input id="resetTokenAccountPassword" type="password" wire:model="accountPassword" autocomplete="current-password" class="{{ $input }}">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The one you sign in with, to confirm it is you.</p>
                    @error('accountPassword')
                        <p class="{{ $error }}">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900 px-6 py-4">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 shadow-sm transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-800 dark:hover:text-gray-100">
                    Cancel
                </button>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="resetToken"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="resetToken">{{ $tokenRecord?->requiresToken() === false ? 'Set token' : 'Reset token' }}</span>
                    <span wire:loading wire:target="resetToken">Saving...</span>
                </button>
            </div>

        </form>
    </div>

</div>
