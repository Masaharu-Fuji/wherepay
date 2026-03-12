<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RoomController::class, 'create'])->name('rooms.create');

Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');

Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');

Route::post('/rooms/{room}/members', [RoomController::class, 'addMember'])->name('rooms.members.store');

Route::post('/rooms/{room}/items', [RoomController::class, 'addItem'])->name('rooms.items.store');

Route::match(['get', 'post'], '/rooms/{room}/settlement', [SettlementController::class, 'show'])
    ->name('rooms.settlement.show');

Route::post('/rooms/{room}/settlement/confirm', [SettlementController::class, 'confirm'])
    ->name('rooms.settlement.confirm');
