<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\FinancialAccountService;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search');
        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $customerId = $request->input('customer_id');

        $query = SalesOrder::where('team_id', $teamId)
            ->with(['customer', 'warehouse', 'items.product'])
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $status))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $customerId));

        $orders = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_sales' => (float) SalesOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_receivables' => (float) SalesOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('due_amount'),
            'pending_dispatch' => SalesOrder::where('team_id', $teamId)->whereIn('status', ['draft', 'confirmed'])->count(),
            'total_orders' => SalesOrder::where('team_id', $teamId)->count(),
        ];

        return Inertia::render('Sales/Index', [
            'orders' => $orders,
            'customers' => fn () => Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => fn () => Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get(),
            'financialAccounts' => fn () => app(FinancialAccountService::class)->getActiveAccountsForTeam($teamId),
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'customer_id' => $customerId,
            ],
        ]);
    }

    public function create()
    {
        $teamId = Auth::user()->current_team_id;

        $customers = Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('team_id', $teamId)
            ->where('is_active', true)
            ->with(['stockLevels' => fn ($q) => $q->where('team_id', $teamId)])
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'barcode' => $p->barcode,
                    'unit' => $p->unit,
                    'cost_price' => (float) $p->cost_price,
                    'unit_price' => (float) ($p->unit_price ?? $p->selling_price),
                    'warranty_days' => (int) ($p->warranty_days ?? 0),
                    'is_serialized' => (bool) $p->is_serialized,
                    'total_stock' => (float) $p->stockLevels->sum('quantity'),
                    'stock_levels' => $p->stockLevels->map(fn ($sl) => [
                        'warehouse_id' => $sl->warehouse_id,
                        'quantity' => (float) $sl->quantity,
                        'reserved_quantity' => (float) $sl->reserved_quantity,
                        'available' => max(0, (float) $sl->quantity - (float) $sl->reserved_quantity),
                    ])->values()->all(),
                ];
            });

        $defaultWarehouse = Warehouse::where('team_id', $teamId)->where('is_default', true)->first()
            ?? Warehouse::where('team_id', $teamId)->first();

        $financialAccounts = app(FinancialAccountService::class)->getActiveAccountsForTeam($teamId);

        return Inertia::render('Sales/Create', [
            'customers' => $customers,
            'warehouses' => $warehouses,
            'products' => $products,
            'financialAccounts' => $financialAccounts,
            'defaultWarehouseId' => $defaultWarehouse?->id,
            'todayDate' => now()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request, SalesOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $rules = [
            'customer_mode' => 'required|in:existing,new',
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'order_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,confirmed',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.warranty_days' => 'nullable|integer|min:0',
        ];

        if ((float) $request->input('paid_amount') > 0) {
            if (!$request->filled('financial_account_id')) {
                $defaultAccount = FinancialAccount::where('team_id', $teamId)->where('is_active', true)->first();
                if (!$defaultAccount) {
                    $defaultAccount = FinancialAccount::create([
                        'team_id' => $teamId,
                        'name' => 'Cash in Hand',
                        'type' => 'cash',
                        'account_type' => 'cash',
                        'opening_balance' => 0.00,
                        'current_balance' => 0.00,
                        'is_default' => true,
                        'is_active' => true,
                    ]);
                }
                $request->merge(['financial_account_id' => $defaultAccount->id]);
            }
            $rules['financial_account_id'] = ['required', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)];
            $rules['payment_reference'] = 'nullable|string|max:100';
        }

        if ($request->input('customer_mode') === 'new') {
            $rules['new_customer_name'] = 'required|string|max:255';
            $rules['new_customer_phone'] = 'required|string|max:50';
            $rules['new_customer_address'] = 'required|string|max:500';
            $rules['new_customer_email'] = 'nullable|email|max:255';
        } else {
            $rules['customer_id'] = ['required', Rule::exists('customers', 'id')->where('team_id', $teamId)];
        }

        $validated = $request->validate($rules, [
            'new_customer_name.required' => 'Customer name is required.',
            'new_customer_phone.required' => 'Customer mobile / phone is required.',
            'new_customer_address.required' => 'Customer physical address is required.',
            'customer_id.required' => 'Please select an existing customer or switch to New Customer.',
            'financial_account_id.required' => 'Please select the bank or MFS account where the payment was deposited.',
            'items.min' => 'At least one line item is required.',
        ]);

        $orderData = [
            'warehouse_id' => $validated['warehouse_id'],
            'order_date' => $validated['order_date'],
            'discount_amount' => $validated['discount_amount'] ?? 0.00,
            'tax_amount' => $validated['tax_amount'] ?? 0.00,
            'shipping_cost' => $validated['shipping_cost'] ?? 0.00,
            'paid_amount' => $validated['paid_amount'] ?? 0.00,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'draft',
        ];

        if ($validated['customer_mode'] === 'new') {
            $orderData['new_customer'] = [
                'name' => $validated['new_customer_name'],
                'phone' => $validated['new_customer_phone'],
                'address' => $validated['new_customer_address'],
                'email' => $validated['new_customer_email'] ?? null,
            ];
        } else {
            $orderData['customer_id'] = $validated['customer_id'];
        }

        $itemsData = array_map(function ($it) {
            return [
                'product_id' => $it['product_id'],
                'quantity' => (float) $it['quantity'],
                'unit_price' => (float) $it['unit_price'],
                'warranty_days' => isset($it['warranty_days']) ? (int) $it['warranty_days'] : null,
            ];
        }, $validated['items']);

        $teamId = Auth::user()->current_team_id;

        $order = DB::transaction(function () use ($service, $orderData, $itemsData, $validated, $teamId) {
            $order = $service->createOrder($orderData, $itemsData);

            if ((float) $order->paid_amount > 0 && !empty($validated['financial_account_id'])) {
                $account = FinancialAccount::where('team_id', $teamId)->findOrFail($validated['financial_account_id']);
                $account->deposit((float) $order->paid_amount, [
                    'reference_type' => SalesOrder::class,
                    'reference_id' => $order->id,
                    'reference_number' => $validated['payment_reference'] ?? null,
                    'description' => "Initial payment collection for Sales Order #{$order->order_number}",
                ]);
            }

            return $order;
        });

        return redirect()->route('sales.index')->with('success', "Sales Order {$order->order_number} generated successfully.");
    }

    public function confirm(SalesOrder $order, SalesOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        $service->confirmOrder($order);

        return redirect()->back()->with('success', "Order {$order->order_number} confirmed and inventory reserved.");
    }

    public function dispatchOrder(SalesOrder $order, SalesOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        $service->dispatchOrder($order);

        return redirect()->back()->with('success', "Order {$order->order_number} dispatched from warehouse.");
    }

    public function complete(SalesOrder $order, SalesOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        $service->completeOrder($order);

        return redirect()->back()->with('success', "Order {$order->order_number} marked as fulfilled.");
    }

    public function cancel(SalesOrder $order, SalesOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        $service->cancelOrder($order);

        return redirect()->back()->with('success', "Order {$order->order_number} cancelled and reservations released.");
    }

    public function pay(Request $request, SalesOrder $order, SalesOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        if (!$request->filled('financial_account_id')) {
            $defaultAccount = FinancialAccount::where('team_id', $teamId)->where('is_active', true)->first();
            if (!$defaultAccount) {
                $defaultAccount = FinancialAccount::create([
                    'team_id' => $teamId,
                    'name' => 'Cash in Hand',
                    'type' => 'cash',
                    'account_type' => 'cash',
                    'opening_balance' => 0.00,
                    'current_balance' => 0.00,
                    'is_default' => true,
                    'is_active' => true,
                ]);
            }
            $request->merge(['financial_account_id' => $defaultAccount->id]);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => ['required', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'payment_reference' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'financial_account_id.required' => 'Please select the depositing bank or MFS account.',
        ]);

        if ((float) $validated['amount'] > (float) $order->due_amount) {
            throw new \InvalidArgumentException("Payment amount exceeds outstanding due amount.");
        }

        DB::transaction(function () use ($order, $service, $validated, $teamId) {
            $service->recordPayment($order, (float) $validated['amount']);

            $account = FinancialAccount::where('team_id', $teamId)->findOrFail($validated['financial_account_id']);
            $account->deposit((float) $validated['amount'], [
                'date' => $validated['payment_date'] ?? now()->toDateString(),
                'reference_type' => SalesOrder::class,
                'reference_id' => $order->id,
                'reference_number' => $validated['payment_reference'] ?? null,
                'description' => "Payment collection for Sales Order #{$order->order_number}" . (!empty($validated['notes']) ? " ({$validated['notes']})" : ""),
            ]);
        });

        return redirect()->back()->with('success', "Payment of {$validated['amount']} recorded for {$order->order_number}.");
    }
}
