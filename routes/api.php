<?php

use App\Http\Controllers\MemberAreaController;
use App\Http\Controllers\MemberAreaReceiptDownloadController;
use Illuminate\Support\Facades\Route;

Route::prefix('member-area')
    ->middleware('wp.member-area')
    ->group(function (): void {
        Route::get('/me', [MemberAreaController::class, 'show'])
            ->name('member-area.me');
    });

Route::get('/member-area/receipts/{receipt}/download', MemberAreaReceiptDownloadController::class)
    ->middleware('signed')
    ->name('member-area.receipts.download');
