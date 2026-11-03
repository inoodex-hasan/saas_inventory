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

        $team = $order->team ?? $user->currentTeam;
        $marginTop = $team->invoice_bg_path ? ($team->invoice_margin_top ?? 48) : 12;
        $marginBottom = $team->invoice_bg_path ? ($team->invoice_margin_bottom ?? 25) : 12;

        $pdf = PDF::loadView('pdf.sales-invoice', [
            'order' => $order,
            'team' => $team,
        ], [], [
            'title' => "Invoice-{$order->order_number}",
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
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

    /**
     * Download or stream 80mm POS Thermal Slip PDF.
     */
    public function posSlip(Request $request, SalesOrder $order)
    {
        $user = Auth::user();
        $order->load(['customer', 'warehouse', 'items.product', 'team']);

        if (!$user->isSuperAdmin() && (!$order->team || !$user->belongsToTeam($order->team) || $order->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $team = $order->team ?? $user->currentTeam;

        // Instant HTML Preview & 1-Click Print Mode (bypasses heavy browser PDFium plugin)
        if ($request->has('preview') || $request->has('html')) {
            return view('pdf.pos-slip-preview', compact('order', 'team'));
        }

        $itemCount = $order->items->count();
        $slipHeight = max(160, 115 + ($itemCount * 13) + (!empty($order->notes) ? 15 : 0));

        $pdf = PDF::loadView('pdf.pos-slip', [
            'order' => $order,
            'team' => $order->team ?? $user->currentTeam,
        ], [], [
            'format' => [80, $slipHeight],
            'title' => "POS-{$order->order_number}",
            'margin_top' => 3,
            'margin_bottom' => 3,
            'margin_left' => 3,
            'margin_right' => 3,
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
        ]);

        $content = $pdf->output();
        $filename = "POS-{$order->order_number}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
            'Content-Length' => strlen($content),
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=120, must-revalidate',
        ]);
    }

    /**
     * Download or stream Money Receipt Voucher PDF.
     */
    public function moneyReceipt(SalesOrder $order)
    {
        $user = Auth::user();
        $order->load(['customer', 'warehouse', 'items.product', 'team']);

        if (!$user->isSuperAdmin() && (!$order->team || !$user->belongsToTeam($order->team) || $order->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $pdf = PDF::loadView('pdf.money-receipt', [
            'order' => $order,
            'team' => $order->team ?? $user->currentTeam,
        ], [], [
            'format' => 'A5',
            'orientation' => 'L',
            'title' => "Receipt-{$order->order_number}",
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);

        $filename = "MoneyReceipt-{$order->order_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Quotation PDF.
     */
    public function quotation(\App\Models\Quotation $quotation)
    {
        $user = Auth::user();
        $quotation->load(['customer', 'items.product', 'team']);

        if (!$user->isSuperAdmin() && (!$quotation->team || !$user->belongsToTeam($quotation->team) || $quotation->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $pdf = PDF::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'team' => $quotation->team ?? $user->currentTeam,
        ], [], [
            'title' => "Quote-{$quotation->quotation_number}",
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "Quotation-{$quotation->quotation_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Delivery Challan PDF.
     */
    public function deliveryChallan(\App\Models\DeliveryChallan $challan)
    {
        $user = Auth::user();
        $challan->load(['customer', 'warehouse', 'salesOrder', 'items.product', 'team']);

        if (!$user->isSuperAdmin() && (!$challan->team || !$user->belongsToTeam($challan->team) || $challan->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $pdf = PDF::loadView('pdf.delivery-challan', [
            'challan' => $challan,
            'team' => $challan->team ?? $user->currentTeam,
        ], [], [
            'title' => "Challan-{$challan->challan_number}",
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "DeliveryChallan-{$challan->challan_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Vendor Payment Voucher PDF.
     */
    public function vendorVoucher(PurchaseOrder $order)
    {
        $user = Auth::user();
        $order->load(['supplier', 'warehouse', 'team']);

        if (!$user->isSuperAdmin() && (!$order->team || !$user->belongsToTeam($order->team) || $order->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $pdf = PDF::loadView('pdf.vendor-voucher', [
            'order' => $order,
            'team' => $order->team ?? $user->currentTeam,
        ], [], [
            'format' => 'A5',
            'orientation' => 'L',
            'title' => "VendorVoucher-{$order->order_number}",
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);

        $filename = "VendorVoucher-{$order->order_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Credit Note / Return Voucher PDF.
     */
    public function creditNote(\App\Models\ProductReturn $return)
    {
        $user = Auth::user();
        $return->load(['customer', 'warehouse', 'salesOrder', 'items.product', 'team']);

        if (!$user->isSuperAdmin() && (!$return->team || !$user->belongsToTeam($return->team) || $return->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $team = $return->team ?? $user->currentTeam;

        $pdf = PDF::loadView('pdf.credit-note', [
            'return' => $return,
            'team' => $team,
        ], [], [
            'title' => "CreditNote-{$return->return_number}",
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "CreditNote-{$return->return_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Service Job Intake Token / Drop-off Slip PDF.
     */
    public function serviceToken(\App\Models\ServiceTicket $ticket)
    {
        $user = Auth::user();
        $ticket->load(['customer', 'technician', 'team']);

        if (!$user->isSuperAdmin() && (!$ticket->team || !$user->belongsToTeam($ticket->team) || $ticket->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $team = $ticket->team ?? $user->currentTeam;

        $pdf = PDF::loadView('pdf.service-token', [
            'ticket' => $ticket,
            'team' => $team,
        ], [], [
            'format' => 'A5',
            'orientation' => 'P',
            'title' => "ServiceToken-{$ticket->ticket_number}",
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);

        $filename = "ServiceToken-{$ticket->ticket_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Service Job Tax Invoice PDF.
     */
    public function serviceInvoice(\App\Models\ServiceTicket $ticket)
    {
        $user = Auth::user();
        $ticket->load(['customer', 'technician', 'items.product', 'team']);

        if (!$user->isSuperAdmin() && (!$ticket->team || !$user->belongsToTeam($ticket->team) || $ticket->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $team = $ticket->team ?? $user->currentTeam;

        $pdf = PDF::loadView('pdf.service-invoice', [
            'ticket' => $ticket,
            'team' => $team,
        ], [], [
            'title' => "ServiceInvoice-{$ticket->ticket_number}",
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "ServiceInvoice-{$ticket->ticket_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Project Billing & Cost Statement PDF.
     */
    public function projectBill(\App\Models\Project $project)
    {
        $user = Auth::user();
        $project->load(['client', 'items.product', 'costs.category', 'bills', 'team', 'creator']);

        if (!$user->isSuperAdmin() && (!$project->team || !$user->belongsToTeam($project->team) || $project->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $team = $project->team ?? $user->currentTeam;

        $pdf = PDF::loadView('pdf.project-bill', [
            'project' => $project,
            'team' => $team,
        ], [], [
            'title' => "ProjectBill-{$project->project_code}",
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "ProjectBill-{$project->project_code}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Employee Salary Slip PDF.
     */
    public function salarySlip(\App\Models\Payroll $payroll)
    {
        $user = Auth::user();
        $payroll->load(['employee', 'team', 'creator']);

        if (!$user->isSuperAdmin() && (!$payroll->team || !$user->belongsToTeam($payroll->team) || $payroll->team_id !== $user->current_team_id)) {
            abort(403, 'Unauthorized access to tenant document.');
        }

        $team = $payroll->team ?? $user->currentTeam;

        $pdf = PDF::loadView('pdf.salary-slip', [
            'payroll' => $payroll,
            'team' => $team,
        ], [], [
            'title' => "SalarySlip-{$payroll->payroll_number}",
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "SalarySlip-{$payroll->payroll_number}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Trial Balance Statement PDF.
     */
    public function trialBalancePdf(Request $request, \App\Services\AccountingService $accountingService)
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        $asOfDate = $request->input('as_of_date', now()->toDateString());

        $data = $accountingService->getTrialBalance($team->id, $asOfDate);

        $marginTop = $team->report_bg_path ? ($team->report_margin_top ?? 42) : 12;
        $marginBottom = $team->report_bg_path ? ($team->report_margin_bottom ?? 25) : 12;

        $pdf = PDF::loadView('pdf.trial-balance', [
            'report' => $data,
            'team' => $team,
        ], [], [
            'title' => "TrialBalance-{$asOfDate}",
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "TrialBalance-{$asOfDate}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Profit & Loss Statement PDF.
     */
    public function profitAndLossPdf(Request $request, \App\Services\AccountingService $accountingService)
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $data = $accountingService->getProfitAndLoss($team->id, $startDate, $endDate);

        $marginTop = $team->report_bg_path ? ($team->report_margin_top ?? 42) : 12;
        $marginBottom = $team->report_bg_path ? ($team->report_margin_bottom ?? 25) : 12;

        $pdf = PDF::loadView('pdf.profit-and-loss', [
            'report' => $data,
            'team' => $team,
        ], [], [
            'title' => "ProfitAndLoss-{$startDate}-to-{$endDate}",
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "ProfitAndLoss-{$startDate}-to-{$endDate}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download or stream Balance Sheet PDF.
     */
    public function balanceSheetPdf(Request $request, \App\Services\AccountingService $accountingService)
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        $asOfDate = $request->input('as_of_date', now()->toDateString());

        $data = $accountingService->getBalanceSheet($team->id, $asOfDate);

        $marginTop = $team->report_bg_path ? ($team->report_margin_top ?? 42) : 12;
        $marginBottom = $team->report_bg_path ? ($team->report_margin_bottom ?? 25) : 12;

        $pdf = PDF::loadView('pdf.balance-sheet', [
            'report' => $data,
            'team' => $team,
        ], [], [
            'title' => "BalanceSheet-{$asOfDate}",
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $filename = "BalanceSheet-{$asOfDate}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}



