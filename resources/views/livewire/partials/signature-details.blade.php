@php
    $signature = $details['signature'];
    $label = 'text-xs text-gray-500 dark:text-gray-400';
    $value = 'font-semibold text-gray-900 dark:text-gray-100';
@endphp

<dl class="grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
    <div>
        <dt class="{{ $label }}">Signed by</dt>
        <dd class="{{ $value }}">{{ $signature->signer_name }}</dd>
    </div>

    <div>
        <dt class="{{ $label }}">Office</dt>
        <dd class="{{ $value }}">{{ $signature->signer_office ?: '—' }}</dd>
    </div>

    <div>
        <dt class="{{ $label }}">Signed at</dt>
        <dd class="{{ $value }}">{{ $signature->signed_at->copy()->setTimezone('Asia/Manila')->format('j M Y g:i A') }}</dd>
    </div>

    <div>
        <dt class="{{ $label }}">Verification code</dt>
        <dd class="{{ $value }} font-mono">{{ $signature->verification_code }}</dd>
    </div>

    @if ($details['canSeeDocument'])
        <div>
            <dt class="{{ $label }}">Document</dt>
            <dd class="{{ $value }} break-words">{{ $signature->attachment?->displayNameFor(auth()->user()) }}</dd>
        </div>

        <div>
            <dt class="{{ $label }}">Record</dt>
            <dd class="{{ $value }}">
                <a href="{{ route('show-transactions', $signature->attachment->record_id) }}" class="text-sky-600 hover:underline dark:text-sky-400">
                    {{ $signature->attachment->record->reference }}
                </a>
            </dd>
        </div>
    @endif

    <div class="sm:col-span-2">
        <dt class="{{ $label }}">Fingerprint (SHA-256) of the signed version</dt>
        <dd class="break-all font-mono text-xs text-gray-700 dark:text-gray-300">{{ $signature->signed_sha256 }}</dd>
    </div>
</dl>

<p class="mt-3 text-xs text-gray-600 dark:text-gray-400">
    @if (! $details['isLatest'])
        Later signatures were added after this one.
    @elseif ($details['complete'])
        This is the final, fully signed version.
    @else
        This is the latest signed version; other signatures are still pending.
    @endif
</p>
