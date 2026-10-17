<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;
        $search = $request->input('search', '');
        $status = $request->input('status', '');

        $vendors = Supplier::where('team_id', $teamId)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('contact_name', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->withCount('purchaseOrders')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total_vendors' => Supplier::where('team_id', $teamId)->count(),
            'active_vendors' => Supplier::where('team_id', $teamId)->where('is_active', true)->count(),
            'pending_orders' => PurchaseOrder::where('team_id', $teamId)
                ->whereIn('status', ['draft', 'ordered', 'partially_received'])
                ->count(),
            'total_payables' => (float) PurchaseOrder::where('team_id', $teamId)->sum('due_amount'),
        ];

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'tax_number' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'The vendor / supplier name is required.',
            'phone.required' => 'The vendor phone / mobile number is required.',
            'address.required' => 'The vendor address is required.',
        ]);

        $validated['team_id'] = $teamId;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $vendor = Supplier::create($validated);

        if ($request->wantsJson()) {
            return response()->json($vendor);
        }

        return redirect()->back()->with('success', "Vendor '{$vendor->name}' created successfully.");
    }

    public function update(Request $request, Supplier $vendor)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($vendor->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'tax_number' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'The vendor / supplier name is required.',
            'phone.required' => 'The vendor phone / mobile number is required.',
            'address.required' => 'The vendor address is required.',
        ]);

        $vendor->update($validated);

        return redirect()->back()->with('success', "Vendor '{$vendor->name}' updated successfully.");
    }

    public function toggleActive(Supplier $vendor)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($vendor->team_id !== $teamId, 403);

        $vendor->update(['is_active' => !$vendor->is_active]);

        $statusStr = $vendor->is_active ? 'active' : 'inactive';
        return redirect()->back()->with('success', "Vendor marked as {$statusStr}.");
    }

    public function destroy(Supplier $vendor)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($vendor->team_id !== $teamId, 403);

        if ($vendor->purchaseOrders()->exists()) {
            return redirect()->back()->with('error', "Cannot delete vendor '{$vendor->name}' because purchase orders are associated with them.");
        }

        $vendor->delete();

        return redirect()->back()->with('success', "Vendor '{$vendor->name}' deleted successfully.");
    }
}
