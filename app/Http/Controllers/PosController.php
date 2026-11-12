<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockOperationException;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\FinancialAccountService;
use App\Services\SalesOrderService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PosController extends Controller
{
    /**
     * Resolve the active team ID for the current session/user.
     */
    protected function resolveTeamId(): int
    {
        $user = Auth::user();
        $teamId = $user?->current_team_id ?? $user?->currentTeam?->id;
        if (!$teamId) {
            $firstTeam = Team::first();
            if ($firstTeam) {
                $teamId = (int) $firstTeam->id;
                if ($user) {
                    $user->forceFill(['current_team_id' => $teamId])->save();
                }
            }
        }
        return (int) ($teamId ?? 1);
    }

    /**
     * Render the high-speed Vue 3 POS interface.
     */
    public function index(Request $request, FinancialAccountService $accountService)
    {
        $teamId = $this->resolveTeamId();

        // Ensure default financial accounts exist
        $accountService->ensureDefaultAccounts($teamId);

        // 1. Warehouses (Default fulfillment hub)
        $warehouses = Warehouse::where('team_id', $teamId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        $userAssignedWh = Auth::user()->assignedWarehouse(Auth::user()->currentTeam);
        $defaultWarehouse = $userAssignedWh ?? $warehouses->first();
        $selectedWarehouseId = (int) ($defaultWarehouse?->id ?? 0);

        // 2. Categories
        $categories = ProductCategory::where('team_id', $teamId)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        // 3. Brands
        $brands = Brand::where('team_id', $teamId)
            ->orderBy('name')
            ->get(['id', 'name']);

        // 4. Products with Stock & Variants (Aggregated across all warehouses)
        $products = Product::where('team_id', $teamId)
            ->where('is_active', true)
            ->with([
                'category:id,name',
                'brand:id,name',
                'variants' => function ($q) use ($teamId) {
                    $q->where('is_active', true)
                        ->with(['stockLevels' => fn ($sl) => $sl->where('team_id', $teamId)]);
                },
                'stockLevels' => fn ($q) => $q->where('team_id', $teamId),
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                if ($p->has_variants && $p->variants->isNotEmpty()) {
                    $stockQty = (float) $p->variants->sum(function ($v) {
                        return max(0, (float) $v->stockLevels->sum('quantity') - (float) $v->stockLevels->sum('reserved_quantity'));
                    });
                } else {
                    $stockQty = max(0, (float) $p->stockLevels->sum('quantity') - (float) $p->stockLevels->sum('reserved_quantity'));
                }

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku ?: "SKU-{$p->id}",
                    'barcode' => $p->barcode ?: '',
                    'category_id' => $p->category_id,
                    'category_name' => $p->category?->name ?? 'General',
                    'brand_name' => $p->brand?->name ?? '',
                    'unit' => $p->unit ?? 'pcs',
                    'unit_price' => (float) $p->unit_price,
                    'cost_price' => (float) $p->cost_price,
                    'stock' => $stockQty,
                    'has_variants' => (bool) $p->has_variants,
                    'is_serialized' => (bool) $p->is_serialized,
                    'warranty_days' => (int) $p->warranty_days,
                    'image_url' => $p->image_url,
                    'variants' => $p->variants->map(function ($v) use ($p) {
                        $vStockQty = max(0, (float) $v->stockLevels->sum('quantity') - (float) $v->stockLevels->sum('reserved_quantity'));

                        return [
                            'id' => $v->id,
                            'name' => $v->name,
                            'sku' => $v->sku ?: ($v->barcode ?: "SKU-V{$v->id}"),
                            'barcode' => $v->barcode ?: '',
                            'attributes' => $v->attributes ?? [],
                            'unit_price' => (float) ($v->unit_price > 0 ? $v->unit_price : $p->unit_price),
                            'stock' => $vStockQty,
                        ];
                    })->values()->all(),
                ];
            });

        // 5. Customers
        $customers = Customer::where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'address']);

        // Default or Persistent Walk-in Customer
        $walkInCustomer = Customer::firstOrCreate(
            ['team_id' => $teamId, 'phone' => '01000000000'],
            ['name' => 'Walk-in Customer', 'address' => 'Counter Sale', 'is_active' => true]
        );

        // 6. Financial Accounts
        $financialAccounts = $accountService->getActiveAccountsForTeam($teamId);

        // 7. Recent POS Orders
        $recentOrders = SalesOrder::where('team_id', $teamId)
            ->with(['customer:id,name,phone', 'items.product:id,name', 'items.variant:id,name'])
            ->latest('id')
            ->take(20)
            ->get()
            ->map(function ($o) {
                return [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'date' => $o->created_at->format('d M, h:i A'),
                    'customer_name' => $o->customer?->name ?? 'Walk-in Customer',
                    'customer_phone' => $o->customer?->phone ?? '',
                    'total_amount' => (float) $o->total_amount,
                    'paid_amount' => (float) $o->paid_amount,
                    'due_amount' => (float) $o->due_amount,
                    'status' => $o->status,
                    'payment_status' => $o->payment_status,
                    'items_summary' => $o->items->map(fn ($it) => ($it->product?->name ?? 'Item') . ' (' . (float) $it->quantity . ')')->implode(', '),
                    'pos_url' => route('sales.pos', $o->id),
                    'invoice_url' => route('sales.invoice', $o->id),
                    'pos_invoice_page_url' => route('pos.invoice', $o->id),
                ];
            });

        // 8. Next estimated sequence
        $prefix = 'POS-' . Carbon::now()->format('Ymd') . '-';
        $lastOrder = SalesOrder::where('team_id', $teamId)
            ->where('order_number', 'LIKE', $prefix . '%')
            ->latest('id')
            ->first();
        $nextSeq = $lastOrder ? str_pad((int) substr($lastOrder->order_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
        $nextOrderNumber = $prefix . $nextSeq;

        return Inertia::render('POS/Index', [
            'warehouses' => $warehouses,
            'selectedWarehouseId' => $selectedWarehouseId,
            'categories' => $categories,
            'brands' => $brands,
            'products' => $products,
            'customers' => $customers,
            'defaultCustomerId' => $walkInCustomer->id,
            'financialAccounts' => $financialAccounts,
            'recentOrders' => $recentOrders,
            'nextOrderNumber' => $nextOrderNumber,
        ]);
    }

    /**
     * Complete and record a real-time POS Sale.
     */
    public function store(Request $request, SalesOrderService $salesService)
    {
        $teamId = $this->resolveTeamId();
        $team = Auth::user()?->currentTeam;

        if ($team && ! app(\App\Services\SubscriptionService::class)->canAddSalesOrder($team)) {
            $message = 'You have reached the maximum sales/invoice limit for your subscription plan. Please upgrade your plan.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }
            return redirect()->back()->with('error', $message);
        }

        $validated = $request->validate([
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('team_id', $teamId)],
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.product_variant_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.warranty_days' => 'nullable|integer|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'tendered_amount' => 'nullable|numeric|min:0',
            'financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $order = DB::transaction(function () use ($validated, $teamId, $salesService) {
                // 1. Resolve Customer
                $customerId = $validated['customer_id'] ?? null;
                if (!$customerId) {
                    if (!empty($validated['customer_phone'])) {
                        $customer = Customer::where('team_id', $teamId)
                            ->where('phone', $validated['customer_phone'])
                            ->first();

                        if (!$customer) {
                            $customer = Customer::create([
                                'team_id' => $teamId,
                                'name' => $validated['customer_name'] ?: 'Customer ' . $validated['customer_phone'],
                                'phone' => $validated['customer_phone'],
                                'address' => $validated['customer_address'] ?? 'Counter Sale',
                                'is_active' => true,
                            ]);
                        }
                        $customerId = $customer->id;
                    } else {
                        // Default walk-in customer
                        $customer = Customer::firstOrCreate(
                            ['team_id' => $teamId, 'phone' => '01000000000'],
                            ['name' => 'Walk-in Customer', 'address' => 'Counter Sale', 'is_active' => true]
                        );
                        $customerId = $customer->id;
                    }
                }

                // 2. Generate POS Order Number
                $prefix = 'POS-' . Carbon::now()->format('Ymd') . '-';
                $lastOrder = SalesOrder::where('team_id', $teamId)
                    ->where('order_number', 'LIKE', $prefix . '%')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();
                $nextSeq = $lastOrder ? str_pad((int) substr($lastOrder->order_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
                $orderNumber = $prefix . $nextSeq;

                $grandTotal = (float) $validated['total_amount'];
                $rawTendered = isset($validated['tendered_amount']) ? (float) $validated['tendered_amount'] : null;

                if ($rawTendered !== null && $rawTendered > 0) {
                    $tenderedAmount = $rawTendered;
                    if ($tenderedAmount >= $grandTotal) {
                        $paidAmount = $grandTotal;
                        $changeAmount = round($tenderedAmount - $grandTotal, 2);
                    } else {
                        // Customer tendered less than grand total (partial cash payment / due sale)
                        $paidAmount = $tenderedAmount;
                        $changeAmount = 0.00;
                    }
                } else {
                    $paidAmount = isset($validated['paid_amount']) ? min($grandTotal, (float) $validated['paid_amount']) : $grandTotal;
                    $tenderedAmount = $paidAmount;
                    $changeAmount = 0.00;
                }

                // 3. Resolve Fulfillment Warehouse
                $warehouseId = $validated['warehouse_id'] ?? null;
                if (!$warehouseId) {
                    $teamWarehouses = Warehouse::where('team_id', $teamId)->get();
                    $defaultWarehouse = $teamWarehouses->firstWhere('is_default', true) ?? $teamWarehouses->first();

                    if ($defaultWarehouse && $this->warehouseCanFulfill($teamId, $defaultWarehouse->id, $validated['items'])) {
                        $warehouseId = $defaultWarehouse->id;
                    } else {
                        $candidate = $teamWarehouses->first(function ($wh) use ($teamId, $validated) {
                            return $this->warehouseCanFulfill($teamId, $wh->id, $validated['items']);
                        });
                        $warehouseId = $candidate?->id ?? $defaultWarehouse?->id;
                    }
                }

                // Verify stock availability in fulfillment warehouse before creating the order
                if ($warehouseId) {
                    foreach ($validated['items'] as $item) {
                        $stock = \App\Models\StockLevel::where('team_id', $teamId)
                            ->where('warehouse_id', $warehouseId)
                            ->where('product_id', $item['product_id'])
                            ->when(!empty($item['product_variant_id']), fn ($q) => $q->where('product_variant_id', $item['product_variant_id']))
                            ->first();

                        $available = $stock ? (float) $stock->available_quantity : 0.0;
                        $needed = (float) $item['quantity'];
                        if ($available < $needed) {
                            $prod = Product::find($item['product_id']);
                            $prodName = $prod?->name ?? "Product #{$item['product_id']}";
                            $wh = Warehouse::find($warehouseId);
                            $whName = $wh?->name ?? "Warehouse #{$warehouseId}";
                            throw new InsufficientStockException(
                                "Insufficient stock in warehouse '{$whName}' for '{$prodName}'. Required: {$needed}, Available: {$available}."
                            );
                        }
                    }
                }

                // 4. Prepare Order Data
                $orderData = [
                    'team_id' => $teamId,
                    'customer_id' => $customerId,
                    'warehouse_id' => $warehouseId,
                    'order_number' => $orderNumber,
                    'order_date' => now()->toDateString(),
                    'discount_amount' => (float) ($validated['discount_amount'] ?? 0.00),
                    'tax_amount' => (float) ($validated['tax_amount'] ?? 0.00),
                    'shipping_cost' => (float) ($validated['shipping_cost'] ?? 0.00),
                    'paid_amount' => $paidAmount,
                    'tendered_amount' => $tenderedAmount,
                    'change_amount' => $changeAmount,
                    'notes' => $validated['notes'] ?? 'POS Counter Sale',
                    'status' => 'confirmed',
                ];

                // Prepare Items
                $itemsData = array_map(function ($it) {
                    return [
                        'product_id' => (int) $it['product_id'],
                        'product_variant_id' => !empty($it['product_variant_id']) ? (int) $it['product_variant_id'] : null,
                        'quantity' => (float) $it['quantity'],
                        'unit_price' => (float) $it['unit_price'],
                        'warranty_days' => isset($it['warranty_days']) ? (int) $it['warranty_days'] : null,
                    ];
                }, $validated['items']);

                // 4. Create Order (reserves stock)
                $order = $salesService->createOrder($orderData, $itemsData);

                // 5. Dispatch goods immediately (deducts warehouse inventory)
                $salesService->dispatchOrder($order);

                // 6. Complete Order
                $salesService->completeOrder($order);

                // 7. Deposit into Financial Account
                if ($paidAmount > 0) {
                    $accountId = $validated['financial_account_id'] ?? null;
                    if (!$accountId) {
                        $defaultAcc = FinancialAccount::where('team_id', $teamId)->where('is_active', true)->where('is_default', true)->first()
                            ?? FinancialAccount::where('team_id', $teamId)->where('is_active', true)->first();
                        $accountId = $defaultAcc?->id;
                    }

                    if ($accountId) {
                        $account = FinancialAccount::where('team_id', $teamId)->find($accountId);
                        if ($account) {
                            $account->deposit($paidAmount, [
                                'reference_type' => SalesOrder::class,
                                'reference_id' => $order->id,
                                'reference_number' => $validated['payment_reference'] ?? $order->order_number,
                                'description' => "POS counter collection for Sale #{$order->order_number}",
                            ]);
                        }
                    }
                }

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => "Sale {$order->order_number} completed successfully!",
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'tendered_amount' => (float) ($order->tendered_amount ?? $tenderedAmount),
                'change_amount' => (float) ($order->change_amount ?? $changeAmount),
                'due_amount' => (float) $order->due_amount,
                'pos_slip_url' => route('sales.pos', $order->id),
                'invoice_url' => route('sales.invoice', $order->id),
                'pos_invoice_page_url' => route('pos.invoice', $order->id),
            ]);
        } catch (\Throwable $e) {
            Log::error('POS transaction failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $userMessage = ($e instanceof InvalidStockOperationException || $e instanceof InsufficientStockException || $e instanceof \InvalidArgumentException)
                ? $e->getMessage()
                : 'Unable to process POS transaction. Please verify inventory or payment details and try again.';

            return response()->json([
                'success' => false,
                'message' => $userMessage,
            ], 422);
        }
    }

    /**
     * Quick Customer Registration from POS (Only phone required).
     */
    public function storeCustomer(Request $request)
    {
        $teamId = $this->resolveTeamId();

        $validated = $request->validate([
            'phone' => 'required|string|max:50',
            'name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);

        $phone = trim($validated['phone']);

        // Check if customer with this phone already exists in this team
        $existing = Customer::where('team_id', $teamId)
            ->where('phone', $phone)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Existing customer selected.',
                'customer' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                    'phone' => $existing->phone,
                    'email' => $existing->email,
                    'address' => $existing->address,
                ],
            ]);
        }

        $name = !empty($validated['name']) ? trim($validated['name']) : 'Customer ' . $phone;

        $customer = Customer::create([
            'team_id' => $teamId,
            'name' => $name,
            'phone' => $phone,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? 'Counter Sale',
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
            ],
        ]);
    }

    /**
     * Render the POS receipt / interactive invoice view.
     */
    public function invoice(Request $request, ?SalesOrder $order = null)
    {
        $teamId = Auth::user()->current_team_id;

        if (!$order && $request->route('order')) {
            $order = SalesOrder::where('team_id', $teamId)->find($request->route('order'));
        }

        if ($order) {
            abort_if($order->team_id !== $teamId && !Auth::user()->isSuperAdmin(), 403);
            $order->load(['customer', 'warehouse', 'items.product.category', 'creator']);
        } else {
            $order = SalesOrder::where('team_id', $teamId)->latest('id')->first();
            $order?->load(['customer', 'warehouse', 'items.product.category', 'creator']);
        }

        abort_if(!$order, 404, 'No orders found.');

        $team = $order->team ?? Auth::user()->currentTeam;

        return view('pos.invoice', compact('order', 'team'));
    }

    protected function warehouseCanFulfill(int $teamId, int $warehouseId, array $items): bool
    {
        foreach ($items as $item) {
            $stock = \App\Models\StockLevel::where('team_id', $teamId)
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $item['product_id'])
                ->when(!empty($item['product_variant_id']), fn ($q) => $q->where('product_variant_id', $item['product_variant_id']))
                ->first();

            $available = $stock ? (float) $stock->available_quantity : 0.0;
            if ($available < (float) $item['quantity']) {
                return false;
            }
        }
        return true;
    }
}

