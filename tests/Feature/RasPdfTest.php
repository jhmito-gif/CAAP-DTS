<?php

use App\Models\Record;
use App\Models\Transaction;
use App\Support\RasFormFooter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rasPdfWithMovements(int $movements, array $receivedReferences = []): \Barryvdh\DomPDF\PDF
{
    $record = Record::create([
        'reference' => 'ITD-2026-0900',
        'subject' => 'Sick leave application on 02-04 September 2026',
        'created_by' => 'ITD Person',
        'origin' => 'ITD',
        'owner' => 'ITD',
    ]);

    $offices = ['ITD', 'CPO', 'PD', 'ODG'];

    for ($i = 0; $i < $movements; $i++) {
        Transaction::create([
            'record_id' => $record->id,
            'internal_reference' => $record->reference,
            'remarks' => 'Movement ' . ($i + 1),
            'status' => 'For Action',
            'office' => $offices[$i % 4],
            'destination' => $offices[($i + 1) % 4],
            'forwarded_by' => $offices[$i % 4] . ' Person',
            'received_reference' => $receivedReferences[$i] ?? null,
        ]);
    }

    $record->load(['transactions' => fn ($query) => $query->orderBy('created_at')->orderBy('id')]);

    $pdf = Pdf::loadView('pdfs.record', ['record' => $record, 'masked' => false])
        ->setPaper('a4', 'portrait');

    RasFormFooter::apply($pdf);

    return $pdf;
}

it('keeps the routing slip on one page with its footer and stacked reference IDs', function () {
    $pdf = rasPdfWithMovements(3, ['CPO-2026-1555', 'PD-07867', 'ODG-26-2769']);

    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1)
        ->and($pdf->output())->toStartWith('%PDF');
});

it('continues on a second page when a record has more movements than the form prints', function () {
    $pdf = rasPdfWithMovements(12);

    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(2)
        ->and($pdf->output())->toStartWith('%PDF');
});
