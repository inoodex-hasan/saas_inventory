<?php

use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminPlanController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\CustomerDueController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryChallanController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PdfDocumentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReturnController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\TaDaClaimController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\ServiceTicketController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Controllers\TenantSubscriptionController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorDueController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarrantyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isSuperAdmin() ? redirect()->route('admin.dashboard') : redirect()->route('dashboard');
    }
    
    $plans = collect();
    if (\Illuminate\Support\Facades\Schema::hasTable('plans')) {
        $plans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get();
    }
    
    return view('welcome', compact('plans'));
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
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    Route::post('/warehouses/{warehouse}/set-default', [WarehouseController::class, 'setDefault'])->name('warehouses.set-default');
    Route::get('/warehouses/{warehouse}/inspect', [WarehouseController::class, 'inspect'])->name('warehouses.inspect');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/{product}/inspect', [ProductController::class, 'inspectStock'])->name('products.inspect');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::post('/products/categories', [ProductController::class, 'storeCategory'])->name('products.categories.store');
    Route::put('/products/categories/{category}', [ProductController::class, 'updateCategory'])->name('products.categories.update');
    Route::delete('/products/categories/{category}', [ProductController::class, 'destroyCategory'])->name('products.categories.destroy');
    Route::post('/products/brands', [ProductController::class, 'storeBrand'])->name('products.brands.store');
    Route::put('/products/brands/{brand}', [ProductController::class, 'updateBrand'])->name('products.brands.update');
    Route::delete('/products/brands/{brand}', [ProductController::class, 'destroyBrand'])->name('products.brands.destroy');
    // Vendors & Procurement Partners
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
    Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
    Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
    Route::post('/vendors/{vendor}/toggle-active', [VendorController::class, 'toggleActive'])->name('vendors.toggle-active');
    Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');

    // Purchase Orders & Inbound Stock
    Route::get('/purchases', [PurchaseOrderController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseOrderController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseOrderController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{order}/edit', [PurchaseOrderController::class, 'edit'])->name('purchases.edit');
    Route::put('/purchases/{order}', [PurchaseOrderController::class, 'update'])->name('purchases.update');
    Route::post('/purchases/{order}/mark-ordered', [PurchaseOrderController::class, 'markOrdered'])->name('purchases.mark-ordered');
    Route::post('/purchases/{order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchases.receive');
    Route::post('/purchases/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchases.cancel');
    Route::post('/purchases/{order}/pay', [PurchaseOrderController::class, 'pay'])->name('purchases.pay');

    // Vendor Dues & Payables
    Route::get('/vendor-dues', [VendorDueController::class, 'index'])->name('vendor-dues.index');
    Route::post('/vendor-dues/pay', [VendorDueController::class, 'pay'])->name('vendor-dues.pay');

    // Outbound Sales & POS
    Route::get('/sales', [SalesOrderController::class, 'index'])->name('sales.index');
    Route::get('/sales/create', [SalesOrderController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SalesOrderController::class, 'store'])->name('sales.store');
    Route::post('/sales/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('sales.confirm');
    Route::post('/sales/{order}/dispatch', [SalesOrderController::class, 'dispatchOrder'])->name('sales.dispatch');
    Route::post('/sales/{order}/complete', [SalesOrderController::class, 'complete'])->name('sales.complete');
    Route::post('/sales/{order}/cancel', [SalesOrderController::class, 'cancel'])->name('sales.cancel');
    Route::post('/sales/{order}/pay', [SalesOrderController::class, 'pay'])->name('sales.pay');

    // Quotations & Estimates
    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
    Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
    Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
    Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])->name('quotations.edit');
    Route::put('/quotations/{quotation}', [QuotationController::class, 'update'])->name('quotations.update');
    Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');
    Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy');

    // Delivery Challans & Dispatch Logistics
    Route::get('/challans', [DeliveryChallanController::class, 'index'])->name('challans.index');
    Route::get('/challans/create', [DeliveryChallanController::class, 'create'])->name('challans.create');
    Route::post('/challans', [DeliveryChallanController::class, 'store'])->name('challans.store');
    Route::get('/challans/{challan}/edit', [DeliveryChallanController::class, 'edit'])->name('challans.edit');
    Route::put('/challans/{challan}', [DeliveryChallanController::class, 'update'])->name('challans.update');
    Route::post('/challans/{challan}/deliver', [DeliveryChallanController::class, 'markDelivered'])->name('challans.deliver');
    Route::delete('/challans/{challan}', [DeliveryChallanController::class, 'destroy'])->name('challans.destroy');

    // Customer Dues & Receivables Ledger
    Route::get('/dues', [CustomerDueController::class, 'index'])->name('dues.index');
    Route::post('/dues/pay', [CustomerDueController::class, 'pay'])->name('dues.pay');

    // Customer Returns & RMA Workflows
    Route::get('/returns', [ProductReturnController::class, 'index'])->name('returns.index');
    Route::post('/returns', [ProductReturnController::class, 'store'])->name('returns.store');
    Route::post('/returns/{return}/approve', [ProductReturnController::class, 'approve'])->name('returns.approve');
    Route::post('/returns/{return}/reject', [ProductReturnController::class, 'reject'])->name('returns.reject');
    Route::post('/returns/{return}/complete', [ProductReturnController::class, 'complete'])->name('returns.complete');

    // Serials & Warranty Center
    Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::post('/warranties/{serial}/defective', [WarrantyController::class, 'markDefective'])->name('warranties.defective');

    // Workshop & Repair Service Management
    Route::get('/services', [ServiceTicketController::class, 'index'])->name('services.index');
    Route::get('/services/create', [ServiceTicketController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceTicketController::class, 'store'])->name('services.store');
    Route::get('/services/{ticket}', [ServiceTicketController::class, 'show'])->name('services.show');
    Route::get('/services/{ticket}/edit', [ServiceTicketController::class, 'edit'])->name('services.edit');
    Route::put('/services/{ticket}', [ServiceTicketController::class, 'update'])->name('services.update');
    Route::post('/services/{ticket}/parts', [ServiceTicketController::class, 'addPart'])->name('services.parts.add');
    Route::delete('/services/{ticket}/parts/{item}', [ServiceTicketController::class, 'removePart'])->name('services.parts.remove');
    Route::post('/services/{ticket}/transition', [ServiceTicketController::class, 'transitionStatus'])->name('services.transition');
    Route::post('/services/{ticket}/pay', [ServiceTicketController::class, 'recordPayment'])->name('services.pay');
    Route::delete('/services/{ticket}', [ServiceTicketController::class, 'destroy'])->name('services.destroy');

    // Project Costing & Client Billing
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/items', [ProjectController::class, 'addItem'])->name('projects.items.add');
    Route::delete('/projects/{project}/items/{item}', [ProjectController::class, 'removeItem'])->name('projects.items.remove');
    Route::post('/projects/{project}/costs', [ProjectController::class, 'addCost'])->name('projects.costs.add');
    Route::delete('/projects/{project}/costs/{cost}', [ProjectController::class, 'removeCost'])->name('projects.costs.remove');
    Route::post('/projects/{project}/bills', [ProjectController::class, 'createBill'])->name('projects.bills.create');
    Route::post('/projects/{project}/pay', [ProjectController::class, 'recordPayment'])->name('projects.pay');
    Route::post('/projects/{project}/transition', [ProjectController::class, 'transitionStatus'])->name('projects.transition');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Staff & HR Management
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    // Business Expenses & Outflows
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Field Travel & Daily Allowance (TA/DA)
    Route::get('/tada', [TaDaClaimController::class, 'index'])->name('tada.index');
    Route::post('/tada', [TaDaClaimController::class, 'store'])->name('tada.store');
    Route::post('/tada/{claim}/approve', [TaDaClaimController::class, 'approve'])->name('tada.approve');
    Route::post('/tada/{claim}/reject', [TaDaClaimController::class, 'reject'])->name('tada.reject');
    Route::delete('/tada/{claim}', [TaDaClaimController::class, 'destroy'])->name('tada.destroy');

    // Monthly Payroll & Salary Slips
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::post('/payroll/{payroll}/pay', [PayrollController::class, 'markPaid'])->name('payroll.pay');
    Route::delete('/payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Tenant Company Settings & Branding
    Route::get('/settings/company', [TenantSettingsController::class, 'index'])->name('settings.company');
    Route::put('/settings/company', [TenantSettingsController::class, 'update'])->name('settings.company.update');

    // Tenant Subscription & Billing
    Route::get('/settings/subscription', [TenantSubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/settings/subscription-portal', [TenantSubscriptionController::class, 'index'])->name('settings.subscription');
    Route::post('/settings/subscription/pay', [TenantSubscriptionController::class, 'submitPayment'])->name('subscription.pay');

    // Tenant Team & Roles Management (Inertia SPA)
    Route::get('/settings/team', [TeamMemberController::class, 'index'])->name('settings.team');
    Route::post('/settings/team/members', [TeamMemberController::class, 'addMember'])->name('settings.team.members.add');
    Route::put('/settings/team/members/{user}', [TeamMemberController::class, 'updateRole'])->name('settings.team.members.update');
    Route::delete('/settings/team/members/{user}', [TeamMemberController::class, 'removeMember'])->name('settings.team.members.remove');
    Route::get('/teams/{team}', [TeamMemberController::class, 'index'])->name('teams.show');

    // PDF Documents Generation
    Route::get('/sales/{order}/invoice', [PdfDocumentController::class, 'salesInvoice'])->name('sales.invoice');
    Route::get('/sales/{order}/pos', [PdfDocumentController::class, 'posSlip'])->name('sales.pos');
    Route::get('/sales/{order}/receipt', [PdfDocumentController::class, 'moneyReceipt'])->name('sales.receipt');
    Route::get('/sales/{order}/packing-slip', [PdfDocumentController::class, 'salesPackingSlip'])->name('sales.packing-slip');
    Route::get('/purchases/{order}/pdf', [PdfDocumentController::class, 'purchaseOrder'])->name('purchases.pdf');
    Route::get('/purchases/{order}/voucher', [PdfDocumentController::class, 'vendorVoucher'])->name('purchases.voucher');
    Route::get('/quotations/{quotation}/pdf', [PdfDocumentController::class, 'quotation'])->name('quotations.pdf');
    Route::get('/challans/{challan}/pdf', [PdfDocumentController::class, 'deliveryChallan'])->name('challans.pdf');
    Route::get('/returns/{return}/credit-note', [PdfDocumentController::class, 'creditNote'])->name('returns.credit-note');
    Route::get('/services/{ticket}/token', [PdfDocumentController::class, 'serviceToken'])->name('services.token');
    Route::get('/services/{ticket}/invoice', [PdfDocumentController::class, 'serviceInvoice'])->name('services.invoice');
    Route::get('/projects/{project}/pdf', [PdfDocumentController::class, 'projectBill'])->name('projects.pdf');
    Route::get('/payroll/{payroll}/pdf', [PdfDocumentController::class, 'salarySlip'])->name('payroll.pdf');

    // Dynamic Bank & MFS Financial Accounts
    Route::get('/accounting/bank-accounts', [FinancialAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('/accounting/bank-accounts', [FinancialAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('/accounting/bank-accounts/{account}', [FinancialAccountController::class, 'update'])->name('bank-accounts.update');
    Route::post('/accounting/bank-accounts/{account}/toggle-active', [FinancialAccountController::class, 'toggleActive'])->name('bank-accounts.toggle-active');
    Route::post('/accounting/bank-accounts/{account}/set-default', [FinancialAccountController::class, 'setDefault'])->name('bank-accounts.set-default');
    Route::delete('/accounting/bank-accounts/{account}', [FinancialAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    // Double-Entry Accounting Engine
    Route::get('/accounting/accounts', [ChartOfAccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounting/accounts', [ChartOfAccountController::class, 'store'])->name('accounts.store');
    Route::put('/accounting/accounts/{account}', [ChartOfAccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounting/accounts/{account}', [ChartOfAccountController::class, 'destroy'])->name('accounts.destroy');

    Route::get('/accounting/journal', [JournalEntryController::class, 'index'])->name('journal.index');
    Route::get('/accounting/journal/create', [JournalEntryController::class, 'create'])->name('journal.create');
    Route::post('/accounting/journal', [JournalEntryController::class, 'store'])->name('journal.store');
    Route::get('/accounting/journal/{entry}', [JournalEntryController::class, 'show'])->name('journal.show');

    // Financial Reports
    Route::get('/accounting/reports/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('/accounting/reports/profit-loss', [FinancialReportController::class, 'profitAndLoss'])->name('reports.profit-loss');
    Route::get('/accounting/reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('/accounting/reports/general-ledger', [FinancialReportController::class, 'generalLedger'])->name('reports.general-ledger');

    // Bank Reconciliation
    Route::get('/accounting/reconciliation', [FinancialReportController::class, 'reconciliation'])->name('reconciliation.index');
    Route::post('/accounting/reconciliation', [FinancialReportController::class, 'storeReconciliation'])->name('reconciliation.store');

    // Financial Reports PDF Downloads
    Route::get('/accounting/reports/trial-balance/pdf', [PdfDocumentController::class, 'trialBalancePdf'])->name('pdf.trial-balance');
    Route::get('/accounting/reports/profit-loss/pdf', [PdfDocumentController::class, 'profitAndLossPdf'])->name('pdf.profit-loss');
    Route::get('/accounting/reports/balance-sheet/pdf', [PdfDocumentController::class, 'balanceSheetPdf'])->name('pdf.balance-sheet');
});

// Platform Super-Admin Control Plane
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'super_admin',
])->group(function () {
    Route::get('/admin', [SuperAdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/tenants', [SuperAdminController::class, 'storeTenant'])->name('admin.tenants.store');
    Route::put('/admin/tenants/{team}', [SuperAdminController::class, 'updateTenant'])->name('admin.tenants.update');
    Route::put('/admin/tenants/{team}/subscription', [SuperAdminController::class, 'updateSubscription'])->name('admin.tenants.subscription.update');
    Route::delete('/admin/tenants/{team}', [SuperAdminController::class, 'destroyTenant'])->name('admin.tenants.destroy');
    Route::post('/admin/tenants/{team}/toggle-status', [SuperAdminController::class, 'toggleStatus'])->name('admin.tenants.toggle-status');
    Route::post('/admin/tenants/{team}/switch', [SuperAdminController::class, 'switchToTenant'])->name('admin.tenants.switch');
    Route::get('/admin/exit-tenant', function () {
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            $user->forceFill(['current_team_id' => null])->save();
            return redirect()->route('admin.dashboard')->with('success', 'Exited tenant workspace. Back to Platform Super-Admin mode.');
        }
        return redirect()->route('dashboard');
    })->name('admin.exit-tenant');

    // Subscription Plans & Quotas Management
    Route::get('/admin/plans', [AdminPlanController::class, 'index'])->name('admin.plans.index');
    Route::post('/admin/plans', [AdminPlanController::class, 'store'])->name('admin.plans.store');
    Route::put('/admin/plans/{plan}', [AdminPlanController::class, 'update'])->name('admin.plans.update');
    Route::delete('/admin/plans/{plan}', [AdminPlanController::class, 'destroy'])->name('admin.plans.destroy');

    // Payment Verification Hub (bKash / Nagad TrxID Approvals)
    Route::get('/admin/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');
    Route::post('/admin/payments/{payment}/approve', [AdminPaymentController::class, 'approve'])->name('admin.payments.approve');
    Route::post('/admin/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('admin.payments.reject');
});

// SSLCommerz Payment Gateway Callbacks (Sandbox Ready)
Route::post('/sslcommerz/success', function () {
    return redirect()->route('subscription.index')->with('success', 'Payment processed successfully via SSLCommerz.');
})->name('sslcommerz.success');

Route::post('/sslcommerz/fail', function () {
    return redirect()->route('subscription.index')->withErrors(['error' => 'SSLCommerz payment failed. Please try again or submit TrxID manually.']);
})->name('sslcommerz.fail');

Route::post('/sslcommerz/cancel', function () {
    return redirect()->route('subscription.index')->with('warning', 'SSLCommerz payment was cancelled.');
})->name('sslcommerz.cancel');

Route::post('/sslcommerz/ipn', function () {
    return response()->json(['status' => 'received']);
})->name('sslcommerz.ipn');

