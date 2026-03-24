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

        Route::prefix('members')->name('members.')->group(function (): void {
            Route::post('/', [RoomController::class, 'addMember'])->name('store');
            Route::get('/', [RoomController::class, 'members'])->name('index');
            Route::patch('/{member}', [RoomController::class, 'updateMember'])->name('update');
        });

        Route::prefix('items')->name('items.')->group(function (): void {
            Route::post('/', [RoomController::class, 'addItem'])->name('store');
            Route::delete('/{item}', [RoomController::class, 'deleteItem'])->name('delete');
        });

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
