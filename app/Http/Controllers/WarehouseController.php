<?php

namespace App\Http\Controllers;

use App\Models\StockLevel;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;
        $search = $request->input('search', '');

        $warehouses = Warehouse::query()
            ->where('team_id', $teamId)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%");
                });
            })
            ->withCount('stockLevels')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'total_warehouses' => Warehouse::where('team_id', $teamId)->count(),
            'active_warehouses' => Warehouse::where('team_id', $teamId)->where('is_active', true)->count(),
            'total_units' => (float) StockLevel::where('team_id', $teamId)->sum('quantity'),
            'total_valuation' => (float) (StockLevel::where('stock_levels.team_id', $teamId)
                ->join('products', 'products.id', '=', 'stock_levels.product_id')
                ->selectRaw('SUM(stock_levels.quantity * products.cost_price) as total_val')
                ->value('total_val') ?: 0),
        ];

        return Inertia::render('Warehouses/Index', [
            'warehouses' => $warehouses,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $isFirst = Warehouse::where('team_id', $teamId)->count() === 0;

        if (!empty($validated['is_default'])) {
            Warehouse::where('team_id', $teamId)->update(['is_default' => false]);
        }

        $validated['team_id'] = $teamId;
        $validated['is_default'] = ($validated['is_default'] ?? false) || $isFirst;
        $validated['is_active'] = $validated['is_active'] ?? true;

        Warehouse::create($validated);

        return redirect()->back()->with('success', 'Warehouse created successfully.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($warehouse->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            Warehouse::where('team_id', $teamId)->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }

        $warehouse->update($validated);

        return redirect()->back()->with('success', 'Warehouse updated successfully.');
    }

    public function setDefault(Warehouse $warehouse)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($warehouse->team_id !== $teamId, 403);

        Warehouse::where('team_id', $teamId)->update(['is_default' => false]);
        $warehouse->update(['is_default' => true]);

        return redirect()->back()->with('success', "{$warehouse->name} set as default warehouse.");
    }

    public function destroy(Warehouse $warehouse)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($warehouse->team_id !== $teamId, 403);

        if ($warehouse->is_default && Warehouse::where('team_id', $teamId)->count() > 1) {
            return redirect()->back()->with('error', 'Please set another warehouse as default before deleting this one.');
        }

        $warehouse->delete();

        return redirect()->back()->with('success', 'Warehouse deleted successfully.');
    }

    public function inspect(Warehouse $warehouse)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($warehouse->team_id !== $teamId, 403);

        $stockLevels = StockLevel::with('product')
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($level) {
                if (!$level->product) {
                    return null;
                }
                $onHand = (float) $level->quantity;
                $reserved = (float) $level->reserved_quantity;
                $available = max(0, $onHand - $reserved);
                $cost = (float) $level->product->cost_price;

                return [
                    'id' => $level->id,
                    'product_name' => $level->product->name,
                    'sku' => $level->product->sku,
                    'quantity_on_hand' => $onHand,
                    'quantity_reserved' => $reserved,
                    'quantity_available' => $available,
                    'cost_price' => $cost,
                    'total_value' => $onHand * $cost,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'warehouse' => $warehouse,
            'stocks' => $stockLevels,
        ]);
    }
}
