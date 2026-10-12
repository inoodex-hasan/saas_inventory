<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PdfDocumentController;
use App\Livewire\ProductManager;
use App\Livewire\PurchaseOrderManager;
use App\Livewire\ReportManager;
use App\Livewire\SalesOrderManager;
use App\Livewire\SuperAdminDashboard;
use App\Livewire\TenantSettingsManager;
use App\Livewire\WarehouseManager;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isSuperAdmin() ? redirect()->route('admin.dashboard') : redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::get('/suspended', function () {
    return view('errors.tenant-suspended');
})->name('tenant.suspended');

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
    Route::get('/reports', ReportManager::class)->name('reports.index');
    Route::get('/settings/company', TenantSettingsManager::class)->name('settings.company');

    // PDF Documents Generation
    Route::get('/sales/{order}/invoice', [PdfDocumentController::class, 'salesInvoice'])->name('sales.invoice');
    Route::get('/sales/{order}/packing-slip', [PdfDocumentController::class, 'salesPackingSlip'])->name('sales.packing-slip');
    Route::get('/purchases/{order}/pdf', [PdfDocumentController::class, 'purchaseOrder'])->name('purchases.pdf');
});

// Platform Super-Admin Control Plane
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'super_admin',
])->group(function () {
    Route::get('/admin', SuperAdminDashboard::class)->name('admin.dashboard');
    Route::get('/admin/exit-tenant', function () {
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            $user->forceFill(['current_team_id' => null])->save();
            return redirect()->route('admin.dashboard')->with('success', 'Exited tenant workspace. Back to Platform Super-Admin mode.');
        }
        return redirect()->route('dashboard');
    })->name('admin.exit-tenant');
});
