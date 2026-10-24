<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\Warehouse;
use App\Services\RecurringInvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RecurringInvoiceController extends Controller
{
    /**
     * List all recurring subscription profiles with metrics.
     */
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $frequency = $request->input('frequency', '');
        $customerId = $request->input('customer_id');

        $query = RecurringInvoice::where('team_id', $teamId)
            ->with(['customer', 'warehouse'])
            ->withCount('salesOrders')
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('subscription_number', 'like', "%{$search}%")
                        ->orWhere('profile_name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($frequency !== '', fn ($q) => $q->where('frequency', $frequency))
            ->when($customerId, fn ($q, $id) => $q->where('customer_id', $id));

        $profiles = $query->latest('id')->paginate(15)->withQueryString();

        // Calculate tenant metrics
        $allActive = RecurringInvoice::where('team_id', $teamId)->where('status', 'active')->get();
        $totalMrr = $allActive->sum(fn ($p) => $p->monthly_recurring_revenue);

        $dueToday = $allActive->filter(fn ($p) => $p->next_issue_date && $p->next_issue_date->lte(now()->endOfDay()))->count();

        $metrics = [
            'active_subscriptions' => $allActive->count(),
            'total_mrr' => round($totalMrr, 2),
            'due_today' => $dueToday,
            'total_profiles' => RecurringInvoice::where('team_id', $teamId)->count(),
        ];

        $customers = Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('RecurringInvoices/Index', [
            'profiles' => $profiles,
            'customers' => $customers,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'frequency' => $frequency,
                'customer_id' => $customerId,
            ],
        ]);
    }

    /**
     * Show form to create a new recurring invoice profile.
     */
    public function create()
    {
        $teamId = Auth::user()->current_team_id;

        $customers = Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('team_id', $teamId)
            ->where('is_active', true)
            ->select('id', 'name', 'sku', 'sale_price', 'cost_price')
            ->orderBy('name')
            ->get();

        return Inertia::render('RecurringInvoices/Create', [
            'customers' => $customers,
            'warehouses' => $warehouses,
            'products' => $products,
            'defaultWarehouseId' => $warehouses->firstWhere('is_default', true)?->id ?? $warehouses->first()?->id,
            'frequencies' => [
                ['value' => 'weekly', 'label' => 'Weekly'],
                ['value' => 'monthly', 'label' => 'Monthly'],
                ['value' => 'quarterly', 'label' => 'Quarterly (Every 3 Months)'],
                ['value' => 'semi_annual', 'label' => 'Semi-Annual (Every 6 Months)'],
                ['value' => 'yearly', 'label' => 'Yearly'],
            ],
        ]);
    }

    /**
     * Store new recurring profile.
     */
    public function store(Request $request, RecurringInvoiceService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'profile_name' => 'required|string|max:255',
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('team_id', $teamId)],
            'warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'frequency' => 'required|string|in:weekly,monthly,quarterly,semi_annual,yearly',
            'interval_count' => 'required|integer|min:1|max:12',
            'start_date' => 'required|date',
            'next_issue_date' => 'required|date|after_or_equal:start_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'total_cycles_limit' => 'nullable|integer|min:1',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'auto_send_email' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.item_name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $profileData = $validated;
        $profileData['team_id'] = $teamId;
        $profileData['created_by'] = Auth::id();
        $items = $validated['items'];
        unset($profileData['items']);

        $profile = $service->createProfile($profileData, $items);

        return redirect()->route('recurring-invoices.show', $profile->id)
            ->with('success', "Subscription profile #{$profile->subscription_number} created successfully.");
    }

    /**
     * Show details of a recurring profile and history of generated invoices.
     */
    public function show(RecurringInvoice $recurringInvoice)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($recurringInvoice->team_id !== $teamId, 403);

        $recurringInvoice->load([
            'customer',
            'warehouse',
            'creator',
            'items.product',
        ]);

        $generatedInvoices = $recurringInvoice->salesOrders()
            ->latest('id')
            ->paginate(10)
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_date' => $order->order_date?->format('Y-m-d') ?? $order->created_at->format('Y-m-d'),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'due_amount' => (float) $order->due_amount,
            ]);

        return Inertia::render('RecurringInvoices/Show', [
            'profile' => [
                'id' => $recurringInvoice->id,
                'subscription_number' => $recurringInvoice->subscription_number,
                'profile_name' => $recurringInvoice->profile_name,
                'status' => $recurringInvoice->status,
                'frequency' => $recurringInvoice->frequency,
                'interval_count' => $recurringInvoice->interval_count,
                'start_date' => $recurringInvoice->start_date?->format('Y-m-d'),
                'end_date' => $recurringInvoice->end_date?->format('Y-m-d'),
                'next_issue_date' => $recurringInvoice->next_issue_date?->format('Y-m-d'),
                'last_issued_at' => $recurringInvoice->last_issued_at?->toDateTimeString(),
                'total_cycles_limit' => $recurringInvoice->total_cycles_limit,
                'cycles_completed' => $recurringInvoice->cycles_completed,
                'subtotal' => (float) $recurringInvoice->subtotal,
                'discount_amount' => (float) $recurringInvoice->discount_amount,
                'tax_amount' => (float) $recurringInvoice->tax_amount,
                'total_amount' => (float) $recurringInvoice->total_amount,
                'monthly_recurring_revenue' => (float) $recurringInvoice->monthly_recurring_revenue,
                'payment_terms_days' => $recurringInvoice->payment_terms_days,
                'auto_send_email' => (bool) $recurringInvoice->auto_send_email,
                'notes' => $recurringInvoice->notes,
                'customer' => $recurringInvoice->customer,
                'warehouse' => $recurringInvoice->warehouse,
                'creator' => $recurringInvoice->creator?->name,
                'items' => $recurringInvoice->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ]),
                'created_at' => $recurringInvoice->created_at->format('Y-m-d H:i'),
            ],
            'generatedInvoices' => $generatedInvoices,
        ]);
    }

    /**
     * Show form to edit recurring invoice profile.
     */
    public function edit(RecurringInvoice $recurringInvoice)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($recurringInvoice->team_id !== $teamId, 403);

        $recurringInvoice->load(['items.product', 'customer', 'warehouse']);

        $customers = Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('team_id', $teamId)
            ->where('is_active', true)
            ->select('id', 'name', 'sku', 'sale_price', 'cost_price')
            ->orderBy('name')
            ->get();

        return Inertia::render('RecurringInvoices/Edit', [
            'profile' => [
                'id' => $recurringInvoice->id,
                'subscription_number' => $recurringInvoice->subscription_number,
                'profile_name' => $recurringInvoice->profile_name,
                'customer_id' => $recurringInvoice->customer_id,
                'warehouse_id' => $recurringInvoice->warehouse_id,
                'frequency' => $recurringInvoice->frequency,
                'interval_count' => $recurringInvoice->interval_count,
                'start_date' => $recurringInvoice->start_date?->format('Y-m-d'),
                'end_date' => $recurringInvoice->end_date?->format('Y-m-d'),
                'next_issue_date' => $recurringInvoice->next_issue_date?->format('Y-m-d'),
                'total_cycles_limit' => $recurringInvoice->total_cycles_limit,
                'discount_amount' => (float) $recurringInvoice->discount_amount,
                'tax_amount' => (float) $recurringInvoice->tax_amount,
                'payment_terms_days' => $recurringInvoice->payment_terms_days,
                'auto_send_email' => (bool) $recurringInvoice->auto_send_email,
                'notes' => $recurringInvoice->notes,
                'status' => $recurringInvoice->status,
                'items' => $recurringInvoice->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ]),
            ],
            'customers' => $customers,
            'warehouses' => $warehouses,
            'products' => $products,
            'frequencies' => [
                ['value' => 'weekly', 'label' => 'Weekly'],
                ['value' => 'monthly', 'label' => 'Monthly'],
                ['value' => 'quarterly', 'label' => 'Quarterly (Every 3 Months)'],
                ['value' => 'semi_annual', 'label' => 'Semi-Annual (Every 6 Months)'],
                ['value' => 'yearly', 'label' => 'Yearly'],
            ],
        ]);
    }

    /**
     * Update existing recurring profile.
     */
    public function update(Request $request, RecurringInvoice $recurringInvoice, RecurringInvoiceService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($recurringInvoice->team_id !== $teamId, 403);

        $validated = $request->validate([
            'profile_name' => 'required|string|max:255',
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('team_id', $teamId)],
            'warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'frequency' => 'required|string|in:weekly,monthly,quarterly,semi_annual,yearly',
            'interval_count' => 'required|integer|min:1|max:12',
            'start_date' => 'required|date',
            'next_issue_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'total_cycles_limit' => 'nullable|integer|min:1',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'auto_send_email' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.item_name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $profileData = $validated;
        $items = $validated['items'];
        unset($profileData['items']);

        $service->updateProfile($recurringInvoice, $profileData, $items);

        return redirect()->route('recurring-invoices.show', $recurringInvoice->id)
            ->with('success', 'Subscription profile updated successfully.');
    }

    /**
     * Trigger immediate invoice generation for the profile.
     */
    public function generateNow(RecurringInvoice $recurringInvoice, RecurringInvoiceService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($recurringInvoice->team_id !== $teamId, 403);

        if ($recurringInvoice->status !== 'active') {
            return back()->with('error', 'Cannot generate invoice for a non-active subscription.');
        }

        $order = $service->generateInvoice($recurringInvoice, now());

        return back()->with('success', "Sales Order {$order->order_number} successfully generated.");
    }

    /**
     * Pause an active profile.
     */
    public function pause(RecurringInvoice $recurringInvoice, RecurringInvoiceService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($recurringInvoice->team_id !== $teamId, 403);

        $service->toggleStatus($recurringInvoice, 'pause');

        return back()->with('success', 'Subscription profile paused.');
    }

    /**
     * Resume a paused profile.
     */
    public function resume(RecurringInvoice $recurringInvoice, RecurringInvoiceService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($recurringInvoice->team_id !== $teamId, 403);

        $service->toggleStatus($recurringInvoice, 'resume');

        return back()->with('success', 'Subscription profile resumed.');
    }

    /**
     * Cancel a profile.
     */
    public function cancel(RecurringInvoice $recurringInvoice, RecurringInvoiceService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($recurringInvoice->team_id !== $teamId, 403);

        $service->toggleStatus($recurringInvoice, 'cancel');

        return back()->with('success', 'Subscription profile cancelled.');
    }

    /**
     * Delete a profile if it has no generated sales orders.
     */
    public function destroy(RecurringInvoice $recurringInvoice)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($recurringInvoice->team_id !== $teamId, 403);

        if ($recurringInvoice->salesOrders()->count() > 0) {
            return back()->with('error', 'Cannot delete subscription profile that already has generated invoices. You can cancel it instead.');
        }

        $recurringInvoice->items()->delete();
        $recurringInvoice->delete();

        return redirect()->route('recurring-invoices.index')
            ->with('success', 'Subscription profile deleted successfully.');
    }
}
