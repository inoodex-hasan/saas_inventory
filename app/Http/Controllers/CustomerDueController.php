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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerDueController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $filter = $request->input('filter', 'all_with_dues'); // all_with_dues, fully_paid, all

        $customers = Customer::where('team_id', $teamId)
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filter === 'all_with_dues', function ($q) {
                $q->whereHas('salesOrders', fn ($so) => $so->where('due_amount', '>', 0)->where('status', '!=', 'cancelled'));
            })
            ->when($filter === 'fully_paid', function ($q) {
                $q->whereDoesntHave('salesOrders', fn ($so) => $so->where('due_amount', '>', 0)->where('status', '!=', 'cancelled'))
                  ->whereHas('salesOrders', fn ($so) => $so->where('status', '!=', 'cancelled'));
            })
            ->withSum(['salesOrders as total_billed' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total_amount')
            ->withSum(['salesOrders as total_paid' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'paid_amount')
            ->withSum(['salesOrders as total_due' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'due_amount')
            ->with(['salesOrders' => function ($q) {
                $q->where('status', '!=', 'cancelled')->where('due_amount', '>', 0)->latest('id');
            }])
            ->latest('id')
            ->get()
            ->map(function ($c) {
                $totalBilled = (float) ($c->total_billed ?? 0);
                $totalPaid = (float) ($c->total_paid ?? 0);
                $totalDue = (float) ($c->total_due ?? 0);
                $dueOrders = $c->salesOrders->values();

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
            $defaultAccount = app(FinancialAccountService::class)->getDefaultAccount($teamId);
            if ($defaultAccount) {
                $request->merge(['financial_account_id' => $defaultAccount->id]);
            }
        }

        $validated = $request->validate([
            'order_id' => ['required', Rule::exists('sales_orders', 'id')->where('team_id', $teamId)],
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => ['required', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = SalesOrder::where('team_id', $teamId)->findOrFail($validated['order_id']);

        if ((float) $validated['amount'] > (float) $order->due_amount) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount cannot exceed the outstanding invoice balance of ' . number_format($order->due_amount, 2),
            ]);
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
