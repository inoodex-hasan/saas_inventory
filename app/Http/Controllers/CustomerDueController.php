<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\SalesOrder;
use App\Services\FinancialAccountService;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerDueController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $filter = $request->input('filter', 'all_with_dues'); // all_with_dues, fully_paid, all

        $customers = Customer::where('team_id', $teamId)
            ->with(['salesOrders' => function ($q) {
                $q->where('status', '!=', 'cancelled')->latest('id');
            }])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->get()
            ->map(function ($c) {
                $totalBilled = (float) $c->salesOrders->sum('total_amount');
                $totalPaid = (float) $c->salesOrders->sum('paid_amount');
                $totalDue = (float) $c->salesOrders->sum('due_amount');
                $dueOrders = $c->salesOrders->where('due_amount', '>', 0)->values();

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'email' => $c->email,
                    'address' => $c->address,
                    'total_billed' => $totalBilled,
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                    'total_due_formatted' => number_format($totalDue, 2),
                    'due_orders' => $dueOrders,
                ];
            });

        if ($filter === 'all_with_dues') {
            $customers = $customers->filter(fn ($c) => $c['total_due'] > 0)->values();
        } elseif ($filter === 'fully_paid') {
            $customers = $customers->filter(fn ($c) => $c['total_due'] <= 0 && $c['total_billed'] > 0)->values();
        }

        $metrics = [
            'total_receivables' => (float) SalesOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('due_amount'),
            'total_collections' => (float) SalesOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('paid_amount'),
            'total_billed' => (float) SalesOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'customers_with_due' => Customer::where('team_id', $teamId)
                ->whereHas('salesOrders', fn ($q) => $q->where('due_amount', '>', 0)->where('status', '!=', 'cancelled'))
                ->count(),
        ];

        $financialAccounts = app(FinancialAccountService::class)->getActiveAccountsForTeam($teamId);

        return Inertia::render('Dues/Index', [
            'page_title' => 'Customer Due Ledger',
            'customers' => $customers,
            'metrics' => $metrics,
            'financialAccounts' => $financialAccounts,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
        ]);
    }

    public function pay(Request $request, SalesOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;

        if (!$request->filled('financial_account_id')) {
            $defaultAccount = FinancialAccount::where('team_id', $teamId)->where('is_active', true)->first();
            if (!$defaultAccount) {
                $defaultAccount = FinancialAccount::create([
                    'team_id' => $teamId,
                    'name' => 'Cash in Hand',
                    'type' => 'cash',
                    'account_type' => 'cash',
                    'opening_balance' => 0.00,
                    'current_balance' => 0.00,
                    'is_default' => true,
                    'is_active' => true,
                ]);
            }
            $request->merge(['financial_account_id' => $defaultAccount->id]);
        }

        $validated = $request->validate([
            'order_id' => 'required|exists:sales_orders,id',
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = SalesOrder::where('team_id', $teamId)->findOrFail($validated['order_id']);

        if ((float) $validated['amount'] > (float) $order->due_amount) {
            throw new \InvalidArgumentException("Payment amount exceeds outstanding due amount.");
        }

        $account = FinancialAccount::where('team_id', $teamId)->findOrFail($validated['financial_account_id']);

        DB::transaction(function () use ($service, $order, $account, $validated) {
            $service->recordPayment($order, (float) $validated['amount']);

            $account->deposit(
                (float) $validated['amount'],
                [
                    'description' => "Customer due settlement for Invoice #{$order->order_number}" . (!empty($validated['notes']) ? " - {$validated['notes']}" : ''),
                    'reference_type' => SalesOrder::class,
                    'reference_id' => $order->id,
                    'reference_number' => $validated['payment_reference'] ?? null,
                ]
            );
        });

        return redirect()->back()->with('success', "Payment of ৳{$validated['amount']} recorded into {$account->name} for invoice {$order->order_number}.");
    }
}
