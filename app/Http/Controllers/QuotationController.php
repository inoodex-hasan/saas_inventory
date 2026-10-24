<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $customerId = $request->input('customer_id');

        $query = Quotation::where('team_id', $teamId)
            ->with(['customer', 'items.product'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('quotation_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($customerId, fn ($q, $id) => $q->where('customer_id', $id));

        $quotations = $query->latest('id')->paginate(15)->withQueryString();

        $customers = Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $metrics = [
            'total_quotations' => Quotation::where('team_id', $teamId)->count(),
            'total_quoted_value' => (float) Quotation::where('team_id', $teamId)->sum('total_amount'),
            'accepted_quotations' => Quotation::where('team_id', $teamId)->whereIn('status', ['accepted', 'converted'])->count(),
            'pending_proposals' => Quotation::where('team_id', $teamId)->whereIn('status', ['draft', 'sent'])->count(),
        ];

        return Inertia::render('Quotations/Index', [
            'page_title' => 'Quotations & Estimates',
            'quotations' => $quotations,
            'customers' => $customers,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'customer_id' => $customerId,
            ],
        ]);
    }

    public function create()
    {
        $teamId = Auth::user()->current_team_id;

        $customers = Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $prefix = 'QT-' . Carbon::now()->format('Ymd') . '-';
        $last = Quotation::where('team_id', $teamId)->where('quotation_number', 'LIKE', $prefix . '%')->latest('id')->first();
        $nextNum = $last ? str_pad((int) substr($last->quotation_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
        $quotationNumber = $prefix . $nextNum;

        return Inertia::render('Quotations/Create', [
            'page_title' => 'Create Formal Quotation',
            'quotationNumber' => $quotationNumber,
            'customers' => $customers,
            'products' => $products,
            'warehouses' => $warehouses,
            'todayDate' => now()->toDateString(),
            'defaultValidUntil' => now()->addDays(15)->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $rules = [
            'customer_mode' => 'required|in:existing,new',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quotation_date',
            'status' => 'nullable|in:draft,sent,accepted',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];

        if ($request->input('customer_mode') === 'new') {
            $rules['new_customer_name'] = 'required|string|max:255';
            $rules['new_customer_phone'] = 'required|string|max:50';
            $rules['new_customer_address'] = 'nullable|string|max:500';
        } else {
            $rules['customer_id'] = ['required', Rule::exists('customers', 'id')->where('team_id', $teamId)];
        }

        $validated = $request->validate($rules);

        return DB::transaction(function () use ($validated, $teamId) {
            $customerId = $validated['customer_id'] ?? null;
            if ($validated['customer_mode'] === 'new') {
                $customer = Customer::create([
                    'team_id' => $teamId,
                    'name' => $validated['new_customer_name'],
                    'phone' => $validated['new_customer_phone'],
                    'address' => $validated['new_customer_address'] ?? null,
                    'is_active' => true,
                ]);
                $customerId = $customer->id;
            }

            $prefix = 'QT-' . Carbon::now()->format('Ymd') . '-';
            $last = Quotation::where('team_id', $teamId)->where('quotation_number', 'LIKE', $prefix . '%')->latest('id')->first();
            $nextNum = $last ? str_pad((int) substr($last->quotation_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
            $quotationNumber = $prefix . $nextNum;

            $subtotal = 0.00;
            foreach ($validated['items'] as $it) {
                $subtotal += (float) $it['quantity'] * (float) $it['unit_price'];
            }

            $discount = (float) ($validated['discount_amount'] ?? 0.00);
            $taxRate = (float) ($validated['tax_rate'] ?? 0.00);
            $taxAmount = max(0, ($subtotal - $discount) * ($taxRate / 100));
            $total = max(0, $subtotal - $discount + $taxAmount);

            $quotation = Quotation::create([
                'team_id' => $teamId,
                'customer_id' => $customerId,
                'quotation_number' => $quotationNumber,
                'status' => $validated['status'] ?? 'draft',
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'terms' => $validated['terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $it) {
                QuotationItem::create([
                    'team_id' => $teamId,
                    'quotation_id' => $quotation->id,
                    'product_id' => $it['product_id'],
                    'quantity' => (float) $it['quantity'],
                    'unit_price' => (float) $it['unit_price'],
                    'subtotal' => (float) $it['quantity'] * (float) $it['unit_price'],
                ]);
            }

            return redirect()->route('quotations.index')->with('success', "Quotation {$quotation->quotation_number} generated successfully.");
        });
    }

    public function edit(Quotation $quotation)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($quotation->team_id !== $teamId, 403);

        if ($quotation->status === 'converted') {
            return redirect()->route('quotations.index')->with('error', "Converted quotation {$quotation->quotation_number} cannot be edited.");
        }

        $quotation->load(['customer', 'items.product']);

        $customers = Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Quotations/Edit', [
            'page_title' => 'Edit Quotation ' . $quotation->quotation_number,
            'quotation' => $quotation,
            'customers' => $customers,
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($quotation->team_id !== $teamId, 403);

        if ($quotation->status === 'converted') {
            return redirect()->route('quotations.index')->with('error', "Converted quotation {$quotation->quotation_number} cannot be edited.");
        }

        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('team_id', $teamId)],
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quotation_date',
            'status' => 'nullable|in:draft,sent,accepted,declined',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $quotation, $teamId) {
            $subtotal = 0.00;
            foreach ($validated['items'] as $it) {
                $subtotal += (float) $it['quantity'] * (float) $it['unit_price'];
            }

            $discount = (float) ($validated['discount_amount'] ?? 0.00);
            $taxRate = (float) ($validated['tax_rate'] ?? 0.00);
            $taxAmount = max(0, ($subtotal - $discount) * ($taxRate / 100));
            $total = max(0, $subtotal - $discount + $taxAmount);

            $quotation->update([
                'customer_id' => $validated['customer_id'],
                'status' => $validated['status'] ?? $quotation->status,
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'terms' => $validated['terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $quotation->items()->delete();
            foreach ($validated['items'] as $it) {
                QuotationItem::create([
                    'team_id' => $teamId,
                    'quotation_id' => $quotation->id,
                    'product_id' => $it['product_id'],
                    'quantity' => (float) $it['quantity'],
                    'unit_price' => (float) $it['unit_price'],
                    'subtotal' => (float) $it['quantity'] * (float) $it['unit_price'],
                ]);
            }

            return redirect()->route('quotations.index')->with('success', "Quotation {$quotation->quotation_number} updated successfully.");
        });
    }

    public function convert(Request $request, Quotation $quotation, SalesOrderService $salesOrderService)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($quotation->team_id !== $teamId, 403);

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $items = $quotation->items->map(function ($it) {
            return [
                'product_id' => $it->product_id,
                'quantity' => (float) $it->quantity,
                'unit_price' => (float) $it->unit_price,
            ];
        })->toArray();

        $orderData = [
            'warehouse_id' => $validated['warehouse_id'],
            'customer_id' => $quotation->customer_id,
            'order_date' => now()->toDateString(),
            'discount_amount' => $quotation->discount_amount,
            'tax_amount' => $quotation->tax_amount,
            'notes' => "Converted from Quotation {$quotation->quotation_number}. " . $quotation->notes,
            'status' => 'confirmed',
        ];

        $order = $salesOrderService->createOrder($orderData, $items);
        $quotation->update([
            'status' => 'converted',
            'converted_sales_order_id' => $order->id,
        ]);

        return redirect()->route('sales.index')->with('success', "Quotation {$quotation->quotation_number} successfully converted to Sales Order {$order->order_number}.");
    }

    public function destroy(Quotation $quotation)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($quotation->team_id !== $teamId, 403);

        $quotation->items()->delete();
        $quotation->delete();

        return redirect()->back()->with('success', "Quotation deleted successfully.");
    }
}
