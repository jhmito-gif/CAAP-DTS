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
    | Document library  (module: documents)
    |--------------------------------------------------------------------------
    | Record attachments are not part of this: they are served by the
    | attachment routes above and keep working when the library is off.
    */
    Route::middleware('module:documents')->group(function () {
        Route::get(
            '/documents',
            [DocumentController::class, 'index']
        )->name('documents.index');

        // Uploads arrive a piece at a time, so a large scan survives a stall and
        // is not at the mercy of PHP's single-request upload limits.
        Route::post(
            '/documents/upload/chunk',
            [\App\Http\Controllers\ChunkedUploadController::class, 'chunk']
        )->name('documents.upload.chunk');

        Route::post(
            '/documents/upload/finish',
            [\App\Http\Controllers\ChunkedUploadController::class, 'finish']
        )->name('documents.upload.finish');

        // Everything known about one file, for the panel beside the viewer.
        Route::get(
            '/documents/info',
            [\App\Http\Controllers\DocumentInfoController::class, 'show']
        )->name('documents.info');

        // A file's own page: an address that can be kept, shared and returned to.
        // The floating window is a convenience on top of this, not instead of it.
        Route::get(
            '/files/{key}',
            [\App\Http\Controllers\DocumentInfoController::class, 'page']
        )->where('key', '(attachment|document|shortcut)-[0-9]+')->name('files.show');

        Route::get(
            '/documents/{document}/view',
            [DocumentController::class, 'view']
        )->name('documents.view');

        Route::get(
            '/documents/{document}/download',
            [DocumentController::class, 'download']
        )->name('documents.download');
    });

    /*
    |--------------------------------------------------------------------------
    | E-signatures  (module: esign)
    |--------------------------------------------------------------------------
    | A link in an old notification answers "turned off" rather than breaking.
    */
    Route::middleware('module:esign')->group(function () {
        Route::get(
            '/sign/{signatureRequest}',
            [\App\Http\Controllers\SignatureController::class, 'sign']
        )->name('esign.sign');

        Route::get(
            '/signatures',
            [\App\Http\Controllers\SignatureController::class, 'queue']
        )->name('esign.queue');

        Route::get(
            '/verify-signature',
            [\App\Http\Controllers\SignatureController::class, 'verify']
        )->name('esign.verify');
    });

    /*
    |--------------------------------------------------------------------------
    | Chat attachments  (module: chat)
    |--------------------------------------------------------------------------
    */
    Route::middleware('module:chat')->group(function () {
        Route::get(
            '/chat-attachments/{attachment}/view',
            [ChatAttachmentController::class, 'view']
        )->name('chat-attachments.view');

        Route::get(
            '/chat-attachments/{attachment}/download',
            [ChatAttachmentController::class, 'download']
        )->name('chat-attachments.download');
    });

});