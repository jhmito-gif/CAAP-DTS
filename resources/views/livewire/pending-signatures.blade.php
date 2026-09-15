<div>
    @if ($signatureRequests->isNotEmpty())
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                    </svg>

                    <div>
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                            Your signature is requested
                        </p>
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            {{ $signatureRequests->count() }} {{ \Illuminate\Support\Str::plural('document', $signatureRequests->count()) }} on this record
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($signatureRequests as $signatureRequest)
                        <a
                            href="{{ route('esign.sign', $signatureRequest) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-700"
                        >
                            Sign {{ \Illuminate\Support\Str::limit($signatureRequest->attachment?->original_name ?? 'document', 32) }}
                        </a>
                    @endforeach
                </div>

            </div>
        </div>
    @endif
</div>
