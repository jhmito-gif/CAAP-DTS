<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\IncomingController;
use App\Http\Controllers\OutgoingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PdfController;


Route::get('/', function () {
    return view('welcome');
});


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Incoming
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/incoming',
        [IncomingController::class, 'Index']
    )->name('incoming-record');


    /*
    |--------------------------------------------------------------------------
    | Outgoing
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/outgoing',
        [OutgoingController::class, 'Index']
    )->name('outgoing-record');


    /*
    |--------------------------------------------------------------------------
    | Transactions
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/transaction/{id}',
        [TransactionController::class, 'Index']
    )->name('show-transactions');


    Route::get(
        '/outgoing-transaction/{id}',
        [TransactionController::class, 'Outgoing']
    )->name('outgoing-transactions');


    /*
    |--------------------------------------------------------------------------
    | Urgent
    |--------------------------------------------------------------------------
    */
    Route::patch(
        '/records/{id}/urgent',
        [TransactionController::class, 'toggleUrgent']
    )->name('records.toggle-urgent');

    Route::patch(
        '/records/{id}/confidential',
        [TransactionController::class, 'toggleConfidential']
    )->name('records.toggle-confidential');


    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/records-pdf/{id}/pdf',
        [PdfController::class, 'RASPDF']
    )->name('records-pdf');


    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/attachments/{attachment}/download',
        [AttachmentController::class, 'download']
    )->name('attachments.download');

    Route::get(
        '/attachments/{attachment}/view',
        [AttachmentController::class, 'view']
    )->name('attachments.view');

    /*
    |--------------------------------------------------------------------------
    | Document library
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/documents',
        [DocumentController::class, 'index']
    )->name('documents.index');

    Route::get(
        '/documents/{document}/view',
        [DocumentController::class, 'view']
    )->name('documents.view');

    Route::get(
        '/documents/{document}/download',
        [DocumentController::class, 'download']
    )->name('documents.download');

    /*
    |--------------------------------------------------------------------------
    | E-signatures
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/sign/{signatureRequest}',
        [\App\Http\Controllers\SignatureController::class, 'sign']
    )->name('esign.sign');

    Route::get(
        '/verify-signature',
        [\App\Http\Controllers\SignatureController::class, 'verify']
    )->name('esign.verify');

    /*
    |--------------------------------------------------------------------------
    | Chat attachments
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/chat-attachments/{attachment}/view',
        [ChatAttachmentController::class, 'view']
    )->name('chat-attachments.view');

    Route::get(
        '/chat-attachments/{attachment}/download',
        [ChatAttachmentController::class, 'download']
    )->name('chat-attachments.download');

});