<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\WpApplicationIngestController;


Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/receipts/{receipt}/download', [ReceiptController::class, 'download'])
        ->middleware('signed')
        ->name('receipts.download');
});

Route::post('/integrations/wp/applications', WpApplicationIngestController::class)
    ->name('integrations.wp.applications.ingest');
