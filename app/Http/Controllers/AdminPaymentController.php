<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminPaymentController extends Controller
{
    /**
     * List submitted subscription payments.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $search = $request->input('search', '');

        $query = SubscriptionPayment::with(['team', 'plan', 'reviewer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('trx_id', 'like', "%{$search}%")
                        ->orWhere('payment_number', 'like', "%{$search}%")
                        ->orWhere('sender_number', 'like', "%{$search}%")
                        ->orWhereHas('team', fn ($t) => $t->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest();

        $payments = $query->paginate(15)->withQueryString();

        $metrics = [
            'pending' => SubscriptionPayment::where('status', 'pending')->count(),
            'approved_this_month' => SubscriptionPayment::where('status', 'approved')->whereMonth('created_at', now()->month)->count(),
            'revenue_this_month_bdt' => (float) SubscriptionPayment::where('status', 'approved')->whereMonth('created_at', now()->month)->sum('amount_bdt'),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'page_title' => 'Subscription Payments Verification',
            'payments' => $payments,
            'metrics' => $metrics,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Approve submitted TrxID payment.
     */
    public function approve(SubscriptionPayment $payment, SubscriptionService $subscriptionService)
    {
        if ($payment->status === 'approved') {
            return back()->with('error', "Payment {$payment->payment_number} has already been approved.");
        }

        $sub = $subscriptionService->approvePayment($payment, Auth::id());

        return back()->with('success', "Payment {$payment->payment_number} (TrxID: {$payment->trx_id}) approved! Tenant subscription extended until {$sub->ends_at->format('M d, Y')}.");
    }

    /**
     * Reject submitted TrxID payment.
     */
    public function reject(Request $request, SubscriptionPayment $payment, SubscriptionService $subscriptionService)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $subscriptionService->rejectPayment($payment, Auth::id(), $validated['rejection_reason']);

        return back()->with('success', "Payment {$payment->payment_number} marked as rejected.");
    }
}
