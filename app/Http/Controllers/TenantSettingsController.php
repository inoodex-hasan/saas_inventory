<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TenantSettingsController extends Controller
{
    public function index(Request $request)
    {
        $team = Auth::user()->currentTeam;

        if (!$team) {
            abort(403, 'No active tenant workspace selected.');
        }

        return Inertia::render('Settings/Company', [
            'settings' => [
                'name' => $team->name ?? '',
                'legal_name' => $team->legal_name ?? '',
                'email' => $team->email ?? '',
                'phone' => $team->phone ?? '',
                'tax_id' => $team->tax_id ?? '',
                'address_line1' => $team->address_line1 ?? '',
                'address_line2' => $team->address_line2 ?? '',
                'city' => $team->city ?? '',
                'state' => $team->state ?? '',
                'postal_code' => $team->postal_code ?? '',
                'country' => $team->country ?? '',
                'currency_symbol' => $team->currency_symbol ?? '৳',
                'currency_code' => $team->currency_code ?? 'BDT',
                'invoice_prefix' => $team->invoice_prefix ?? 'INV-',
                'po_prefix' => $team->po_prefix ?? 'PO-',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $team = Auth::user()->currentTeam;

        if (!$team) {
            abort(403, 'No active tenant workspace selected.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:100',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:30',
            'country' => 'nullable|string|max:100',
            'currency_symbol' => 'required|string|max:10',
            'currency_code' => 'required|string|max:10',
            'invoice_prefix' => 'required|string|max:20',
            'po_prefix' => 'required|string|max:20',
        ]);

        $team->update($validated);

        return redirect()->back()->with('success', 'Company profile, branding, and localization settings updated successfully.');
    }
}
