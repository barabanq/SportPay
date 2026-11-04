<?php

declare(strict_types=1);

use App\Http\Controllers\ArbitrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GlobalChatController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

// Аутентификация (Вход / Регистрация / Выход)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Главная витрина, переключение аккаунта и языка (RU / EN)
Route::get('/', [MarketplaceController::class, 'index'])->name('home');
Route::post('/switch-user', [MarketplaceController::class, 'switchUser'])->name('user.switch');
Route::post('/locale', [MarketplaceController::class, 'switchLocale'])->name('locale.switch');

// Профиль пользователя, загрузка аватара + JSON-эндпоинт для попапа продавца
Route::get('/users/{profileUser}/modal', [ProfileController::class, 'modal'])->name('users.modal');
Route::post('/users/{profileUser}/avatar', [ProfileController::class, 'updateAvatar'])->name('users.avatar.update');
Route::get('/users/{profileUser}', [ProfileController::class, 'show'])->name('users.show');

// Лоты
Route::get('/lots/{lot}', [LotController::class, 'show'])->name('lots.show');
Route::post('/lots', [LotController::class, 'store'])->name('lots.store');
Route::get('/lots/{lot}/edit', [LotController::class, 'edit'])->name('lots.edit');
Route::put('/lots/{lot}', [LotController::class, 'update'])->name('lots.update');
Route::delete('/lots/{lot}', [LotController::class, 'destroy'])->name('lots.destroy');
Route::post('/lots/{lot}/purchase', [OrderController::class, 'purchase'])->name('lots.purchase');

// Чаты и сделки
Route::get('/messages', [ChatController::class, 'index'])->name('chat.index');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/orders/{order}/messages', [ChatController::class, 'store'])->name('orders.messages.store');
Route::get('/orders/{order}/messages', [ChatController::class, 'messages'])->name('orders.messages.index');
Route::post('/orders/{order}/dispute', [OrderController::class, 'dispute'])->name('orders.dispute');

// Финансы и Арбитраж
Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
Route::post('/wallet/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');
Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');

Route::get('/arbitration', [ArbitrationController::class, 'index'])->name('arbitration.index');
Route::post('/orders/{order}/resolve', [ArbitrationController::class, 'resolve'])->name('orders.resolve');

// Общий чат
Route::get('/global-chat', [GlobalChatController::class, 'index'])->name('chat.global');
Route::get('/global-chat/messages', [GlobalChatController::class, 'messages'])->name('chat.global.messages');
Route::post('/global-chat/messages', [GlobalChatController::class, 'store'])->name('chat.global.store');

// Раздел «Помощь»
Route::prefix('support')->name('support.')->group(function (): void {
    Route::get('/rules', [SupportController::class, 'rules'])->name('rules');
    Route::get('/help', [SupportController::class, 'helpCenter'])->name('help');
    Route::get('/tickets/create', [SupportController::class, 'createTicket'])->name('tickets.create');
    Route::post('/tickets', [SupportController::class, 'storeTicket'])->name('tickets.store');
    Route::get('/tickets', [SupportController::class, 'myTickets'])->name('tickets.index');
    Route::post('/tickets/{ticket}/reply', [SupportController::class, 'replyTicket'])->name('tickets.reply');
});
