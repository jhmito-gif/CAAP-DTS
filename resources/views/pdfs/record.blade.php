<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Routing Action Slip</title>
    <style>
        @page {
            margin: 22px 28px;
        }

        body {
            color: #000;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.25;
            margin: 0;
        }

        .banner {
            display: block;
            margin-bottom: 8px;
            width: 100%;
        }

        .ras {
            border-collapse: collapse;
            width: 100%;
        }

        .ras th,
        .ras td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }

        /*
         * dompdf ignores <colgroup> percentages once colspans overlap, so the
         * six form columns are pinned by a zero-height ruler row instead.
         */
        .ruler td {
            border: 0;
            font-size: 0;
            height: 0;
            line-height: 0;
            padding: 0;
        }

        .title {
            font-size: 13px;
            font-weight: bold;
            padding: 6px !important;
            text-align: center;
        }

        .label {
            font-weight: bold;
        }

        .value {
            font-weight: bold;
        }

        .head-cell {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .stack-cell {
            height: 34px;
        }

        .stack-value {
            font-weight: bold;
            padding-top: 6px;
            text-align: center;
        }

        .subject-cell {
            height: 96px;
        }

        .subject {
            font-size: 11px;
            font-weight: bold;
            padding-top: 10px;
            text-align: left;
        }

        .movement-heading {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .movement-subheading {
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .date-cell {
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .office-cell {
            font-size: 11px;
            font-weight: bold;
            height: 34px;
            text-align: center;
            vertical-align: middle;
        }

        .official-cell {
            font-size: 8px;
            height: 34px;
            text-align: center;
            vertical-align: middle;
        }

        .remarks-cell {
            height: 68px;
        }

        .muted {
            color: #333;
            font-size: 7px;
        }

        .status-text {
            font-size: 9px;
            font-weight: bold;
        }

        .remarks {
            font-size: 9px;
            font-weight: bold;
            padding-top: 2px;
            white-space: pre-line;
        }

        .action-grid {
            border-collapse: collapse;
            width: 100%;
        }

        .action-grid td {
            border: 0;
            padding: 0;
            vertical-align: top;
            width: 50%;
        }

        .action-item {
            font-size: 7.5px;
            font-weight: bold;
            line-height: 1.5;
        }

        .checkbox {
            border: 1px solid #000;
            display: inline-block;
            height: 7px;
            margin-right: 4px;
            width: 9px;
        }

        .checkbox.checked {
            background: #000;
        }
    </style>
</head>
<body>
    @php
        /*
        |----------------------------------------------------------------------
        | CAAP-ODG-CCS-001 r2 -- ROUTING ACTION SLIP
        |----------------------------------------------------------------------
        | The printed form is fixed; only the fill logic lives here.
        |
        | GRID
        |   Six columns -- 12% 16% 16% 16% 12% 28% -- reproduce every rule on the
        |   form: the header splits at 28/72, the subject splits at 60, and the
        |   movement grid splits at 12/28/44.
        |
        | 1. HEADER BLOCK
        |    Originating Office   <- record.origin
        |    Reference Number     <- record.origin_reference, else record.reference
        |    Subject              <- record.subject
        |    Date of Document     <- record.created_at
        |    Date / Time Received <- earliest transaction carrying a date_recieved
        |
        | 2. MOVEMENT GRID -- $formRows blocks, each block is two sub-rows.
        |    DATE/TIME <- transaction.created_at        (spans both sub-rows)
        |    FROM      <- office      / forwarded_by    (sub-row 1 / sub-row 2)
        |    TO        <- destination / recieved_by     (sub-row 1 / sub-row 2)
        |    REMARKS   <- transaction.remarks           (spans both sub-rows)
        |    Unused blocks print empty so the slip can be continued by hand.
        |
        | 3. ACTION CHECKLIST
        |    Printed ONCE, inside the FIRST movement block only -- the source form
        |    carries no checklist on rows 2..n. The ticked box is resolved from the
        |    first transaction's status; later movements show their status as plain
        |    text, so the checklist is never repeated down the slip.
        */

        // -- Form geometry -----------------------------------------------------
        // The blank slip prints 8 movement blocks; grow only if a record has more.
        $formRows = 8;

        // The ten pre-printed actions in the order the form lays them out:
        // indexes 0-4 fill the left column, 5-9 the right column.
        $rasActions = [
            'Approval / Signature',
            'Comments / Recommendation',
            'Request Appropriate Action',
            'Reply Directly to Writer',
            'Rewrite / Redraft',
            'Information / Notation',
            'Endorsement',
            'See Me / Call me',
            'File',
            'Remarks',
        ];

        // Office wording that is not the literal label printed on the form.
        $statusAliases = [
            'for approval' => 0, 'for signature' => 0, 'approved' => 0, 'sign' => 0,
            'for comment' => 1, 'for comments' => 1, 'for recommendation' => 1, 'comment' => 1,
            'for appropriate action' => 2, 'for action' => 2, 'appropriate action' => 2, 'action' => 2,
            'for reply' => 3, 'reply directly' => 3, 'reply' => 3,
            'for revision' => 4, 'for rewrite' => 4, 'revise' => 4, 'revision' => 4, 'draft' => 4,
            'for information' => 5, 'for notation' => 5, 'information' => 5, 'info' => 5, 'fyi' => 5,
            'for endorsement' => 6, 'endorsed' => 6, 'endorse' => 6,
            'see me' => 7, 'call me' => 7,
            'for filing' => 8, 'filed' => 8, 'filing' => 8, 'closed' => 8,
            'others' => 9, 'other' => 9,
        ];

        // Strip punctuation and casing so "For Appropriate Action" matches.
        $normalize = function (?string $value): string {
            $value = strtolower(trim((string) $value));
            $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

            return trim(preg_replace('/\s+/', ' ', (string) $value));
        };

        // Status -> index of the box to tick, or null when nothing on the form fits.
        $resolveActionIndex = function (?string $status) use ($rasActions, $statusAliases, $normalize): ?int {
            $needle = $normalize($status);

            if ($needle === '') {
                return null;
            }

            // 1. The status is the printed label verbatim.
            foreach ($rasActions as $index => $action) {
                if ($normalize($action) === $needle) {
                    return $index;
                }
            }

            // 2. The status is one half of a slashed label ("Endorsement", "See Me").
            foreach ($rasActions as $index => $action) {
                foreach (explode('/', $action) as $part) {
                    if ($normalize($part) === $needle) {
                        return $index;
                    }
                }
            }

            // 3. Known office wording.
            if (array_key_exists($needle, $statusAliases)) {
                return $statusAliases[$needle];
            }

            // 4. Last resort: one string contains the other.
            foreach ($rasActions as $index => $action) {
                $haystack = $normalize($action);

                if (str_contains($haystack, $needle) || str_contains($needle, $haystack)) {
                    return $index;
                }
            }

            return null;
        };

        // -- Header data -------------------------------------------------------
        $transactions = $record->transactions;
        $dateOfDocument = optional($record->created_at)->format('j F Y');
        $receivedAt = optional($transactions->firstWhere('date_recieved', '!=', null))->date_recieved;
        $referenceNumber = $record->origin_reference ?: $record->reference;
        $internalReference = $record->origin_reference ? $record->reference : null;

        // -- Movement grid: pad the transactions out to the printed block count --
        $blocks = array_pad($transactions->all(), max($formRows, $transactions->count()), null);

        // -- Checklist: resolved once, for the first movement only --------------
        $headStatus = optional($transactions->first())->status;
        $checkedActionIndex = $resolveActionIndex($headStatus);
        $unlistedHeadStatus = $checkedActionIndex === null ? $headStatus : null;
    @endphp

    <img src="{{ public_path('img/caap-banner.png') }}" class="banner" alt="CAAP Banner">

    <table class="ras">
        <tr class="ruler">
            <td style="width: 12%"></td>
            <td style="width: 16%"></td>
            <td style="width: 16%"></td>
            <td style="width: 16%"></td>
            <td style="width: 12%"></td>
            <td style="width: 28%"></td>
        </tr>

        <tr>
            <td colspan="6" class="title">ROUTING ACTION SLIP</td>
        </tr>

        <tr>
            <td colspan="2" class="head-cell">Originating Office</td>
            <td colspan="3" class="head-cell">{{ $record->origin ?? '' }}</td>
            <td class="head-cell" style="text-align: left">
                <span class="label">Reference Number:</span><br>
                <span class="value">{{ $referenceNumber ?? '' }}</span>
                @if ($internalReference)
                    <br><span class="muted">Internal Tracking Number: {{ $internalReference }}</span>
                @endif
            </td>
        </tr>

        <tr>
            <td colspan="4" rowspan="2" class="subject-cell">
                <span class="label">Subject</span>
                <div class="subject">{{ $record->subject ?? '' }}</div>
            </td>
            <td colspan="2" class="stack-cell">
                <span class="label">Date of Document:</span>
                <div class="stack-value">{{ $dateOfDocument ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="stack-cell">
                <span class="label">Date / Time Received:</span>
                <div class="stack-value">
                    {{ $receivedAt ? \Carbon\Carbon::parse($receivedAt)->format('j M Y g:i A') : '' }}
                </div>
            </td>
        </tr>

        <tr>
            <th rowspan="2" class="movement-heading">DATE/TIME</th>
            <th class="movement-heading">FROM</th>
            <th class="movement-heading">TO</th>
            <th colspan="3" rowspan="2" class="movement-heading">
                REMARKS / INSTRUCTIONS / ACTION REQUESTED
            </th>
        </tr>
        <tr>
            <th class="movement-subheading">Name and Position of Official</th>
            <th class="movement-subheading">Name and Position of Official</th>
        </tr>

        @foreach ($blocks as $index => $transaction)
            <tr>
                <td rowspan="2" class="date-cell">
                    @if ($transaction)
                        {{ optional($transaction->created_at)->format('j M Y') }}<br>
                        {{ optional($transaction->created_at)->format('g:i A') }}
                    @endif
                </td>
                <td class="office-cell">{{ $transaction->office ?? '' }}</td>
                <td class="office-cell">{{ $transaction->destination ?? '' }}</td>
                <td colspan="3" rowspan="2" class="remarks-cell">
                    @if ($index === 0)
                        {{-- Action checklist: top block only, never repeated below. --}}
                        <table class="action-grid">
                            <tr>
                                <td>
                                    @foreach (array_slice($rasActions, 0, 5, true) as $action => $label)
                                        <div class="action-item">
                                            <span class="checkbox {{ $action === $checkedActionIndex ? 'checked' : '' }}"></span>{{ $label }}
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach (array_slice($rasActions, 5, 5, true) as $action => $label)
                                        <div class="action-item">
                                            <span class="checkbox {{ $action === $checkedActionIndex ? 'checked' : '' }}"></span>{{ $label }}
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        </table>

                        @if ($unlistedHeadStatus)
                            <div class="status-text">{{ $unlistedHeadStatus }}</div>
                        @endif
                    @elseif ($transaction && filled($transaction->status))
                        {{-- Later movements carry their status as plain text. --}}
                        <div class="status-text">{{ $transaction->status }}</div>
                    @endif

                    @if ($transaction && filled($transaction->remarks))
                        <div class="remarks">{{ $transaction->remarks }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="official-cell">{{ $transaction->forwarded_by ?? '' }}</td>
                <td class="official-cell">
                    {{ $transaction->recieved_by ?? '' }}
                    @if ($transaction && $transaction->date_recieved)
                        <div class="muted">
                            Received {{ \Carbon\Carbon::parse($transaction->date_recieved)->format('j M Y g:i A') }}
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</body>
</html>
