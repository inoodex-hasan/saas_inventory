<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminPlanController extends Controller
{
    /**
     * List all subscription plans.
     */
    public function index(SubscriptionService $subscriptionService)
    {
        $subscriptionService->ensureDefaultPlans();

        $plans = Plan::withCount('subscriptions')->orderBy('sort_order')->get();

        return Inertia::render('Admin/Plans/Index', [
            'page_title' => 'SaaS Subscription Plans Manager',
            'plans' => $plans,
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
            'max_products' => 'required|integer',
            'max_warehouses' => 'required|integer',
            'max_users' => 'required|integer',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $plan = Plan::create($validated);

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
            'max_products' => 'required|integer',
            'max_warehouses' => 'required|integer',
            'max_users' => 'required|integer',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
        ]);

        $plan->update($validated);

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

        return back()->with('success', "Plan '{$name}' deleted.");
    }
}
