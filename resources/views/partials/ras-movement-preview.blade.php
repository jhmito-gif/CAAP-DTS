@php
    $previewDate = now()->format('j M Y g:i A');
    $previewFrom = filled($fromOffice ?? null) ? $fromOffice : 'FROM';
    $previewTo = filled($toOffice ?? null) ? $toOffice : 'TO';
    $previewStatus = filled($status ?? null) ? $status : 'Selected status';
    $previewRemarks = filled($remarks ?? null)
        ? $remarks
        : 'Remarks will appear here on the printed RAS.';
@endphp

<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3">
    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
        RAS Preview
    </p>

    <div class="overflow-hidden rounded border border-gray-300 dark:border-gray-700 bg-white dark:!bg-gray-800">
        <table class="w-full border-collapse text-[11px] text-gray-700 dark:text-gray-200">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-800 text-[10px] uppercase tracking-wide text-gray-600 dark:text-gray-300">
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-1.5 text-center font-bold">
                        Date/Time
                    </th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-1.5 text-center font-bold">
                        From
                    </th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-1.5 text-center font-bold">
                        To
                    </th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-1.5 text-left font-bold">
                        Remarks / Instructions / Action Requested
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="w-[18%] border border-gray-300 dark:border-gray-700 px-2 py-2 text-center align-top">
                        {{ $previewDate }}
                    </td>
                    <td class="w-[18%] border border-gray-300 dark:border-gray-700 px-2 py-2 text-center font-semibold align-top">
                        {{ $previewFrom }}
                    </td>
                    <td class="w-[18%] border border-gray-300 dark:border-gray-700 px-2 py-2 text-center font-semibold align-top">
                        {{ $previewTo }}
                    </td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 align-top">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-gray-900 dark:text-gray-100">
                            {{ $previewStatus }}
                        </p>
                        <p class="min-h-10 whitespace-pre-line font-semibold">
                            {{ $previewRemarks }}
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
