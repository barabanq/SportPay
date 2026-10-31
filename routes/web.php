<?php

use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'index'])->name('home');
Route::post('/switch-user', [MarketplaceController::class, 'switchUser'])->name('user.switch');
Route::post('/lots', [MarketplaceController::class, 'storeLot'])->name('lots.store');
Route::post('/lots/{lot}/purchase', [MarketplaceController::class, 'purchase'])->name('lots.purchase');
Route::post('/orders/{order}/confirm', [MarketplaceController::class, 'confirmOrder'])->name('orders.confirm');
Route::post('/orders/{order}/cancel', [MarketplaceController::class, 'cancelOrder'])->name('orders.cancel');
Route::post('/wallet/deposit', [MarketplaceController::class, 'deposit'])->name('wallet.deposit');
Route::post('/wallet/withdraw', [MarketplaceController::class, 'withdraw'])->name('wallet.withdraw');