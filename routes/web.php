<?php

declare(strict_types=1);

use App\Http\Controllers\ChatController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'index'])->name('home');
Route::post('/switch-user', [MarketplaceController::class, 'switchUser'])->name('user.switch');

Route::get('/users/{profileUser}', [ProfileController::class, 'show'])->name('users.show');

Route::post('/lots', [LotController::class, 'store'])->name('lots.store');
Route::get('/lots/{lot}/edit', [LotController::class, 'edit'])->name('lots.edit');
Route::put('/lots/{lot}', [LotController::class, 'update'])->name('lots.update');
Route::delete('/lots/{lot}', [LotController::class, 'destroy'])->name('lots.destroy');
Route::post('/lots/{lot}/purchase', [OrderController::class, 'purchase'])->name('lots.purchase');

Route::get('/messages', [ChatController::class, 'index'])->name('chat.index');

Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/orders/{order}/messages', [ChatController::class, 'store'])->name('orders.messages.store');
Route::post('/orders/{order}/dispute', [OrderController::class, 'dispute'])->name('orders.dispute');

Route::post('/wallet/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');
Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');