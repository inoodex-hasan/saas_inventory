<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\FinancialAccountService;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerDueController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $filter = $request->input('filter', 'all_with_dues'); // all_with_dues, fully_paid, all

        $customers = Customer::where('team_id', $teamId)
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filter === 'all_with_dues', function ($q) {
                $q->whereHas('salesOrders', fn ($so) => $so->where('due_amount', '>', 0)->where('status', '!=', 'cancelled'));
            })
            ->when($filter === 'fully_paid', function ($q) {
                $q->whereDoesntHave('salesOrders', fn ($so) => $so->where('due_amount', '>', 0)->where('status', '!=', 'cancelled'))
                  ->whereHas('salesOrders', fn ($so) => $so->where('status', '!=', 'cancelled'));
            })
            ->withSum(['salesOrders as total_billed' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total_amount')
            ->withSum(['salesOrders as total_paid' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'paid_amount')
            ->withSum(['salesOrders as total_due' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'due_amount')
            ->with(['salesOrders' => function ($q) {
                $q->where('status', '!=', 'cancelled')->where('due_amount', '>', 0)->latest('id');
            }])
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($c) {
                $totalBilled = (float) ($c->total_billed ?? 0);
                $totalPaid = (float) ($c->total_paid ?? 0);
                $totalDue = (float) ($c->total_due ?? 0);
                $dueOrders = $c->salesOrders->values();

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'company_name' => $c->company_name,
                    'phone' => $c->phone,
                    'email' => $c->email,
                    'address' => $c->address,
                    'opening_balance' => (float) ($c->opening_balance ?? 0),
                    'total_billed' => $totalBilled,
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                    'total_due_formatted' => number_format($totalDue, 2),
                    'due_orders' => $dueOrders,
                ];
            });

        $metrics = [
            'total_receivables' => (float) SalesOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('due_amount'),
            'total_collections' => (float) SalesOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('paid_amount'),
            'total_billed' => (float) SalesOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'customers_with_due' => Customer::where('team_id', $teamId)
                ->whereHas('salesOrders', fn ($q) => $q->where('due_amount', '>', 0)->where('status', '!=', 'cancelled'))
                ->count(),
        ];

        $financialAccounts = app(FinancialAccountService::class)->getActiveAccountsForTeam($teamId);

        return Inertia::render('Dues/Index', [
            'page_title' => 'Customer Due Ledger',
            'customers' => $customers,
            'metrics' => $metrics,
            'financialAccounts' => $financialAccounts,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
        ]);
    }

    public function pay(Request $request, SalesOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;

        if (!$request->filled('financial_account_id')) {
            $defaultAccount = app(FinancialAccountService::class)->getDefaultAccount($teamId);
            if ($defaultAccount) {
                $request->merge(['financial_account_id' => $defaultAccount->id]);
            }
        }

        $validated = $request->validate([
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('team_id', $teamId)],
            'order_id' => ['nullable', Rule::exists('sales_orders', 'id')->where('team_id', $teamId)],
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => ['required', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'allocations' => 'nullable|array',
            'allocations.*.order_id' => ['required_with:allocations', Rule::exists('sales_orders', 'id')->where('team_id', $teamId)],
            'allocations.*.amount' => 'required_with:allocations|numeric|min:0',
        ]);

        $totalPayment = round((float) $validated['amount'], 2);

        // Normalize allocations: support both multi-allocation array and single order fallback
        $allocations = [];
        if (!empty($validated['allocations'])) {
            $allocations = array_values(array_filter($validated['allocations'], function ($a) {
                return (float) ($a['amount'] ?? 0) > 0;
            }));
        } elseif (!empty($validated['order_id'])) {
            $allocations = [
                [
                    'order_id' => $validated['order_id'],
                    'amount' => $totalPayment,
                ],
            ];
        }

        if (empty($allocations)) {
            throw ValidationException::withMessages([
                'amount' => 'Please allocate the payment amount to at least one invoice.',
            ]);
        }

        $totalAllocated = round(array_sum(array_column($allocations, 'amount')), 2);
        if (abs($totalAllocated - $totalPayment) > 0.01) {
            throw ValidationException::withMessages([
                'amount' => "Total allocated amount (৳" . number_format($totalAllocated, 2) . ") must match the payment collection amount (৳" . number_format($totalPayment, 2) . ").",
            ]);
        }

        $orderIds = array_column($allocations, 'order_id');
        $orders = SalesOrder::where('team_id', $teamId)
            ->whereIn('id', $orderIds)
            ->get()
            ->keyBy('id');

        foreach ($allocations as $alloc) {
            $order = $orders->get($alloc['order_id']);
            if (!$order) {
                throw ValidationException::withMessages([
                    'amount' => "Invoice ID {$alloc['order_id']} not found.",
                ]);
            }
            if ((float) $alloc['amount'] > (float) $order->due_amount + 0.01) {
                throw ValidationException::withMessages([
                    'amount' => "Allocated amount of ৳" . number_format($alloc['amount'], 2) . " exceeds outstanding balance for invoice {$order->order_number} (৳" . number_format($order->due_amount, 2) . ").",
                ]);
            }
        }

        $account = FinancialAccount::where('team_id', $teamId)->findOrFail($validated['financial_account_id']);

        $customer = null;
        if (!empty($validated['customer_id'])) {
            $customer = Customer::where('team_id', $teamId)->find($validated['customer_id']);
        }
        if (!$customer && $orders->isNotEmpty()) {
            $customer = $orders->first()->customer;
        }

        $processedInvoices = [];

        DB::transaction(function () use ($service, $orders, $allocations, $account, $validated, $totalPayment, $customer, &$processedInvoices) {
            foreach ($allocations as $alloc) {
                $order = $orders->get($alloc['order_id']);
                $allocatedAmount = round((float) $alloc['amount'], 2);
                $service->recordPayment($order, $allocatedAmount);
                $processedInvoices[] = "{$order->order_number} (৳" . number_format($allocatedAmount, 2) . ")";
            }

            $customerName = $customer?->name ?? 'Customer';
            $description = count($processedInvoices) === 1
                ? "Customer due settlement for {$customerName} - Invoice {$processedInvoices[0]}"
                : "Multi-invoice due settlement for {$customerName} across " . count($processedInvoices) . " invoices: " . implode(', ', $processedInvoices);

            if (!empty($validated['notes'])) {
                $description .= " - {$validated['notes']}";
            }

            $account->deposit(
                $totalPayment,
                [
                    'description' => $description,
                    'reference_type' => $customer ? Customer::class : SalesOrder::class,
                    'reference_id' => $customer ? $customer->id : $orders->first()->id,
                    'reference_number' => $validated['payment_reference'] ?? null,
                ]
            );
        });

        $customerDisplayName = $customer?->name ?? 'Customer';
        $successMsg = count($processedInvoices) === 1
            ? "Payment of ৳" . number_format($totalPayment, 2) . " recorded for invoice {$processedInvoices[0]}."
            : "Payment of ৳" . number_format($totalPayment, 2) . " successfully distributed across " . count($processedInvoices) . " invoices for {$customerDisplayName}.";

        return redirect()->back()->with('success', $successMsg);
    }

    public function storeCustomer(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'Customer name is required.',
            'phone.required' => 'Customer contact number is required.',
        ]);

        $openingBalance = round((float) ($request->input('opening_balance', 0)), 2);
        $validated['opening_balance'] = $openingBalance;
        $validated['team_id'] = $teamId;
        $validated['is_active'] = true;

        $customer = DB::transaction(function () use ($validated, $openingBalance, $teamId) {
            $customer = Customer::create($validated);

            if ($openingBalance > 0) {
                $warehouse = Warehouse::where('team_id', $teamId)->where('is_default', true)->first()
                    ?? Warehouse::where('team_id', $teamId)->first();

                if (!$warehouse) {
                    $warehouse = Warehouse::create([
                        'team_id' => $teamId,
                        'name' => 'Main Warehouse',
                        'code' => 'WH-01',
                        'is_default' => true,
                        'is_active' => true,
                    ]);
                }

                $orderNumber = 'INV-OPEN-' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT);
                while (SalesOrder::where('team_id', $teamId)->where('order_number', $orderNumber)->exists()) {
                    $orderNumber .= '-' . rand(10, 99);
                }

                $salesOrder = SalesOrder::create([
                    'team_id' => $teamId,
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'order_number' => $orderNumber,
                    'status' => 'completed',
                    'payment_status' => 'unpaid',
                    'order_date' => now()->toDateString(),
                    'subtotal' => $openingBalance,
                    'tax_amount' => 0.00,
                    'shipping_cost' => 0.00,
                    'discount_amount' => 0.00,
                    'total_amount' => $openingBalance,
                    'paid_amount' => 0.00,
                    'due_amount' => $openingBalance,
                    'notes' => 'Opening receivable due balance recorded on customer registration.',
                    'created_by' => Auth::id(),
                ]);

                $this->recordCustomerOpeningJournalEntry($teamId, $customer, $salesOrder, $openingBalance);
            }

            return $customer;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'customer' => $customer,
            ]);
        }

        $successMsg = "Customer '{$customer->name}' created successfully" . ($openingBalance > 0 ? " with opening due of ৳" . number_format($openingBalance, 2) : "") . ".";

        return redirect()->back()->with('success', $successMsg);
    }

    public function updateCustomer(Request $request, Customer $customer)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($customer->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'The customer name is required.',
            'phone.required' => 'The customer phone number is required.',
        ]);

        $oldOpeningBalance = round((float) ($customer->opening_balance ?? 0), 2);
        $newOpeningBalance = $request->has('opening_balance')
            ? round((float) $request->input('opening_balance', 0), 2)
            : $oldOpeningBalance;
        $validated['opening_balance'] = $newOpeningBalance;

        DB::transaction(function () use ($customer, $validated, $oldOpeningBalance, $newOpeningBalance, $teamId) {
            $prefix = 'INV-OPEN-' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT);
            $so = SalesOrder::where('team_id', $teamId)
                ->where('customer_id', $customer->id)
                ->where('order_number', 'like', $prefix . '%')
                ->first();

            if ($so && $newOpeningBalance < (float) $so->paid_amount) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'Opening balance cannot be less than already collected amount of ' . number_format($so->paid_amount, 2),
                ]);
            }

            $customer->update($validated);

            if ($oldOpeningBalance != $newOpeningBalance) {
                if ($so) {
                    if ($newOpeningBalance <= 0 && (float) $so->paid_amount <= 0) {
                        JournalEntry::where('reference_type', SalesOrder::class)
                            ->where('reference_id', $so->id)
                            ->delete();
                        $so->delete();
                    } else {
                        $so->subtotal = $newOpeningBalance;
                        $so->total_amount = $newOpeningBalance;
                        $so->due_amount = max(0, $newOpeningBalance - (float) $so->paid_amount);
                        if ($so->due_amount <= 0) {
                            $so->payment_status = 'paid';
                        } elseif ((float) $so->paid_amount > 0) {
                            $so->payment_status = 'partially_paid';
                        } else {
                            $so->payment_status = 'unpaid';
                        }
                        $so->save();

                        JournalEntry::where('reference_type', SalesOrder::class)
                            ->where('reference_id', $so->id)
                            ->delete();
                        if ($newOpeningBalance > 0) {
                            $this->recordCustomerOpeningJournalEntry($teamId, $customer, $so, $newOpeningBalance);
                        }
                    }
                } elseif ($newOpeningBalance > 0) {
                    $warehouse = Warehouse::where('team_id', $teamId)->where('is_default', true)->first()
                        ?? Warehouse::where('team_id', $teamId)->first();

                    if (!$warehouse) {
                        $warehouse = Warehouse::create([
                            'team_id' => $teamId,
                            'name' => 'Main Warehouse',
                            'code' => 'WH-01',
                            'is_default' => true,
                            'is_active' => true,
                        ]);
                    }

                    $orderNumber = $prefix;
                    while (SalesOrder::where('team_id', $teamId)->where('order_number', $orderNumber)->exists()) {
                        $orderNumber .= '-' . rand(10, 99);
                    }

                    $salesOrder = SalesOrder::create([
                        'team_id' => $teamId,
                        'customer_id' => $customer->id,
                        'warehouse_id' => $warehouse->id,
                        'order_number' => $orderNumber,
                        'status' => 'completed',
                        'payment_status' => 'unpaid',
                        'order_date' => now()->toDateString(),
                        'subtotal' => $newOpeningBalance,
                        'tax_amount' => 0.00,
                        'shipping_cost' => 0.00,
                        'discount_amount' => 0.00,
                        'total_amount' => $newOpeningBalance,
                        'paid_amount' => 0.00,
                        'due_amount' => $newOpeningBalance,
                        'notes' => 'Opening receivable due balance recorded on customer update.',
                        'created_by' => Auth::id(),
                    ]);

                    $this->recordCustomerOpeningJournalEntry($teamId, $customer, $salesOrder, $newOpeningBalance);
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'customer' => $customer->fresh(),
            ]);
        }

        return redirect()->back()->with('success', "Customer '{$customer->name}' updated successfully.");
    }

    protected function recordCustomerOpeningJournalEntry(int $teamId, Customer $customer, SalesOrder $so, float $amount): void
    {
        try {
            /** @var AccountingService $accountingService */
            $accountingService = app(AccountingService::class);
            $accountingService->ensureDefaultAccounts($teamId);

            $arAccount = ChartOfAccount::where('team_id', $teamId)->where('account_code', '1030')->first();
            $equityAccount = ChartOfAccount::where('team_id', $teamId)->whereIn('account_code', ['3020', '3010'])->orderBy('account_code', 'desc')->first();

            if ($arAccount && $equityAccount) {
                $fiscalYear = FiscalYear::where('team_id', $teamId)->where('is_closed', false)->first();

                $accountingService->recordJournalEntry([
                    'fiscal_year_id' => $fiscalYear?->id,
                    'entry_date' => now()->toDateString(),
                    'reference_type' => SalesOrder::class,
                    'reference_id' => $so->id,
                    'narration' => "Opening receivable due balance for Customer: {$customer->name} (#{$so->order_number})",
                ], [
                    [
                        'chart_of_account_id' => $arAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => "Accounts Receivable - Opening Due for {$customer->name}",
                    ],
                    [
                        'chart_of_account_id' => $equityAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => "Opening Balance Equity - Customer Receivable for {$customer->name}",
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Could not record journal entry for customer opening balance: " . $e->getMessage());
        }
    }
}
