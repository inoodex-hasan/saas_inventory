<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

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
                'invoice_bg_path' => $team->invoice_bg_path,
                'invoice_bg_url' => $team->getInvoiceBgUrl(),
                'report_bg_path' => $team->report_bg_path,
                'report_bg_url' => $team->getReportBgUrl(),
                'invoice_margin_top' => (int) ($team->invoice_margin_top ?? 48),
                'invoice_margin_bottom' => (int) ($team->invoice_margin_bottom ?? 25),
                'report_margin_top' => (int) ($team->report_margin_top ?? 42),
                'report_margin_bottom' => (int) ($team->report_margin_bottom ?? 25),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            abort(403, 'No active tenant workspace selected.');
        }

        if (!$user->isSuperAdmin() && !$user->ownsTeam($team) && !$user->hasTeamRole($team, 'admin')) {
            abort(403, 'Only organization owners or administrators can update company settings.');
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
            'invoice_bg' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'report_bg' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'invoice_margin_top' => 'nullable|integer|min:0|max:150',
            'invoice_margin_bottom' => 'nullable|integer|min:0|max:150',
            'report_margin_top' => 'nullable|integer|min:0|max:150',
            'report_margin_bottom' => 'nullable|integer|min:0|max:150',
        ]);

        // Handle Invoice Background Upload
        if ($request->hasFile('invoice_bg')) {
            if ($team->invoice_bg_path) {
                try {
                    if (Storage::disk('public')->exists($team->invoice_bg_path)) {
                        Storage::disk('public')->delete($team->invoice_bg_path);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Failed to delete previous invoice background '{$team->invoice_bg_path}': " . $e->getMessage());
                }
            }
            $validated['invoice_bg_path'] = $request->file('invoice_bg')->store("tenants/{$team->id}/letterheads", 'public');
        }

        // Handle Report Background Upload
        if ($request->hasFile('report_bg')) {
            if ($team->report_bg_path) {
                try {
                    if (Storage::disk('public')->exists($team->report_bg_path)) {
                        Storage::disk('public')->delete($team->report_bg_path);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Failed to delete previous report background '{$team->report_bg_path}': " . $e->getMessage());
                }
            }
            $validated['report_bg_path'] = $request->file('report_bg')->store("tenants/{$team->id}/letterheads", 'public');
        }

        $team->update($validated);

        return redirect()->back()->with('success', 'Company profile, branding, and print letterhead settings updated successfully.');
    }

    /**
     * Remove the custom invoice letterhead background.
     */
    public function removeInvoiceBg(Request $request)
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            abort(403, 'No active tenant workspace selected.');
        }

        if (!$user->isSuperAdmin() && !$user->ownsTeam($team) && !$user->hasTeamRole($team, 'admin')) {
            abort(403, 'Only organization owners or administrators can modify branding assets.');
        }

        if ($team->invoice_bg_path) {
            try {
                if (Storage::disk('public')->exists($team->invoice_bg_path)) {
                    Storage::disk('public')->delete($team->invoice_bg_path);
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to delete invoice background '{$team->invoice_bg_path}': " . $e->getMessage());
            }
        }

        $team->update(['invoice_bg_path' => null]);

        return redirect()->back()->with('success', 'Invoice letterhead background removed. Digital header restored.');
    }

    /**
     * Remove the custom report letterhead background.
     */
    public function removeReportBg(Request $request)
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            abort(403, 'No active tenant workspace selected.');
        }

        if (!$user->isSuperAdmin() && !$user->ownsTeam($team) && !$user->hasTeamRole($team, 'admin')) {
            abort(403, 'Only organization owners or administrators can modify branding assets.');
        }

        if ($team->report_bg_path) {
            try {
                if (Storage::disk('public')->exists($team->report_bg_path)) {
                    Storage::disk('public')->delete($team->report_bg_path);
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to delete report background '{$team->report_bg_path}': " . $e->getMessage());
            }
        }

        $team->update(['report_bg_path' => null]);

        return redirect()->back()->with('success', 'Report letterhead background removed. Digital header restored.');
    }

    /**
     * Stream a live sample invoice preview with current background & margin settings.
     */
    public function previewInvoiceBg(Request $request)
    {
        $team = Auth::user()->currentTeam;

        if (!$team) {
            abort(403, 'No active tenant workspace selected.');
        }

        $sampleOrder = new SalesOrder([
            'order_number' => ($team->invoice_prefix ?? 'INV-') . 'SAMPLE-001',
            'order_date' => now(),
            'payment_status' => 'paid',
            'subtotal' => 2500.00,
            'tax_amount' => 125.00,
            'discount_amount' => 50.00,
            'shipping_cost' => 60.00,
            'grand_total' => 2635.00,
            'paid_amount' => 2635.00,
            'due_amount' => 0.00,
            'notes' => 'This is a live layout preview using your uploaded invoice letterhead and margin offsets.',
        ]);

        $sampleOrder->setRelation('customer', new Customer([
            'name' => 'Acme Corporation Ltd.',
            'phone' => '+880 1712-345678',
            'email' => 'procurement@acme.example.com',
            'address' => 'Gulshan Avenue, Dhaka 1212',
            'tax_number' => 'VAT-99887766',
        ]));

        $sampleOrder->setRelation('warehouse', new Warehouse([
            'name' => 'Main Logistics Hub',
            'code' => 'WH-01',
        ]));

        $item1 = new SalesOrderItem([
            'quantity' => 2,
            'unit_price' => 750.00,
            'subtotal' => 1500.00,
        ]);
        $item1->setRelation('product', new Product([
            'name' => 'Sample Enterprise Product Alpha',
            'sku' => 'SKU-SAMPLE-A',
            'unit' => 'pcs',
        ]));

        $item2 = new SalesOrderItem([
            'quantity' => 1,
            'unit_price' => 1000.00,
            'subtotal' => 1000.00,
        ]);
        $item2->setRelation('product', new Product([
            'name' => 'Sample Enterprise Product Beta',
            'sku' => 'SKU-SAMPLE-B',
            'unit' => 'pcs',
        ]));

        $sampleOrder->setRelation('items', collect([$item1, $item2]));
        $sampleOrder->setRelation('team', $team);

        $marginTop = $team->invoice_bg_path ? ($team->invoice_margin_top ?? 48) : 12;
        $marginBottom = $team->invoice_bg_path ? ($team->invoice_margin_bottom ?? 25) : 12;

        $pdf = PDF::loadView('pdf.sales-invoice', [
            'order' => $sampleOrder,
            'team' => $team,
        ], [], [
            'title' => "Invoice-Preview-{$sampleOrder->order_number}",
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="sample-invoice-preview.pdf"',
        ]);
    }

    /**
     * Stream a live sample financial report preview with current background & margin settings.
     */
    public function previewReportBg(Request $request)
    {
        $team = Auth::user()->currentTeam;

        if (!$team) {
            abort(403, 'No active tenant workspace selected.');
        }

        $sampleReport = [
            'as_of_date' => now()->toDateString(),
            'is_balanced' => true,
            'total_debit' => 145000.00,
            'total_credit' => 145000.00,
            'rows' => [
                ['account_code' => '1010', 'name' => 'Cash in Hand', 'type' => 'Asset', 'debit' => 25000.00, 'credit' => 0.00],
                ['account_code' => '1020', 'name' => 'Primary Operating Bank Account', 'type' => 'Asset', 'debit' => 75000.00, 'credit' => 0.00],
                ['account_code' => '1050', 'name' => 'Accounts Receivable (Trade Debtors)', 'type' => 'Asset', 'debit' => 45000.00, 'credit' => 0.00],
                ['account_code' => '2010', 'name' => 'Accounts Payable (Suppliers)', 'type' => 'Liability', 'debit' => 0.00, 'credit' => 35000.00],
                ['account_code' => '3010', 'name' => 'Owner Capital & Equity', 'type' => 'Equity', 'debit' => 0.00, 'credit' => 110000.00],
            ],
        ];

        $marginTop = $team->report_bg_path ? ($team->report_margin_top ?? 42) : 12;
        $marginBottom = $team->report_bg_path ? ($team->report_margin_bottom ?? 25) : 12;

        $pdf = PDF::loadView('pdf.trial-balance', [
            'report' => $sampleReport,
            'team' => $team,
        ], [], [
            'title' => "Trial-Balance-Preview",
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="sample-report-preview.pdf"',
        ]);
    }
}
