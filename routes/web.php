<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReceiptController;
use App\Livewire\CategoryManager;
use App\Livewire\Dashboard;
use App\Livewire\InventoryManager;
use App\Livewire\OrderHistory;
use App\Livewire\Pos\CashierScreen;
use App\Livewire\ProductManager;
use App\Livewire\ProfileManager;
use App\Livewire\SalesReport;
use App\Livewire\SettingsManager;
use App\Livewire\ShiftManager;
use App\Livewire\UserManager;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/pos', CashierScreen::class)->name('pos');
    Route::get('/shifts', ShiftManager::class)->name('shifts');
    Route::get('/profile', ProfileManager::class)->name('profile');
    Route::get('/orders', OrderHistory::class)->name('orders');
    Route::get('/orders/{order}/receipt', [ReceiptController::class, 'show'])->name('orders.receipt');
    Route::get('/orders/{order}/receipt-data', [ReceiptController::class, 'data'])->name('orders.receipt.data');


    Route::middleware('role:OWNER,MANAGER')->group(function () {
        Route::get('/products', ProductManager::class)->name('products');
        Route::get('/categories', CategoryManager::class)->name('categories');
        Route::get('/inventory', InventoryManager::class)->name('inventory');
        Route::get('/reports/sales', SalesReport::class)->name('reports.sales');
    });

    Route::middleware('role:OWNER')->group(function () {
        Route::get('/settings', SettingsManager::class)->name('settings');
        Route::get('/users', UserManager::class)->name('users');
    });
});

Route::fallback(fn () => response()->view('errors.404', status: 404));
