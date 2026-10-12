<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class PdfDocumentController extends Controller
{
    /**
     * Download or stream Sales Order Invoice PDF.
     */
    public function salesInvoice(SalesOrder $order)
    {
        $user = Auth::user();
        $order->load(['customer', 'warehouse', 'items.product', 'team']);

        if (!$user->isSuperAdmin() && (!$order->team || !$user->belongsToTeam($order->team) || $order->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $pdf = PDF::loadView('pdf.sales-invoice', [
            'order' => $order,
            'team' => $order->team ?? $user->currentTeam,
        ], [], [
            'title' => "Invoice-{$order->order_number}",
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "Invoice-{$order->order_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Sales Order Warehouse Packing Slip PDF.
     */
    public function salesPackingSlip(SalesOrder $order)
    {
        $user = Auth::user();
        $order->load(['customer', 'warehouse', 'items.product', 'team']);

        if (!$user->isSuperAdmin() && (!$order->team || !$user->belongsToTeam($order->team) || $order->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $pdf = PDF::loadView('pdf.packing-slip', [
            'order' => $order,
            'team' => $order->team ?? $user->currentTeam,
        ], [], [
            'title' => "PackingSlip-{$order->order_number}",
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "PackingSlip-{$order->order_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Purchase Order Slip PDF.
     */
    public function purchaseOrder(PurchaseOrder $order)
    {
        $user = Auth::user();
        $order->load(['supplier', 'warehouse', 'items.product', 'team']);

        if (!$user->isSuperAdmin() && (!$order->team || !$user->belongsToTeam($order->team) || $order->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $pdf = PDF::loadView('pdf.purchase-order', [
            'order' => $order,
            'team' => $order->team ?? $user->currentTeam,
        ], [], [
            'title' => "PO-{$order->order_number}",
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "PO-{$order->order_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
