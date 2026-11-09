<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinancialSummaryExport implements WithMultipleSheets
{
    protected int $teamId;
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;

    public function __construct(int $teamId, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $this->teamId = $teamId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        return [
            new FinancialSummaryOverviewSheet($this->teamId, $this->startDate, $this->endDate),
            new FinancialSummarySalesSheet($this->teamId, $this->startDate, $this->endDate),
            new FinancialSummaryPurchaseSheet($this->teamId, $this->startDate, $this->endDate),
            new FinancialSummaryExpenseSheet($this->teamId, $this->startDate, $this->endDate),
        ];
    }
}

class FinancialSummaryOverviewSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;

    public function __construct(int $teamId, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $this->teamId = $teamId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Executive Overview';
    }

    public function headings(): array
    {
        return ['Financial & Operations Metric', 'Value', 'Unit / Detail'];
    }

    public function collection(): Collection
    {
        // 1. Sales
        $salesQ = DB::table('sales_orders')
            ->where('team_id', $this->teamId)
            ->whereNotIn('status', ['cancelled'])
            ->whereNull('deleted_at');
        if ($this->startDate && $this->endDate) {
            $salesQ->whereBetween('order_date', [$this->startDate->toDateString(), $this->endDate->toDateString()]);
        }
        $sales = $salesQ->selectRaw("
            COUNT(*) as orders_count,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(SUM(paid_amount), 0) as total_paid,
            COALESCE(SUM(due_amount), 0) as total_due
        ")->first();

        // 2. Purchases
        $purchQ = DB::table('purchase_orders')
            ->where('team_id', $this->teamId)
            ->whereNotIn('status', ['cancelled'])
            ->whereNull('deleted_at');
        if ($this->startDate && $this->endDate) {
            $purchQ->whereBetween('order_date', [$this->startDate->toDateString(), $this->endDate->toDateString()]);
        }
        $purch = $purchQ->selectRaw("
            COUNT(*) as orders_count,
            COALESCE(SUM(total_amount), 0) as total_purchases,
            COALESCE(SUM(paid_amount), 0) as total_paid,
            COALESCE(SUM(due_amount), 0) as total_due
        ")->first();

        // 3. Expenses
        $expQ = DB::table('expenses')->where('team_id', $this->teamId);
        if ($this->startDate && $this->endDate) {
            $expQ->whereBetween('expense_date', [$this->startDate->toDateString(), $this->endDate->toDateString()]);
        }
        $exp = $expQ->selectRaw("
            COUNT(*) as vouchers_count,
            COALESCE(SUM(amount), 0) as total_expenses
        ")->first();

        $totalSales = (float) ($sales->total_sales ?? 0);
        $totalPurchases = (float) ($purch->total_purchases ?? 0);
        $totalExpenses = (float) ($exp->total_expenses ?? 0);
        $grossProfit = round($totalSales - $totalPurchases, 2);
        $netProfit = round($grossProfit - $totalExpenses, 2);
        $grossMargin = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 2) : 0;
        $netMargin = $totalSales > 0 ? round(($netProfit / $totalSales) * 100, 2) : 0;

        $periodStr = ($this->startDate && $this->endDate)
            ? "{$this->startDate->toDateString()} to {$this->endDate->toDateString()}"
            : "All Time";

        return collect([
            ['Reporting Period', $periodStr, 'Date Window'],
            ['Total Sales Volume', number_format($totalSales, 2), 'Total Invoice Value'],
            ['Total Sales Orders', number_format($sales->orders_count ?? 0), 'Orders Count'],
            ['Sales Collections (Paid)', number_format($sales->total_paid ?? 0, 2), 'Cash / Bank Inflow'],
            ['Customer Receivables (Due)', number_format($sales->total_due ?? 0, 2), 'Outstanding Due'],
            ['-----------------------------', '-----------------------------', '-----------------------------'],
            ['Total Purchases Volume', number_format($totalPurchases, 2), 'Total Procurement Value'],
            ['Total Purchase Orders', number_format($purch->orders_count ?? 0), 'PO Count'],
            ['Supplier Payments (Paid)', number_format($purch->total_paid ?? 0, 2), 'Cash / Bank Outflow'],
            ['Supplier Payables (Due)', number_format($purch->total_due ?? 0, 2), 'Outstanding Payable'],
            ['-----------------------------', '-----------------------------', '-----------------------------'],
            ['Total Operating Expenses', number_format($totalExpenses, 2), 'Operating Overhead'],
            ['Total Expense Vouchers', number_format($exp->vouchers_count ?? 0), 'Vouchers Count'],
            ['-----------------------------', '-----------------------------', '-----------------------------'],
            ['Gross Profit (Sales - Purchases)', number_format($grossProfit, 2), 'Gross Income'],
            ['Gross Margin %', "{$grossMargin}%", 'Gross Margin Ratio'],
            ['Net Profit (Gross - Expenses)', number_format($netProfit, 2), 'Operating Income'],
            ['Net Profit Margin %', "{$netMargin}%", 'Net Margin Ratio'],
            ['Net Cash Flow (Collected - Paid - Expenses)', number_format(($sales->total_paid ?? 0) - ($purch->total_paid ?? 0) - $totalExpenses, 2), 'Liquid Cash Movement'],
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4338CA'],
                ],
            ],
        ];
    }
}

class FinancialSummarySalesSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;

    public function __construct(int $teamId, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $this->teamId = $teamId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Sales Orders';
    }

    public function headings(): array
    {
        return ['Order Number', 'Date', 'Customer', 'Status', 'Payment Status', 'Total Amount', 'Paid Amount', 'Due Amount'];
    }

    public function collection(): Collection
    {
        $q = DB::table('sales_orders')
            ->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->where('sales_orders.team_id', $this->teamId)
            ->whereNotIn('sales_orders.status', ['cancelled'])
            ->whereNull('sales_orders.deleted_at')
            ->select('sales_orders.*', 'customers.name as customer_name')
            ->orderByDesc('sales_orders.order_date')
            ->orderByDesc('sales_orders.id');

        if ($this->startDate && $this->endDate) {
            $q->whereBetween('sales_orders.order_date', [$this->startDate->toDateString(), $this->endDate->toDateString()]);
        }

        return $q->get();
    }

    public function map($row): array
    {
        return [
            $row->order_number,
            $row->order_date,
            $row->customer_name ?: 'Walk-in Customer',
            ucfirst($row->status),
            ucfirst($row->payment_status ?: 'paid'),
            number_format((float) $row->total_amount, 2, '.', ''),
            number_format((float) $row->paid_amount, 2, '.', ''),
            number_format((float) $row->due_amount, 2, '.', ''),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
            ],
        ];
    }
}

class FinancialSummaryPurchaseSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;

    public function __construct(int $teamId, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $this->teamId = $teamId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Purchase Orders';
    }

    public function headings(): array
    {
        return ['PO Number', 'Date', 'Supplier', 'Status', 'Payment Status', 'Total Amount', 'Paid Amount', 'Due Amount'];
    }

    public function collection(): Collection
    {
        $q = DB::table('purchase_orders')
            ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->where('purchase_orders.team_id', $this->teamId)
            ->whereNotIn('purchase_orders.status', ['cancelled'])
            ->whereNull('purchase_orders.deleted_at')
            ->select('purchase_orders.*', 'suppliers.name as supplier_name')
            ->orderByDesc('purchase_orders.order_date')
            ->orderByDesc('purchase_orders.id');

        if ($this->startDate && $this->endDate) {
            $q->whereBetween('purchase_orders.order_date', [$this->startDate->toDateString(), $this->endDate->toDateString()]);
        }

        return $q->get();
    }

    public function map($row): array
    {
        return [
            $row->order_number,
            $row->order_date,
            $row->supplier_name ?: 'General Supplier',
            ucfirst($row->status),
            ucfirst($row->payment_status ?: 'paid'),
            number_format((float) $row->total_amount, 2, '.', ''),
            number_format((float) $row->paid_amount, 2, '.', ''),
            number_format((float) $row->due_amount, 2, '.', ''),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
            ],
        ];
    }
}

class FinancialSummaryExpenseSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;

    public function __construct(int $teamId, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $this->teamId = $teamId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Operating Expenses';
    }

    public function headings(): array
    {
        return ['Expense Number', 'Date', 'Category', 'Title / Description', 'Payee / Vendor', 'Payment Method', 'Amount'];
    }

    public function collection(): Collection
    {
        $q = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->where('expenses.team_id', $this->teamId)
            ->select('expenses.*', 'expense_categories.name as category_name')
            ->orderByDesc('expenses.expense_date')
            ->orderByDesc('expenses.id');

        if ($this->startDate && $this->endDate) {
            $q->whereBetween('expenses.expense_date', [$this->startDate->toDateString(), $this->endDate->toDateString()]);
        }

        return $q->get();
    }

    public function map($row): array
    {
        return [
            $row->expense_number,
            $row->expense_date,
            $row->category_name ?: 'General Expense',
            $row->title,
            $row->vendor_or_payee ?: '-',
            ucfirst(str_replace('_', ' ', $row->payment_method ?: 'cash')),
            number_format((float) $row->amount, 2, '.', ''),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
            ],
        ];
    }
}

