<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SuperAdminController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', '');

        // 1. Platform-wide System Metrics (exclude super admin teams)
        $clientTeams = Team::withoutGlobalScopes()->whereHas('owner', fn ($q) => $q->where('is_super_admin', false));
        $totalTenants = (clone $clientTeams)->count();
        $activeTenants = (clone $clientTeams)->where('status', '!=', 'suspended')->count();
        $suspendedTenants = (clone $clientTeams)->where('status', 'suspended')->count();
        $totalUsers = User::where('is_super_admin', false)->count();
        $totalProducts = Product::withoutGlobalScopes()->count();
        $totalSales = SalesOrder::withoutGlobalScopes()->count();

        // 2. Query Tenants
        $query = Team::withoutGlobalScopes()
            ->with(['owner'])
            ->withCount(['users', 'teamInvitations'])
            ->whereHas('owner', fn ($q) => $q->where('is_super_admin', false));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('legal_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
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

        // Transform tenants with product and warehouse counts
        $tenants->getCollection()->transform(function ($team) use ($productsCounts, $warehouseCounts) {
            $team->products_count = $productsCounts[$team->id] ?? 0;
            $team->warehouses_count = $warehouseCounts[$team->id] ?? 0;
            return $team;
        });

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'total_users' => $totalUsers,
                'total_products' => $totalProducts,
                'total_sales' => $totalSales,
            ],
            'tenants' => $tenants,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
            ],
        ]);
    }

    public function toggleStatus(Team $team)
    {
        if ($team->status === 'suspended') {
            $team->update(['status' => 'active']);
            $msg = "Tenant '{$team->name}' has been reactivated successfully.";
        } else {
            $team->update(['status' => 'suspended']);
            $msg = "Tenant '{$team->name}' has been suspended. User access is now blocked.";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function switchToTenant(Team $team)
    {
        $user = Auth::user();

        $user->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        return redirect()->route('dashboard')->with('success', "Switched workspace context to '{$team->name}'.");
    }
}
