<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SuperAdminDashboard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = ''; // all, active, suspended, trial

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $teamId): void
    {
        $team = Team::withoutGlobalScopes()->findOrFail($teamId);

        if ($team->status === 'suspended') {
            $team->update(['status' => 'active']);
            session()->flash('success', "Tenant '{$team->name}' has been reactivated successfully.");
        } else {
            $team->update(['status' => 'suspended']);
            session()->flash('success', "Tenant '{$team->name}' has been suspended. User access is now blocked.");
        }
    }

    public function switchToTenant(int $teamId): void
    {
        $team = Team::withoutGlobalScopes()->findOrFail($teamId);
        $user = Auth::user();

        // Switch active team for the super admin
        $user->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        session()->flash('success', "Switched workspace context to '{$team->name}'.");
        $this->redirect(route('dashboard'));
    }

    public function render()
    {
        // 1. Platform-wide System Metrics (exclude super admin teams)
        $clientTeams = Team::withoutGlobalScopes()->whereHas('owner', fn ($q) => $q->where('is_super_admin', false));
        $totalTenants    = (clone $clientTeams)->count();
        $activeTenants   = (clone $clientTeams)->where('status', '!=', 'suspended')->count();
        $suspendedTenants = (clone $clientTeams)->where('status', 'suspended')->count();
        $totalUsers      = User::where('is_super_admin', false)->count();
        $totalProducts   = Product::withoutGlobalScopes()->count();
        $totalSales      = SalesOrder::withoutGlobalScopes()->count();

        // 2. Query Tenants with aggregated counts — exclude super admin teams
        $query = Team::withoutGlobalScopes()
            ->with(['owner'])
            ->withCount(['users', 'teamInvitations'])
            ->whereHas('owner', fn ($q) => $q->where('is_super_admin', false));

        if ($this->search) {
            $search = '%' . $this->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('legal_name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhereHas('owner', function ($oq) use ($search) {
                      $oq->where('name', 'like', $search)
                         ->orWhere('email', 'like', $search);
                  });
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $tenants = $query->latest('id')->paginate(12);

        // Preload counts per team for products and warehouses
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

        return view('livewire.super-admin-dashboard', [
            'totalTenants' => $totalTenants,
            'activeTenants' => $activeTenants,
            'suspendedTenants' => $suspendedTenants,
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalSales' => $totalSales,
            'tenants' => $tenants,
            'productsCounts' => $productsCounts,
            'warehouseCounts' => $warehouseCounts,
        ])->layout('layouts.app');
    }
}
