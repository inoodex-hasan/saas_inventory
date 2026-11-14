<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\SubscriptionPayment;
use App\Models\Team;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    /**
     * Standard Bangladeshi SaaS Plan Presets
     */
    public const DEFAULT_PLANS = [
        [
            'name' => 'Free Trial',
            'slug' => 'trial',
            'description' => '3-day fully featured trial workspace for new businesses.',
            'price_bdt' => 0.00,
            'annual_price_bdt' => 0.00,
            'max_products' => 100,
            'max_warehouses' => 1,
            'max_users' => 2,
            'max_quotations' => 20,
            'max_sales_orders' => 50,
            'max_purchase_orders' => 20,
            'max_projects' => 3,
            'max_employees' => 5,
            'trial_days' => 3,
            'is_active' => true,
            'sort_order' => 0,
            'features' => [
                'pos_billing' => true,
                'barcode_labels' => true,
                'inventory_tracking' => true,
                'purchase_management' => true,
                'accounting' => false,
                'service_workshop' => false,
                'projects_costing' => false,
                'payroll' => false,
            ],
        ],
        [
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Ideal for single retail shops, computer hardware vendors & traders.',
            'price_bdt' => 999.00,
            'annual_price_bdt' => 9990.00, // 2 months free
            'max_products' => 500,
            'max_warehouses' => 1,
            'max_users' => 3,
            'max_quotations' => 100,
            'max_sales_orders' => 500,
            'max_purchase_orders' => 200,
            'max_projects' => 5,
            'max_employees' => 10,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
            'features' => [
                'pos_billing' => true,
                'barcode_labels' => true,
                'inventory_tracking' => true,
                'purchase_management' => true,
                'customer_due_ledger' => true,
                'accounting' => false,
                'service_workshop' => false,
                'projects_costing' => false,
                'payroll' => false,
            ],
        ],
        [
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Complete enterprise control with multi-hub, workshop, projects & full accounting.',
            'price_bdt' => 2499.00,
            'annual_price_bdt' => 24990.00,
            'max_products' => 5000,
            'max_warehouses' => 3,
            'max_users' => 10,
            'max_quotations' => 1000,
            'max_sales_orders' => 5000,
            'max_purchase_orders' => 2000,
            'max_projects' => 50,
            'max_employees' => 50,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 2,
            'features' => [
                'pos_billing' => true,
                'barcode_labels' => true,
                'inventory_tracking' => true,
                'purchase_management' => true,
                'customer_due_ledger' => true,
                'accounting' => true,
                'service_workshop' => true,
                'projects_costing' => true,
                'payroll' => true,
                'warranties_rma' => true,
            ],
        ],
        [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'Unlimited resources, unlimited branches, priority phone support & custom SLAs.',
            'price_bdt' => 4999.00,
            'annual_price_bdt' => 49990.00,
            'max_products' => -1, // Unlimited
            'max_warehouses' => -1, // Unlimited
            'max_users' => -1, // Unlimited
            'max_quotations' => -1,
            'max_sales_orders' => -1,
            'max_purchase_orders' => -1,
            'max_projects' => -1,
            'max_employees' => -1,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 3,
            'features' => [
                'pos_billing' => true,
                'barcode_labels' => true,
                'inventory_tracking' => true,
                'purchase_management' => true,
                'customer_due_ledger' => true,
                'accounting' => true,
                'service_workshop' => true,
                'projects_costing' => true,
                'payroll' => true,
                'warranties_rma' => true,
                'unlimited_everything' => true,
                'priority_24_7_support' => true,
            ],
        ],
    ];

    /**
     * Ensure default Bangladeshi BDT plans are provisioned.
     * Uses firstOrCreate so custom prices, quotas, and edits made by the admin are never overwritten on deploy or signup.
     */
    public function ensureDefaultPlans(): void
    {
        foreach (self::DEFAULT_PLANS as $preset) {
            Plan::firstOrCreate(
                ['slug' => $preset['slug']],
                $preset
            );
        }
    }

    /**
     * Ensure a tenant has a subscription (defaults to 3-day trial).
     */
    public function ensureTenantSubscription(Team $team): TenantSubscription
    {
        $sub = $team->relationLoaded('subscription')
            ? $team->subscription
            : TenantSubscription::where('team_id', $team->id)->first();

        if ($sub) {
            return $sub;
        }

        $this->ensureDefaultPlans();

        $trialPlan = Plan::where('slug', 'trial')->first() ?? Plan::first();

        return TenantSubscription::create([
            'team_id' => $team->id,
            'plan_id' => $trialPlan->id,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays($trialPlan->trial_days ?: 3),
            'starts_at' => now(),
            'ends_at' => now()->addDays($trialPlan->trial_days ?: 3),
        ]);
    }

    /**
     * Get real-time usage metrics and quota comparisons.
     */
    public function getUsage(Team $team): array
    {
        $sub = $this->ensureTenantSubscription($team);
        $plan = $sub->plan;

        $productCount = Product::where('team_id', $team->id)->count();
        $warehouseCount = Warehouse::where('team_id', $team->id)->count();
        $userCount = $team->users()->pluck('users.id')->push($team->user_id)->unique()->filter()->count();
        $quotationCount = Quotation::where('team_id', $team->id)->count();
        $salesOrderCount = SalesOrder::where('team_id', $team->id)->count();
        $purchaseOrderCount = PurchaseOrder::where('team_id', $team->id)->count();
        $projectCount = Project::where('team_id', $team->id)->count();
        $employeeCount = Employee::where('team_id', $team->id)->count();

        return [
            'subscription' => $sub,
            'plan' => $plan,
            'days_remaining' => $sub->days_remaining,
            'is_active' => $sub->isActive(),
            'products' => $this->buildQuotaMetric($productCount, (int) ($plan->max_products ?? -1)),
            'warehouses' => $this->buildQuotaMetric($warehouseCount, (int) ($plan->max_warehouses ?? -1)),
            'users' => $this->buildQuotaMetric($userCount, (int) ($plan->max_users ?? -1)),
            'quotations' => $this->buildQuotaMetric($quotationCount, (int) ($plan->max_quotations ?? -1)),
            'sales_orders' => $this->buildQuotaMetric($salesOrderCount, (int) ($plan->max_sales_orders ?? -1)),
            'purchase_orders' => $this->buildQuotaMetric($purchaseOrderCount, (int) ($plan->max_purchase_orders ?? -1)),
            'projects' => $this->buildQuotaMetric($projectCount, (int) ($plan->max_projects ?? -1)),
            'employees' => $this->buildQuotaMetric($employeeCount, (int) ($plan->max_employees ?? -1)),
        ];
    }

    /**
     * Build normalized quota usage metric array.
     */
    protected function buildQuotaMetric(int $current, int $limit): array
    {
        $isUnlimited = $limit === -1;

        return [
            'current' => $current,
            'limit' => $limit,
            'is_unlimited' => $isUnlimited,
            'percent' => $limit > 0 ? min(100, round(($current / $limit) * 100)) : 0,
            'can_add' => $isUnlimited || $current < $limit,
        ];
    }

    /**
     * Check quota limits before resource creation.
     */
    public function canAddResource(Team $team, string $resource): bool
    {
        $usage = $this->getUsage($team);

        return $usage['is_active'] && (bool) ($usage[$resource]['can_add'] ?? true);
    }

    public function canAddProduct(Team $team): bool
    {
        return $this->canAddResource($team, 'products');
    }

    public function canAddWarehouse(Team $team): bool
    {
        return $this->canAddResource($team, 'warehouses');
    }

    public function canAddUser(Team $team): bool
    {
        return $this->canAddResource($team, 'users');
    }

    public function canAddQuotation(Team $team): bool
    {
        return $this->canAddResource($team, 'quotations');
    }

    public function canAddSalesOrder(Team $team): bool
    {
        return $this->canAddResource($team, 'sales_orders');
    }

    public function canAddPurchaseOrder(Team $team): bool
    {
        return $this->canAddResource($team, 'purchase_orders');
    }

    public function canAddProject(Team $team): bool
    {
        return $this->canAddResource($team, 'projects');
    }

    public function canAddEmployee(Team $team): bool
    {
        return $this->canAddResource($team, 'employees');
    }

    /**
     * Submit a Bangladeshi manual TrxID payment request.
     */
    public function submitPayment(Team $team, array $data): SubscriptionPayment
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $cycle = $data['billing_cycle'] ?? 'monthly';
        $amount = $cycle === 'yearly' ? $plan->annual_price_bdt : $plan->price_bdt;

        $datePrefix = now()->format('Ymd');
        $randomSeq = strtoupper(Str::random(4));
        $paymentNumber = "SUB-{$datePrefix}-{$randomSeq}";

        return SubscriptionPayment::create([
            'payment_number' => $paymentNumber,
            'team_id' => $team->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'amount_bdt' => $amount,
            'payment_method' => $data['payment_method'], // bkash, nagad, rocket, bank_transfer
            'sender_number' => $data['sender_number'] ?? null,
            'trx_id' => $data['trx_id'] ?? null,
            'bank_account_info' => $data['bank_account_info'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Super-Admin approval of a submitted TrxID / Subscription Payment.
     */
    public function approvePayment(SubscriptionPayment $payment, ?int $adminUserId = null): TenantSubscription
    {
        if ($payment->status === 'approved') {
            throw new \InvalidArgumentException("Payment {$payment->payment_number} has already been approved.");
        }

        return DB::transaction(function () use ($payment, $adminUserId) {
            $payment->update([
                'status' => 'approved',
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
            ]);

            $sub = TenantSubscription::where('team_id', $payment->team_id)->first();
            $cycleMonths = $payment->billing_cycle === 'yearly' ? 12 : 1;

            if ($sub) {
                // If already active and ends_at is in the future, extend from current ends_at
                $baseDate = ($sub->isActive() && $sub->ends_at && $sub->ends_at->isFuture())
                    ? $sub->ends_at
                    : now();

                $newEndsAt = (clone $baseDate)->addMonths($cycleMonths);

                $sub->update([
                    'plan_id' => $payment->plan_id,
                    'status' => 'active',
                    'billing_cycle' => $payment->billing_cycle,
                    'starts_at' => $sub->starts_at ?? now(),
                    'ends_at' => $newEndsAt,
                ]);
            } else {
                $sub = TenantSubscription::create([
                    'team_id' => $payment->team_id,
                    'plan_id' => $payment->plan_id,
                    'status' => 'active',
                    'billing_cycle' => $payment->billing_cycle,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths($cycleMonths),
                ]);
            }

            // Sync with Team status
            $payment->team->update([
                'status' => 'active',
                'subscription_plan' => $payment->plan->slug,
                'subscription_expires_at' => $sub->ends_at,
            ]);

            return $sub;
        });
    }

    /**
     * Super-Admin rejection of a submitted TrxID / Subscription Payment.
     */
    public function rejectPayment(SubscriptionPayment $payment, int $adminUserId, string $reason): SubscriptionPayment
    {
        $payment->update([
            'status' => 'rejected',
            'reviewed_by' => $adminUserId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $payment;
    }

    /**
     * SSLCommerz Gateway Checkout Initiation Preparation (Sandbox Ready)
     */
    public function initiateSslCommerzPayment(Team $team, Plan $plan, string $cycle = 'monthly'): array
    {
        $amount = $cycle === 'yearly' ? $plan->annual_price_bdt : $plan->price_bdt;
        $tranId = 'SSL-' . now()->format('YmdHis') . '-' . Str::random(4);

        $payment = SubscriptionPayment::create([
            'payment_number' => $tranId,
            'team_id' => $team->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'amount_bdt' => $amount,
            'payment_method' => 'sslcommerz',
            'status' => 'pending',
        ]);

        $postData = [
            'store_id' => config('services.sslcommerz.store_id', 'saas_sandbox'),
            'store_passwd' => config('services.sslcommerz.store_password', 'sandbox_pass'),
            'total_amount' => $amount,
            'currency' => 'BDT',
            'tran_id' => $tranId,
            'success_url' => route('sslcommerz.success'),
            'fail_url' => route('sslcommerz.fail'),
            'cancel_url' => route('sslcommerz.cancel'),
            'ipn_url' => route('sslcommerz.ipn'),
            'cus_name' => $team->displayName(),
            'cus_email' => $team->email ?: 'billing@' . Str::slug($team->name) . '.com',
            'cus_phone' => $team->phone ?: '01700000000',
            'product_name' => "SaaS {$plan->name} ({$cycle})",
            'product_category' => 'Software Subscription',
            'product_profile' => 'non-physical-goods',
        ];

        return [
            'payment' => $payment,
            'post_data' => $postData,
            'gateway_url' => config('services.sslcommerz.sandbox', true)
                ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
                : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php',
        ];
    }
}
