<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\FinancialAccountService;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $supplierId = $request->input('supplier_id');

        $query = PurchaseOrder::where('team_id', $teamId)
            ->with(['supplier', 'warehouse', 'items.product'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('order_number', 'like', "%{$search}%")
                        ->orWhere('po_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($supplierId, fn ($q, $id) => $q->where('supplier_id', $id));

        $orders = $query->latest('id')->paginate(15)->withQueryString();

        $suppliers = Supplier::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        // High level metrics
        $metrics = [
            'total_inbound' => PurchaseOrder::where('team_id', $teamId)->count(),
            'pending_orders' => PurchaseOrder::where('team_id', $teamId)->whereIn('status', ['draft', 'ordered', 'partially_received'])->count(),
            'total_payables' => (float) PurchaseOrder::where('team_id', $teamId)->sum('due_amount'),
        ];

        $financialAccounts = app(FinancialAccountService::class)->getActiveAccountsForTeam($teamId);

        return Inertia::render('Purchases/Index', [
            'orders' => $orders,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'financialAccounts' => $financialAccounts,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'supplier_id' => $supplierId,
            ],
        ]);
    }

    public function create()
    {
        $teamId = Auth::user()->current_team_id;

        $suppliers = Supplier::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $defaultWarehouse = Warehouse::where('team_id', $teamId)->where('is_default', true)->first()
            ?? Warehouse::where('team_id', $teamId)->first();

        $financialAccounts = app(FinancialAccountService::class)->getActiveAccountsForTeam($teamId);

        return Inertia::render('Purchases/Create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
            'financialAccounts' => $financialAccounts,
            'defaultWarehouseId' => $defaultWarehouse?->id,
            'todayDate' => now()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request, PurchaseOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $rules = [
            'vendor_mode' => 'required|in:existing,new',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string|max:1000',
            'paid_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.manufacture_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date',
        ];

        if ((float) $request->input('paid_amount') > 0) {
            $rules['financial_account_id'] = 'required|exists:financial_accounts,id';
            $rules['payment_reference'] = 'nullable|string|max:100';
        }

        if ($request->input('vendor_mode') === 'new') {
            $rules['new_vendor_name'] = 'required|string|max:255';
            $rules['new_vendor_phone'] = 'required|string|max:50';
            $rules['new_vendor_address'] = 'required|string|max:500';
            $rules['new_vendor_contact'] = 'nullable|string|max:255';
            $rules['new_vendor_email'] = 'nullable|email|max:255';
            $rules['new_vendor_tax_number'] = 'nullable|string|max:100';
        } else {
            $rules['supplier_id'] = 'required|exists:suppliers,id';
        }

        $validated = $request->validate($rules, [
            'new_vendor_name.required' => 'Vendor / Supplier company name is required.',
            'new_vendor_phone.required' => 'Vendor mobile / phone number is required.',
            'new_vendor_address.required' => 'Vendor physical address is required.',
            'supplier_id.required' => 'Please select an existing vendor or switch to New Vendor.',
            'financial_account_id.required' => 'Please select the bank or MFS account used to pay the vendor.',
            'items.min' => 'You must add at least one line item to the purchase order.',
        ]);

        $orderData = [
            'warehouse_id' => $validated['warehouse_id'],
            'order_date' => $validated['order_date'],
            'expected_date' => $validated['expected_delivery_date'] ?? null,
            'paid_amount' => $validated['paid_amount'] ?? 0.00,
            'notes' => $validated['notes'] ?? null,
            'status' => 'ordered',
        ];

        if ($validated['vendor_mode'] === 'new') {
            $orderData['new_supplier'] = [
                'name' => $validated['new_vendor_name'],
                'phone' => $validated['new_vendor_phone'],
                'address' => $validated['new_vendor_address'],
                'contact_name' => $validated['new_vendor_contact'] ?? null,
                'email' => $validated['new_vendor_email'] ?? null,
                'tax_number' => $validated['new_vendor_tax_number'] ?? null,
            ];
        } else {
            $orderData['supplier_id'] = $validated['supplier_id'];
        }

        $itemsData = array_map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'quantity' => (float) $item['quantity_ordered'],
                'unit_cost' => (float) $item['unit_cost'],
                'batch_number' => $item['batch_number'] ?? null,
                'manufacture_date' => $item['manufacture_date'] ?? null,
                'expiry_date' => $item['expiry_date'] ?? null,
            ];
        }, $validated['items']);

        $order = DB::transaction(function () use ($service, $orderData, $itemsData, $validated, $teamId) {
            $order = $service->createOrder($orderData, $itemsData);

            if ((float) $order->paid_amount > 0 && !empty($validated['financial_account_id'])) {
                $account = FinancialAccount::where('team_id', $teamId)->findOrFail($validated['financial_account_id']);
                $account->withdraw((float) $order->paid_amount, [
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $order->id,
                    'reference_number' => $validated['payment_reference'] ?? null,
                    'description' => "Initial payment to vendor for PO #{$order->po_number}",
                ]);
            }

            return $order;
        });

        return redirect()->route('purchases.index')->with('success', "Purchase Order {$order->po_number} placed successfully.");
    }

    public function edit(PurchaseOrder $order)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        if (in_array($order->status, ['received', 'cancelled'])) {
            return redirect()->route('purchases.index')
                ->with('error', "Purchase Order {$order->po_number} is {$order->status} and cannot be modified.");
        }

        $order->load(['supplier', 'warehouse', 'items.product']);

        $suppliers = Supplier::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Purchases/Edit', [
            'order' => $order,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function update(Request $request, PurchaseOrder $order, PurchaseOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        if (in_array($order->status, ['received', 'cancelled'])) {
            return redirect()->route('purchases.index')
                ->with('error', "Purchase Order {$order->po_number} is {$order->status} and cannot be modified.");
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.manufacture_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date',
        ], [
            'supplier_id.required' => 'Please select a vendor / supplier.',
            'warehouse_id.required' => 'Please select the destination warehouse.',
            'items.min' => 'You must have at least one line item in the purchase order.',
        ]);

        $orderData = [
            'supplier_id' => $validated['supplier_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'order_date' => $validated['order_date'],
            'expected_date' => $validated['expected_delivery_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        $itemsData = array_map(function ($item) {
            return [
                'id' => $item['id'] ?? null,
                'product_id' => $item['product_id'],
                'quantity' => (float) $item['quantity_ordered'],
                'unit_cost' => (float) $item['unit_cost'],
                'batch_number' => $item['batch_number'] ?? null,
                'manufacture_date' => $item['manufacture_date'] ?? null,
                'expiry_date' => $item['expiry_date'] ?? null,
            ];
        }, $validated['items']);

        $service->updateOrder($order, $orderData, $itemsData);

        return redirect()->route('purchases.index')->with('success', "Purchase Order {$order->po_number} updated successfully.");
    }

    public function pay(Request $request, PurchaseOrder $order, PurchaseOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'payment_reference' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'financial_account_id.required' => 'Please select the bank or MFS account used to pay the vendor.',
        ]);

        if ((float) $validated['amount'] > (float) $order->due_amount) {
            throw new \InvalidArgumentException("Payment amount exceeds outstanding payable due.");
        }

        DB::transaction(function () use ($order, $service, $validated, $teamId) {
            $service->recordPayment($order, (float) $validated['amount']);

            $account = FinancialAccount::where('team_id', $teamId)->findOrFail($validated['financial_account_id']);
            $account->withdraw((float) $validated['amount'], [
                'date' => $validated['payment_date'] ?? now()->toDateString(),
                'reference_type' => PurchaseOrder::class,
                'reference_id' => $order->id,
                'reference_number' => $validated['payment_reference'] ?? null,
                'description' => "Vendor payment for PO #{$order->po_number}" . (!empty($validated['notes']) ? " ({$validated['notes']})" : ""),
            ]);
        });

        return redirect()->back()->with('success', "Payment of {$validated['amount']} recorded for PO {$order->po_number}.");
    }

    public function markOrdered(PurchaseOrder $order, PurchaseOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        $service->markAsOrdered($order);

        return redirect()->back()->with('success', "Purchase Order {$order->po_number} marked as ordered.");
    }

    public function receive(Request $request, PurchaseOrder $order, PurchaseOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.bin_location' => 'nullable|string|max:100',
            'items.*.serials' => 'nullable|array',
        ]);

        $receivedItems = array_map(function ($it) {
            return [
                'item_id' => (int) $it['item_id'],
                'quantity' => (float) $it['quantity'],
                'batch_number' => $it['batch_number'] ?? null,
                'bin_location' => $it['bin_location'] ?? null,
                'serials' => $it['serials'] ?? [],
            ];
        }, $request->input('items'));

        $service->receiveItems($order, $receivedItems);

        return redirect()->back()->with('success', "Inventory received and stocked into warehouse.");
    }

    public function cancel(PurchaseOrder $order, PurchaseOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($order->team_id !== $teamId, 403);

        $service->cancelOrder($order);

        return redirect()->back()->with('success', "Purchase Order {$order->po_number} cancelled.");
    }
}
