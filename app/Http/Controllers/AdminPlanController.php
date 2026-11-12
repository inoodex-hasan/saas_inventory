<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminPlanController extends Controller
{
    /**
     * List all subscription plans.
     */
    public function index()
    {
        $plans = Plan::withCount('subscriptions')->orderBy('sort_order')->get();

        $metrics = [
            'total_plans' => $plans->count(),
            'active_plans' => $plans->where('is_active', true)->count(),
            'total_subscribers' => $plans->sum('subscriptions_count'),
            'top_tier' => $plans->sortByDesc('price_bdt')->first()?->name ?? 'Standard',
        ];

        return Inertia::render('Admin/Plans/Index', [
            'page_title' => 'SaaS Subscription Plans Manager',
            'plans' => $plans,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Store new custom plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:plans,slug',
            'description' => 'nullable|string|max:500',
            'price_bdt' => 'required|numeric|min:0',
            'annual_price_bdt' => 'required|numeric|min:0',
            'max_products' => 'required|integer|min:-1',
            'max_warehouses' => 'required|integer|min:-1',
            'max_users' => 'required|integer|min:-1',
            'max_quotations' => 'nullable|integer|min:-1',
            'max_sales_orders' => 'nullable|integer|min:-1',
            'max_purchase_orders' => 'nullable|integer|min:-1',
            'max_projects' => 'nullable|integer|min:-1',
            'max_employees' => 'nullable|integer|min:-1',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Plan::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $plan = Plan::create($validated);
        Cache::forget('superadmin_active_plans');

        return back()->with('success', "Plan '{$plan->name}' created successfully.");
    }

    /**
     * Update existing plan.
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price_bdt' => 'required|numeric|min:0',
            'annual_price_bdt' => 'required|numeric|min:0',
            'max_products' => 'required|integer|min:-1',
            'max_warehouses' => 'required|integer|min:-1',
            'max_users' => 'required|integer|min:-1',
            'max_quotations' => 'nullable|integer|min:-1',
            'max_sales_orders' => 'nullable|integer|min:-1',
            'max_purchase_orders' => 'nullable|integer|min:-1',
            'max_projects' => 'nullable|integer|min:-1',
            'max_employees' => 'nullable|integer|min:-1',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
        ]);

        $plan->update($validated);
        Cache::forget('superadmin_active_plans');

        return back()->with('success', "Plan '{$plan->name}' updated.");
    }

    /**
     * Delete plan if unused.
     */
    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete a plan with active or assigned tenant subscriptions.']);
        }

        $name = $plan->name;
        $plan->delete();
        Cache::forget('superadmin_active_plans');

        return back()->with('success', "Plan '{$name}' deleted.");
    }
}
