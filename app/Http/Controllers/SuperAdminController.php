<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SuperAdminController extends Controller
{
    /**
     * Display the Platform Super-Admin Control Plane with real-time metrics and tenant controls.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', '');
        $planFilter = $request->input('plan_id', '');

        // 1. Query Tenants with owners and subscriptions
        $query = Team::withoutGlobalScopes()
            ->with(['owner', 'subscription.plan'])
            ->withCount(['users', 'teamInvitations'])
            ->whereHas('owner', fn ($q) => $q->where('is_super_admin', false));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('legal_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($planFilter) {
            $query->whereHas('subscription', function ($sq) use ($planFilter) {
                $sq->where('plan_id', $planFilter);
            });
        }

        $tenants = $query->latest('id')->paginate(12)->withQueryString();

        $teamIds = $tenants->pluck('id')->toArray();

        $productsCounts = DB::table('products')
            ->whereIn('team_id', $teamIds)
            ->whereNull('deleted_at')
            ->groupBy('team_id')
            ->select('team_id', DB::raw('count(*) as count'))
            ->pluck('count', 'team_id');

        $warehouseCounts = DB::table('warehouses')
            ->whereIn('team_id', $teamIds)
            ->whereNull('deleted_at')
            ->groupBy('team_id')
            ->select('team_id', DB::raw('count(*) as count'))
            ->pluck('count', 'team_id');

        $salesCounts = DB::table('sales_orders')
            ->whereIn('team_id', $teamIds)
            ->groupBy('team_id')
            ->select('team_id', DB::raw('count(*) as count'))
            ->pluck('count', 'team_id');

        // Transform tenants with product, warehouse, sales counts, and subscription info
        $tenants->getCollection()->transform(function ($team) use ($productsCounts, $warehouseCounts, $salesCounts) {
            $team->products_count = $productsCounts[$team->id] ?? 0;
            $team->warehouses_count = $warehouseCounts[$team->id] ?? 0;
            $team->sales_orders_count = $salesCounts[$team->id] ?? 0;

            $sub = $team->subscription;
            $effectiveDate = ($sub?->status === 'trialing' && $sub?->trial_ends_at)
                ? $sub->trial_ends_at
                : $sub?->ends_at;

            $team->subscription_info = [
                'plan_id' => $sub?->plan_id,
                'plan_name' => $sub?->plan?->name ?? 'Trial / Custom',
                'plan_slug' => $sub?->plan?->slug ?? 'trial',
                'status' => $sub?->status ?? ($team->status === 'suspended' ? 'suspended' : 'active'),
                'billing_cycle' => $sub?->billing_cycle ?? 'monthly',
                'ends_at' => $effectiveDate ? $effectiveDate->format('Y-m-d') : null,
                'formatted_ends_at' => $effectiveDate ? $effectiveDate->format('M d, Y') : 'N/A',
                'days_remaining' => $sub?->days_remaining ?? 0,
            ];

            return $team;
        });

        return Inertia::render('Admin/Dashboard', [
            'metrics' => fn () => Cache::remember('superadmin_system_metrics', 30, function () {
                $clientTeams = Team::withoutGlobalScopes()->whereHas('owner', fn ($q) => $q->where('is_super_admin', false));
                return [
                    'total_tenants' => (clone $clientTeams)->count(),
                    'active_tenants' => (clone $clientTeams)->where('status', '!=', 'suspended')->count(),
                    'suspended_tenants' => (clone $clientTeams)->where('status', 'suspended')->count(),
                    'total_users' => User::where('is_super_admin', false)->count(),
                    'total_products' => Product::withoutGlobalScopes()->count(),
                    'total_sales' => SalesOrder::withoutGlobalScopes()->count(),
                ];
            }),
            'tenants' => $tenants,
            'plans' => fn () => Cache::remember('superadmin_active_plans', 300, fn () => Plan::where('is_active', true)->orderBy('sort_order')->get()),
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'plan_id' => $planFilter,
            ],
        ]);
    }

    /**
     * Provision and onboard a brand new tenant organization from the control plane.
     */
    public function storeTenant(Request $request, SubscriptionService $subscriptionService, AccountingService $accountingService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255|unique:users,email',
            'owner_password' => 'required|string|min:8',
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'validity_days' => 'required|integer|min:1|max:3650',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($validated, $accountingService) {
            // 1. Create Tenant Owner User
            $owner = User::create([
                'name' => $validated['owner_name'],
                'email' => strtolower(trim($validated['owner_email'])),
                'password' => Hash::make($validated['owner_password']),
                'is_super_admin' => false,
            ]);

            // 2. Create Tenant Team
            $team = Team::forceCreate([
                'user_id' => $owner->id,
                'name' => $validated['name'],
                'legal_name' => (!empty($validated['legal_name'])) ? $validated['legal_name'] : $validated['name'],
                'email' => (!empty($validated['email'])) ? $validated['email'] : $validated['owner_email'],
                'phone' => $validated['phone'] ?? null,
                'city' => $validated['city'] ?? 'Dhaka',
                'country' => $validated['country'] ?? 'Bangladesh',
                'currency_code' => strtoupper($validated['currency_code']),
                'currency_symbol' => $validated['currency_symbol'],
                'personal_team' => true,
                'status' => 'active',
            ]);

            $owner->forceFill(['current_team_id' => $team->id])->save();

            // 3. Seed Roles & Assign Owner
            RolesAndPermissionsSeeder::seedRolesForTeam($team->id);
            setPermissionsTeamId($team->id);
            $owner->assignRole('owner');

            // 4. Provision Default Warehouse Hub
            Warehouse::firstOrCreate(
                ['team_id' => $team->id, 'is_default' => true],
                [
                    'name' => 'Main Warehouse',
                    'code' => 'WH-MAIN',
                    'address' => $team->formattedAddress() ?: 'Central Logistics Hub',
                    'is_active' => true,
                ]
            );

            // 5. Seed Chart of Accounts
            $accountingService->ensureDefaultAccounts($team->id);

            // 6. Provision Subscription
            $validityDays = (int) $validated['validity_days'];
            TenantSubscription::create([
                'team_id' => $team->id,
                'plan_id' => $validated['plan_id'],
                'status' => 'active',
                'billing_cycle' => $validated['billing_cycle'],
                'starts_at' => now(),
                'ends_at' => now()->addDays($validityDays),
                'trial_ends_at' => now()->addDays(14),
            ]);

            Cache::forget('superadmin_system_metrics');

            return redirect()->back()->with('success', "Tenant organization '{$team->name}' provisioned successfully for {$owner->name}.");
        });
    }

    /**
     * Update tenant company profile, contact information, and localization.
     */
    public function updateTenant(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        $team->update([
            'name' => $validated['name'],
            'legal_name' => (!empty($validated['legal_name'])) ? $validated['legal_name'] : $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'currency_code' => strtoupper($validated['currency_code']),
            'currency_symbol' => $validated['currency_symbol'],
            'address_line1' => $validated['address_line1'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? null,
        ]);

        return redirect()->back()->with('success', "Tenant details for '{$team->name}' updated successfully.");
    }

    /**
     * Direct Super-Admin override of tenant subscription plan, expiration, and status.
     */
    public function updateSubscription(Request $request, Team $team, SubscriptionService $subscriptionService)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:active,trialing,past_due,expired,suspended',
            'billing_cycle' => 'required|in:monthly,yearly',
            'extension_days' => 'nullable|integer|min:0|max:3650',
            'custom_ends_at' => 'nullable|date',
        ]);

        $sub = $team->subscription()->first() ?? $subscriptionService->ensureTenantSubscription($team);

        // Determine current expiration date to base extensions on
        $currentExpiry = ($sub->status === 'trialing' && $sub->trial_ends_at)
            ? $sub->trial_ends_at
            : $sub->ends_at;

        $endsAt = $currentExpiry;

        if (!empty($validated['custom_ends_at'])) {
            $endsAt = Carbon::parse($validated['custom_ends_at'])->endOfDay();
        } elseif (!empty($validated['extension_days']) && (int) $validated['extension_days'] > 0) {
            $base = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry : now();
            $endsAt = $base->copy()->addDays((int) $validated['extension_days'])->endOfDay();
        }

        $updateData = [
            'plan_id' => $validated['plan_id'],
            'status' => $validated['status'],
            'billing_cycle' => $validated['billing_cycle'],
            'ends_at' => $endsAt,
        ];

        // Keep trial_ends_at synchronized whenever status is trialing
        if ($validated['status'] === 'trialing') {
            $updateData['trial_ends_at'] = $endsAt;
        }

        $sub->update($updateData);

        // Sync team suspension status
        if ($validated['status'] === 'suspended' && $team->status !== 'suspended') {
            $team->update(['status' => 'suspended']);
            Cache::forget('superadmin_system_metrics');
        } elseif ($validated['status'] === 'active' && $team->status === 'suspended') {
            $team->update(['status' => 'active']);
            Cache::forget('superadmin_system_metrics');
        }

        $planName = Plan::find($validated['plan_id'])?->name ?? 'Selected';
        return redirect()->back()->with('success', "Subscription for '{$team->name}' updated to {$planName} ({$validated['status']}).");
    }

    /**
     * Permanently remove a tenant organization with security guards.
     */
    public function destroyTenant(Request $request, Team $team)
    {
        if ($team->owner && $team->owner->is_super_admin) {
            return redirect()->back()->with('error', 'Cannot delete platform Super-Admin organization.');
        }

        $name = $team->name;

        DB::transaction(function () use ($team) {
            $team->delete();
        });

        Cache::forget('superadmin_system_metrics');

        return redirect()->back()->with('success', "Tenant organization '{$name}' has been permanently deleted.");
    }

    /**
     * Toggle tenant suspension status with immediate lockout enforcement.
     */
    public function toggleStatus(Team $team)
    {
        if ($team->status === 'suspended') {
            $team->update(['status' => 'active']);
            $msg = "Tenant '{$team->name}' has been reactivated successfully.";
        } else {
            $team->update(['status' => 'suspended']);
            $msg = "Tenant '{$team->name}' has been suspended. User access is now blocked.";
        }

        Cache::forget('superadmin_system_metrics');

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Impersonate a tenant by switching workspace context.
     */
    public function switchToTenant(Team $team)
    {
        $user = Auth::user();

        $user->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        return redirect()->route('dashboard')->with('success', "Switched workspace context to '{$team->name}'.");
    }
}
