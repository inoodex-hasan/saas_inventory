<?php

use App\Http\Controllers\DashboardController;
use App\Livewire\ProductManager;
use App\Livewire\PurchaseOrderManager;
use App\Livewire\SalesOrderManager;
use App\Livewire\WarehouseManager;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/warehouses', WarehouseManager::class)->name('warehouses.index');
    Route::get('/products', ProductManager::class)->name('products.index');
    Route::get('/purchases', PurchaseOrderManager::class)->name('purchases.index');
    Route::get('/sales', SalesOrderManager::class)->name('sales.index');
});
