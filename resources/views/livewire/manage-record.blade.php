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
                                @foreach ($record->attachments as $file)
                                    @php
                                        $removing = in_array($file->id, $removeAttachmentIds, true);
                                    @endphp

                                    <li
                                        wire:key="edit-attachment-{{ $file->id }}"
                                        class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 {{ $removing ? 'border-red-200 dark:border-red-800 bg-red-50/60 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:!bg-gray-800' }}"
                                    >
                                        <div class="flex min-w-0 items-center gap-2.5">
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-gray-100 dark:bg-gray-900 text-[10px] font-bold text-gray-500 dark:text-gray-400">
                                                {{ $file->extension }}
                                            </span>

                                            <div class="min-w-0">
                                                <p class="truncate text-xs font-semibold {{ $removing ? 'text-red-600 line-through dark:text-red-400' : 'text-gray-800 dark:text-gray-100' }}">
                                                    {{ $file->displayNameFor(auth()->user()) }}
                                                </p>
                                                <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                                    {{ $removing ? 'Will be removed when you save' : $file->human_size }}
                                                </p>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="toggleRemoveAttachment({{ $file->id }})"
                                            class="shrink-0 rounded-md px-2 py-1 text-[11px] font-semibold transition {{ $removing ? 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' : 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30' }}"
                                        >
                                            {{ $removing ? 'Undo' : 'Remove' }}
                                        </button>
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
                                    <li class="flex items-center justify-between gap-3 rounded-lg border border-sky-200 dark:border-sky-800 bg-sky-50/50 dark:bg-sky-900/20 px-3 py-2">
                                        <p class="min-w-0 truncate text-xs font-semibold text-gray-800 dark:text-gray-100">
                                            {{ $file->getClientOriginalName() }}
                                            <span class="font-normal text-sky-600 dark:text-sky-400">&middot; new</span>
                                        </p>

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
    {{-- DELETE RECORD MODAL --}}
    {{-- ============================================================= --}}
    <div
        wire:ignore.self
        x-data="{ open: false }"
        x-on:open-delete-record-modal.window="open = true"
        x-on:close-delete-record-modal.window="open = false"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="deleteRecordModalLabel"
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
            class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white dark:!bg-gray-800 shadow-xl"
        >

            <div class="flex items-start gap-4 px-6 pb-4 pt-6">

                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>

                <div class="min-w-0">
                    <h5 id="deleteRecordModalLabel" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Delete {{ $record?->reference ?? 'record' }}?
                    </h5>

                    @if ($record)
                        <p class="mt-1 truncate text-sm text-gray-600 dark:text-gray-300" title="{{ $record->subject }}">
                            {{ $record->subject }}
                        </p>

                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                            This permanently removes the record, its
                            {{ $record->transactions_count }} {{ \Illuminate\Support\Str::plural('routing entry', $record->transactions_count) }}
                            and {{ $record->attachments->count() }} {{ \Illuminate\Support\Str::plural('file', $record->attachments->count()) }}
                            for every office. The reference number will not be reused.
                        </p>
                    @endif
                </div>

            </div>

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
                    wire:click="delete"
                    wire:loading.attr="disabled"
                    wire:target="delete"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="delete">Delete record</span>
                    <span wire:loading wire:target="delete">Deleting...</span>
                </button>

            </div>

        </div>
    </div>

</div>
