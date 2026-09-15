<x-action-section>
    <x-slot name="title">
        {{ __('E-Signature') }}
    </x-slot>

    <x-slot name="description">
        {{ __('The signature applied when you sign documents. It is stored encrypted and only placed on a document after you confirm with your signing PIN (and your authenticator code once per session).') }}
    </x-slot>

    <x-slot name="content">
        @unless ($twoFactorEnabled)
            <div class="mb-4 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
                {{ __('Turn on two-factor authentication above before you can sign documents.') }}
            </div>
        @endunless

        @if ($signature)
            <div class="mb-5">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Current signature') }}</p>

                <div class="mt-2 flex flex-wrap items-center gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white px-4 py-3">
                        <img src="{{ $signature->dataUri() }}" alt="{{ __('Your saved signature') }}" class="h-16 w-auto">
                    </div>

                    <button
                        type="button"
                        wire:click="remove"
                        wire:confirm="{{ __('Remove your saved signature?') }}"
                        class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400"
                    >
                        {{ __('Remove') }}
                    </button>
                </div>
            </div>
        @endif

        <div x-data="{ tab: 'draw' }">

            <div class="mb-3 inline-flex rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 text-sm">
                <button type="button" @click="tab = 'draw'" :class="tab === 'draw' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'text-gray-600 dark:text-gray-300'" class="rounded-md px-3 py-1 font-medium">
                    {{ $signature ? __('Draw a new one') : __('Draw') }}
                </button>
                <button type="button" @click="tab = 'upload'" :class="tab === 'upload' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'text-gray-600 dark:text-gray-300'" class="rounded-md px-3 py-1 font-medium">
                    {{ __('Upload image') }}
                </button>
            </div>

            {{-- Draw --}}
            <div
                x-show="tab === 'draw'"
                x-data="{
                    empty: true,
                    drawing: false,
                    ctx() { return this.$refs.canvas.getContext('2d') },
                    point(e) {
                        let rect = this.$refs.canvas.getBoundingClientRect()
                        return {
                            x: (e.clientX - rect.left) * (this.$refs.canvas.width / rect.width),
                            y: (e.clientY - rect.top) * (this.$refs.canvas.height / rect.height),
                        }
                    },
                    start(e) {
                        let c = this.ctx()
                        let p = this.point(e)
                        c.lineWidth = 3
                        c.lineCap = 'round'
                        c.lineJoin = 'round'
                        c.strokeStyle = '#111827'
                        c.beginPath()
                        c.moveTo(p.x, p.y)
                        this.drawing = true
                        this.empty = false
                        this.$refs.canvas.setPointerCapture(e.pointerId)
                    },
                    move(e) {
                        if (! this.drawing) return
                        let p = this.point(e)
                        this.ctx().lineTo(p.x, p.y)
                        this.ctx().stroke()
                    },
                    end() { this.drawing = false },
                    clear() {
                        this.ctx().clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height)
                        this.empty = true
                    },
                    save() {
                        if (this.empty) return
                        this.$wire.saveDrawn(this.$refs.canvas.toDataURL('image/png')).then(() => this.clear())
                    },
                }"
            >
                <canvas
                    x-ref="canvas"
                    width="600"
                    height="200"
                    @pointerdown="start($event)"
                    @pointermove="move($event)"
                    @pointerup="end()"
                    @pointercancel="end()"
                    class="block w-full max-w-xl cursor-crosshair touch-none rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-white"
                ></canvas>

                <div class="mt-3 flex items-center gap-3">
                    <x-button type="button" @click="save()" x-bind:disabled="empty">
                        {{ __('Save signature') }}
                    </x-button>

                    <button type="button" @click="clear()" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300">
                        {{ __('Clear') }}
                    </button>
                </div>
            </div>

            {{-- Upload --}}
            <div x-show="tab === 'upload'" x-cloak>
                <input
                    type="file"
                    wire:model="upload"
                    accept="image/png,image/jpeg"
                    class="block w-full max-w-xl text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-md file:border-0 file:bg-gray-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white"
                >

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('PNG or JPG up to 2 MB. Sign in dark ink on plain white paper; the background is removed automatically.') }}
                </p>

                <div class="mt-3">
                    <x-button type="button" wire:click="saveUpload" wire:loading.attr="disabled" wire:target="upload,saveUpload">
                        {{ __('Save signature') }}
                    </x-button>
                </div>
            </div>
        </div>

        <x-input-error for="signature" class="mt-3" />
        <x-input-error for="upload" class="mt-3" />

        {{-- Signing PIN --}}
        <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Signing PIN') }}</p>

                @if ($signingPin)
                    <span class="rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">
                        {{ __('Set') }} {{ $signingPin->updated_at?->diffForHumans() }}
                    </span>
                @else
                    <span class="rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:text-amber-300">
                        {{ __('Not set') }}
                    </span>
                @endif
            </div>

            <p class="mt-1 max-w-xl text-xs text-gray-500 dark:text-gray-400">
                {{ __(':min–:max digits, entered each time you sign instead of your password. Your authenticator code is only asked once per session. Avoid repeated or sequential numbers.', ['min' => \App\Models\SigningPin::MIN_LENGTH, 'max' => \App\Models\SigningPin::MAX_LENGTH]) }}
            </p>

            <form wire:submit="savePin" class="mt-3 grid max-w-xl gap-3 sm:grid-cols-3">
                <div>
                    <x-label for="signingCurrentPassword" value="{{ __('Current password') }}" />
                    <x-input id="signingCurrentPassword" type="password" class="mt-1 block w-full" wire:model="currentPassword" autocomplete="current-password" />
                    <x-input-error for="currentPassword" class="mt-1" />
                </div>

                <div>
                    <x-label for="signingNewPin" value="{{ $signingPin ? __('New PIN') : __('PIN') }}" />
                    <x-input id="signingNewPin" type="password" inputmode="numeric" maxlength="{{ \App\Models\SigningPin::MAX_LENGTH }}" class="mt-1 block w-full tracking-widest" wire:model="newPin" autocomplete="new-password" />
                    <x-input-error for="newPin" class="mt-1" />
                </div>

                <div>
                    <x-label for="signingNewPinConfirmation" value="{{ __('Confirm PIN') }}" />
                    <x-input id="signingNewPinConfirmation" type="password" inputmode="numeric" maxlength="{{ \App\Models\SigningPin::MAX_LENGTH }}" class="mt-1 block w-full tracking-widest" wire:model="newPin_confirmation" autocomplete="new-password" />
                </div>

                <div class="sm:col-span-3">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="savePin">
                        {{ $signingPin ? __('Change PIN') : __('Set PIN') }}
                    </x-button>
                </div>
            </form>
        </div>
    </x-slot>
</x-action-section>
