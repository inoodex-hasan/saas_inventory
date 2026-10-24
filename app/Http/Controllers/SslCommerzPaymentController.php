<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SslCommerzPaymentController extends Controller
{
    /**
     * Handle successful payment return from SSLCommerz.
     */
    public function success(Request $request, SubscriptionService $service)
    {
        $tranId = $request->input('tran_id');
        $valId = $request->input('val_id');
        $amount = (float) $request->input('amount', 0);
        $currency = $request->input('currency', 'BDT');
        $status = strtoupper((string) $request->input('status', ''));

        if (!$tranId) {
            return redirect()->route('subscription.index')->withErrors(['error' => 'Invalid gateway response: Transaction ID missing.']);
        }

        $payment = SubscriptionPayment::where('payment_number', $tranId)->first();

        if (!$payment) {
            return redirect()->route('subscription.index')->withErrors(['error' => 'Subscription payment record not found.']);
        }

        if ($payment->status === 'approved') {
            return redirect()->route('subscription.index')->with('success', 'Payment has already been processed and verified.');
        }

        // Validate currency and amount match expected record
        if (abs((float) $payment->amount_bdt - $amount) > 0.01 && $amount > 0) {
            Log::warning("SSLCommerz amount mismatch for {$tranId}. Expected: {$payment->amount_bdt}, Received: {$amount}");
            return redirect()->route('subscription.index')->withErrors(['error' => 'Payment amount mismatch. Please contact support.']);
        }

        // Accept VALID / VALIDATED or sandbox testing confirmation
        $isValidStatus = in_array($status, ['VALID', 'VALIDATED']) || config('services.sslcommerz.sandbox', true);

        if (!$isValidStatus) {
            $payment->update([
                'status' => 'rejected',
                'rejection_reason' => "Gateway status: {$status}",
                'gateway_payload' => $request->all(),
            ]);

            return redirect()->route('subscription.index')->withErrors(['error' => "Payment verification failed with status: {$status}"]);
        }

        $payment->update([
            'trx_id' => $valId ?: $tranId,
            'gateway_payload' => $request->all(),
        ]);

        $service->approvePayment($payment, null);

        return redirect()->route('subscription.index')->with('success', 'Payment processed and verified successfully via SSLCommerz. Subscription upgraded.');
    }

    /**
     * Handle failed payment return from SSLCommerz.
     */
    public function fail(Request $request)
    {
        $tranId = $request->input('tran_id');

        if ($tranId) {
            SubscriptionPayment::where('payment_number', $tranId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Transaction failed at SSLCommerz gateway.',
                    'gateway_payload' => $request->all(),
                ]);
        }

        return redirect()->route('subscription.index')->withErrors([
            'error' => 'SSLCommerz payment failed. Please try again or submit your TrxID manually.',
        ]);
    }

    /**
     * Handle cancelled payment return from SSLCommerz.
     */
    public function cancel(Request $request)
    {
        $tranId = $request->input('tran_id');

        if ($tranId) {
            SubscriptionPayment::where('payment_number', $tranId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Customer cancelled checkout on SSLCommerz gateway.',
                    'gateway_payload' => $request->all(),
                ]);
        }

        return redirect()->route('subscription.index')->with('warning', 'SSLCommerz payment checkout was cancelled.');
    }

    /**
     * Handle background IPN webhook from SSLCommerz.
     */
    public function ipn(Request $request, SubscriptionService $service)
    {
        $tranId = $request->input('tran_id');
        $valId = $request->input('val_id');
        $amount = (float) $request->input('amount', 0);
        $status = strtoupper((string) $request->input('status', ''));

        if (!$tranId) {
            return response()->json(['status' => 'error', 'message' => 'Missing tran_id'], 400);
        }

        $payment = SubscriptionPayment::where('payment_number', $tranId)->first();

        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        if ($payment->status === 'approved') {
            return response()->json(['status' => 'already_processed']);
        }

        $isValidStatus = in_array($status, ['VALID', 'VALIDATED']) || config('services.sslcommerz.sandbox', true);

        if ($isValidStatus) {
            $payment->update([
                'trx_id' => $valId ?: $tranId,
                'gateway_payload' => $request->all(),
            ]);

            $service->approvePayment($payment, null);

            return response()->json(['status' => 'success', 'message' => 'Payment approved']);
        }

        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => "IPN status: {$status}",
            'gateway_payload' => $request->all(),
        ]);

        return response()->json(['status' => 'rejected']);
    }
}
