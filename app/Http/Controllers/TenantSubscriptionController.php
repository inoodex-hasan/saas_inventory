<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TenantSubscriptionController extends Controller
{
    /**
     * Tenant subscription overview and plan checkout.
     */
    public function index(SubscriptionService $subscriptionService)
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        $usage = $subscriptionService->getUsage($team);
        $plans = Plan::where('is_active', true)->where('slug', '!=', 'trial')->orderBy('sort_order')->get();

        $payments = SubscriptionPayment::where('team_id', $team->id)
            ->with(['plan', 'reviewer'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Settings/Subscription/Index', [
            'page_title' => 'Subscription & Billing',
            'usage' => $usage,
            'plans' => $plans,
            'payments' => $payments,
            'payment_instructions' => [
                'bkash' => 'Send Money / Payment to Merchant bKash: 01712-345678 (Counter 1)',
                'nagad' => 'Send Money / Payment to Merchant Nagad: 01812-345678',
                'rocket' => 'Merchant Biller ID: 4567 or Pay to 01912-345678-9',
                'bank' => 'City Bank Ltd, Account: 1102938475001, Branch: Gulshan, Name: SaaS Inventory BD Ltd',
            ],
        ]);
    }

    /**
     * Tenant submits manual TrxID payment.
     */
    public function submitPayment(Request $request, SubscriptionService $subscriptionService)
    {
        $team = Auth::user()->currentTeam;

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:bkash,nagad,rocket,bank_transfer',
            'sender_number' => 'required|string|max:50',
            'trx_id' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('subscription_payments', 'trx_id')
                    ->where(fn ($q) => $q->whereIn('status', ['pending', 'approved'])),
            ],
            'bank_account_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'trx_id.unique' => 'This Transaction ID (TrxID) has already been submitted and is currently active or being verified.',
        ]);

        $payment = $subscriptionService->submitPayment($team, $validated);

        return back()->with('success', "Payment receipt {$payment->payment_number} submitted! Super-Admin will verify your TrxID ({$payment->trx_id}) shortly.");
    }
}
