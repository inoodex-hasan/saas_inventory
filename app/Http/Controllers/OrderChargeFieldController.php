<?php

namespace App\Http\Controllers;

use App\Models\OrderChargeField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderChargeFieldController extends Controller
{
    /**
     * Display a listing of custom charge fields for the tenant.
     */
    public function index(Request $request): Response
    {
        $teamId = Auth::user()->current_team_id;

        $fields = OrderChargeField::where('team_id', $teamId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Settings/OrderCharges', [
            'fields' => $fields,
        ]);
    }

    /**
     * Store a newly created charge field.
     */
    public function store(Request $request): RedirectResponse
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('order_charge_fields', 'name')->where('team_id', $teamId)],
            'applies_to' => ['required', Rule::in(['sales', 'purchases', 'both'])],
            'type' => ['required', Rule::in(['fixed', 'percentage'])],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['nullable', 'boolean'],
            'show_in_pos' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['team_id'] = $teamId;
        $validated['default_amount'] = (float) ($validated['default_amount'] ?? 0);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_taxable'] = (bool) ($validated['is_taxable'] ?? false);
        $validated['show_in_pos'] = (bool) ($validated['show_in_pos'] ?? true);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        OrderChargeField::create($validated);

        return redirect()->back()->with('success', "Charge field '{$validated['name']}' created successfully.");
    }

    /**
     * Update an existing charge field.
     */
    public function update(Request $request, OrderChargeField $chargeField): RedirectResponse
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($chargeField->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('order_charge_fields', 'name')->where('team_id', $teamId)->ignore($chargeField->id)],
            'applies_to' => ['required', Rule::in(['sales', 'purchases', 'both'])],
            'type' => ['required', Rule::in(['fixed', 'percentage'])],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['nullable', 'boolean'],
            'show_in_pos' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['default_amount'] = (float) ($validated['default_amount'] ?? 0);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_taxable'] = (bool) ($validated['is_taxable'] ?? false);
        $validated['show_in_pos'] = (bool) ($validated['show_in_pos'] ?? true);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $chargeField->update($validated);

        return redirect()->back()->with('success', "Charge field '{$chargeField->name}' updated successfully.");
    }

    /**
     * Remove the specified charge field.
     */
    public function destroy(OrderChargeField $chargeField): RedirectResponse
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($chargeField->team_id !== $teamId, 403);

        $name = $chargeField->name;
        $chargeField->delete();

        return redirect()->back()->with('success', "Charge field '{$name}' deleted.");
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(OrderChargeField $chargeField): RedirectResponse
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($chargeField->team_id !== $teamId, 403);

        $chargeField->update([
            'is_active' => !$chargeField->is_active,
        ]);

        $status = $chargeField->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Charge field '{$chargeField->name}' {$status}.");
    }

    /**
     * API helper to fetch active charge fields for order creation.
     */
    public function apiActive(Request $request): JsonResponse
    {
        $teamId = Auth::user()->current_team_id;
        $context = $request->query('context', 'sales'); // 'sales', 'purchases', 'pos'

        $query = OrderChargeField::where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($context === 'purchases') {
            $query->whereIn('applies_to', ['purchases', 'both']);
        } elseif ($context === 'pos') {
            $query->whereIn('applies_to', ['sales', 'both'])->where('show_in_pos', true);
        } else {
            $query->whereIn('applies_to', ['sales', 'both']);
        }

        return response()->json([
            'fields' => $query->get(),
        ]);
    }
}
