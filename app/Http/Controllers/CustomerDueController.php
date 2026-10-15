<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return Inertia::render('Dues/Index', [
            'page_title' => 'Customer Due Ledger',
            'customers' => $customers,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
        ]);
    }

    public function pay(Request $request, SalesOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'order_id' => 'required|exists:sales_orders,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = SalesOrder::where('team_id', $teamId)->findOrFail($validated['order_id']);

        if ((float) $validated['amount'] > (float) $order->due_amount) {
            throw new \InvalidArgumentException("Payment amount exceeds outstanding due amount.");
        }

        $service->recordPayment($order, (float) $validated['amount']);

        return redirect()->back()->with('success', "Payment of {$validated['amount']} recorded for invoice {$order->order_number}.");
    }
}
