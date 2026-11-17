<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SiteSetting;
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

        $paymentAccounts = [
            'bkash' => [
                'is_active'    => filter_var(SiteSetting::get('payment_bkash_active', true), FILTER_VALIDATE_BOOLEAN),
                'name'         => SiteSetting::get('payment_bkash_name', 'bKash Merchant'),
                'number'       => SiteSetting::get('payment_bkash_number', '01712-345678'),
                'counter'      => SiteSetting::get('payment_bkash_counter', 'Counter 1'),
                'type'         => SiteSetting::get('payment_bkash_type', 'Merchant Payment'),
                'instructions' => SiteSetting::get('payment_bkash_instructions', 'Select "Payment" option in bKash App'),
            ],
            'nagad' => [
                'is_active'    => filter_var(SiteSetting::get('payment_nagad_active', true), FILTER_VALIDATE_BOOLEAN),
                'name'         => SiteSetting::get('payment_nagad_name', 'Nagad Merchant'),
                'number'       => SiteSetting::get('payment_nagad_number', '01812-345678'),
                'counter'      => SiteSetting::get('payment_nagad_counter', 'Counter 1'),
                'type'         => SiteSetting::get('payment_nagad_type', 'Merchant Payment'),
                'instructions' => SiteSetting::get('payment_nagad_instructions', 'Select "Merchant Pay" in Nagad App'),
            ],
            'rocket' => [
                'is_active'    => filter_var(SiteSetting::get('payment_rocket_active', true), FILTER_VALIDATE_BOOLEAN),
                'name'         => SiteSetting::get('payment_rocket_name', 'Rocket Biller'),
                'number'       => SiteSetting::get('payment_rocket_number', '01912-345678-9'),
                'counter'      => SiteSetting::get('payment_rocket_counter', 'Biller ID 4567'),
                'type'         => SiteSetting::get('payment_rocket_type', 'Merchant / Biller'),
                'instructions' => SiteSetting::get('payment_rocket_instructions', 'Pay via Biller ID or Merchant Pay'),
            ],
            'bank_transfer' => [
                'is_active'    => filter_var(SiteSetting::get('payment_bank_active', true), FILTER_VALIDATE_BOOLEAN),
                'name'         => SiteSetting::get('payment_bank_name', 'City Bank Ltd'),
                'number'       => SiteSetting::get('payment_bank_account_number', '1102938475001'),
                'counter'      => SiteSetting::get('payment_bank_branch', 'Gulshan Branch'),
                'account_name' => SiteSetting::get('payment_bank_account_name', 'SaaS Inventory BD Ltd'),
                'routing'      => SiteSetting::get('payment_bank_routing', '225261145'),
                'swift'        => SiteSetting::get('payment_bank_swift', 'CIBLBDDH'),
                'type'         => SiteSetting::get('payment_bank_type', 'Corporate Account'),
                'instructions' => SiteSetting::get('payment_bank_instructions', 'Direct deposit, BEFTN, NPSB or RTGS to our corporate bank account'),
            ],
        ];

        return Inertia::render('Settings/Subscription/Index', [
            'page_title' => 'Subscription & Billing',
            'usage' => $usage,
            'plans' => $plans,
            'payments' => $payments,
            'payment_instructions' => [
                'bkash' => 'Send Money / Payment to ' . $paymentAccounts['bkash']['name'] . ': ' . $paymentAccounts['bkash']['number'] . ' (' . $paymentAccounts['bkash']['counter'] . ')',
                'nagad' => 'Send Money / Payment to ' . $paymentAccounts['nagad']['name'] . ': ' . $paymentAccounts['nagad']['number'],
                'rocket' => $paymentAccounts['rocket']['counter'] . ' or Pay to ' . $paymentAccounts['rocket']['number'],
                'bank' => $paymentAccounts['bank_transfer']['name'] . ', Account: ' . $paymentAccounts['bank_transfer']['number'] . ', Branch: ' . $paymentAccounts['bank_transfer']['counter'] . ', Name: ' . $paymentAccounts['bank_transfer']['account_name'],
            ],
            'payment_accounts' => $paymentAccounts,
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

        $methodActive = match($validated['payment_method']) {
            'bkash' => filter_var(SiteSetting::get('payment_bkash_active', true), FILTER_VALIDATE_BOOLEAN),
            'nagad' => filter_var(SiteSetting::get('payment_nagad_active', true), FILTER_VALIDATE_BOOLEAN),
            'rocket' => filter_var(SiteSetting::get('payment_rocket_active', true), FILTER_VALIDATE_BOOLEAN),
            'bank_transfer' => filter_var(SiteSetting::get('payment_bank_active', true), FILTER_VALIDATE_BOOLEAN),
            default => false,
        };

        if (!$methodActive) {
            return back()->withErrors(['payment_method' => 'This payment service is temporarily not available. Please choose an active payment method.']);
        }

        $payment = $subscriptionService->submitPayment($team, $validated);

        return back()->with('success', "Payment receipt {$payment->payment_number} submitted! Super-Admin will verify your TrxID ({$payment->trx_id}) shortly.");
    }
}
