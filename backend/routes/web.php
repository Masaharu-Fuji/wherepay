<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RoomController::class, 'create'])->name('rooms.create');

Route::prefix('rooms')->name('rooms.')->group(function (): void {
    Route::post('/', [RoomController::class, 'store'])->name('store');

    Route::prefix('{room}')->group(function (): void {
        Route::get('/', [RoomController::class, 'show'])->name('show');
        Route::get('/csv', [RoomController::class, 'exportCsv'])->name('csv');

        Route::post('/members', [RoomController::class, 'addMember'])->name('members.store');

        Route::post('/items', [RoomController::class, 'addItem'])->name('items.store');
        Route::delete('/items/{item}', [RoomController::class, 'deleteItem'])->name('items.delete');

        Route::prefix('settlement')->name('settlement.')->group(function (): void {
            Route::match(['get', 'post'], '/', [SettlementController::class, 'show'])
                ->name('show');

            Route::get('/csv', [SettlementController::class, 'exportSettlementCsv'])
                ->name('csv');

            Route::post('/confirm', [SettlementController::class, 'confirm'])
                ->name('confirm');
        });
    });
});
