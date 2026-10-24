<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecurringInvoiceService
{
    /**
     * Create a new recurring invoice profile with items.
     */
    public function createProfile(array $data, array $items): RecurringInvoice
    {
        if (empty($items)) {
            throw new \InvalidArgumentException("A recurring invoice profile must contain at least one line item.");
        }

        return DB::transaction(function () use ($data, $items) {
            $teamId = $data['team_id'];

            // Calculate totals from items
            $subtotal = 0.00;
            $preparedItems = [];

            foreach ($items as $item) {
                $qty = max(0.01, (float) ($item['quantity'] ?? 1));
                $price = max(0, (float) ($item['unit_price'] ?? 0));
                $lineSubtotal = round($qty * $price, 2);
                $subtotal += $lineSubtotal;

                $preparedItems[] = [
                    'product_id' => !empty($item['product_id']) ? (int) $item['product_id'] : null,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discount = max(0, (float) ($data['discount_amount'] ?? 0));
            $tax = max(0, (float) ($data['tax_amount'] ?? 0));
            $totalAmount = max(0, round($subtotal - $discount + $tax, 2));

            $subscriptionNumber = $data['subscription_number'] ?? $this->generateSubscriptionNumber($teamId);
            $startDate = Carbon::parse($data['start_date']);
            $nextIssueDate = Carbon::parse($data['next_issue_date'] ?? $data['start_date']);

            $profile = RecurringInvoice::create([
                'team_id' => $teamId,
                'customer_id' => $data['customer_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'profile_name' => $data['profile_name'],
                'subscription_number' => $subscriptionNumber,
                'status' => 'active',
                'frequency' => $data['frequency'] ?? 'monthly',
                'interval_count' => max(1, (int) ($data['interval_count'] ?? 1)),
                'start_date' => $startDate->toDateString(),
                'end_date' => !empty($data['end_date']) ? Carbon::parse($data['end_date'])->toDateString() : null,
                'next_issue_date' => $nextIssueDate->toDateString(),
                'total_cycles_limit' => !empty($data['total_cycles_limit']) ? (int) $data['total_cycles_limit'] : null,
                'cycles_completed' => 0,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $totalAmount,
                'payment_terms_days' => max(0, (int) ($data['payment_terms_days'] ?? 7)),
                'auto_send_email' => filter_var($data['auto_send_email'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($preparedItems as $pItem) {
                $profile->items()->create($pItem);
            }

            return $profile->load(['customer', 'warehouse', 'items.product']);
        });
    }

    /**
     * Update an existing recurring invoice profile.
     */
    public function updateProfile(RecurringInvoice $profile, array $data, array $items): RecurringInvoice
    {
        if (empty($items)) {
            throw new \InvalidArgumentException("A recurring invoice profile must contain at least one line item.");
        }

        return DB::transaction(function () use ($profile, $data, $items) {
            $subtotal = 0.00;
            $preparedItems = [];

            foreach ($items as $item) {
                $qty = max(0.01, (float) ($item['quantity'] ?? 1));
                $price = max(0, (float) ($item['unit_price'] ?? 0));
                $lineSubtotal = round($qty * $price, 2);
                $subtotal += $lineSubtotal;

                $preparedItems[] = [
                    'product_id' => !empty($item['product_id']) ? (int) $item['product_id'] : null,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discount = max(0, (float) ($data['discount_amount'] ?? 0));
            $tax = max(0, (float) ($data['tax_amount'] ?? 0));
            $totalAmount = max(0, round($subtotal - $discount + $tax, 2));

            $profile->update([
                'customer_id' => $data['customer_id'] ?? $profile->customer_id,
                'warehouse_id' => array_key_exists('warehouse_id', $data) ? $data['warehouse_id'] : $profile->warehouse_id,
                'profile_name' => $data['profile_name'] ?? $profile->profile_name,
                'frequency' => $data['frequency'] ?? $profile->frequency,
                'interval_count' => max(1, (int) ($data['interval_count'] ?? $profile->interval_count)),
                'start_date' => !empty($data['start_date']) ? Carbon::parse($data['start_date'])->toDateString() : $profile->start_date,
                'end_date' => !empty($data['end_date']) ? Carbon::parse($data['end_date'])->toDateString() : null,
                'next_issue_date' => !empty($data['next_issue_date']) ? Carbon::parse($data['next_issue_date'])->toDateString() : $profile->next_issue_date,
                'total_cycles_limit' => !empty($data['total_cycles_limit']) ? (int) $data['total_cycles_limit'] : null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $totalAmount,
                'payment_terms_days' => max(0, (int) ($data['payment_terms_days'] ?? $profile->payment_terms_days)),
                'auto_send_email' => filter_var($data['auto_send_email'] ?? $profile->auto_send_email, FILTER_VALIDATE_BOOLEAN),
                'notes' => $data['notes'] ?? $profile->notes,
            ]);

            // Replace line items
            $profile->items()->delete();
            foreach ($preparedItems as $pItem) {
                $profile->items()->create($pItem);
            }

            return $profile->fresh(['customer', 'warehouse', 'items.product']);
        });
    }

    /**
     * Generate a new SalesOrder from this recurring profile for the current cycle.
     */
    public function generateInvoice(RecurringInvoice $profile, ?Carbon $issueDate = null): SalesOrder
    {
        if ($profile->status !== 'active') {
            throw new \InvalidArgumentException("Cannot generate invoice for {$profile->status} subscription profile.");
        }

        return DB::transaction(function () use ($profile, $issueDate) {
            $issueDate = $issueDate ?? Carbon::parse($profile->next_issue_date);
            $teamId = $profile->team_id;

            // Generate unique Sales Order Number
            $prefix = 'SO-' . $issueDate->format('Ymd') . '-';
            $lastOrder = SalesOrder::where('team_id', $teamId)
                ->where('order_number', 'LIKE', $prefix . '%')
                ->latest('id')
                ->first();

            $nextNum = $lastOrder
                ? str_pad((int) substr($lastOrder->order_number, -4) + 1, 4, '0', STR_PAD_LEFT)
                : '0001';

            $orderNumber = $prefix . $nextNum;

            // Pick default warehouse if none specified on profile
            $warehouseId = $profile->warehouse_id;
            if (!$warehouseId) {
                $defaultWh = Warehouse::where('team_id', $teamId)->where('is_default', true)->first()
                    ?? Warehouse::where('team_id', $teamId)->first();
                $warehouseId = $defaultWh?->id;
            }

            $order = SalesOrder::create([
                'team_id' => $teamId,
                'customer_id' => $profile->customer_id,
                'warehouse_id' => $warehouseId,
                'recurring_invoice_id' => $profile->id,
                'order_number' => $orderNumber,
                'status' => 'confirmed',
                'payment_status' => 'unpaid',
                'order_date' => $issueDate->toDateString(),
                'subtotal' => $profile->subtotal,
                'tax_amount' => $profile->tax_amount,
                'discount_amount' => $profile->discount_amount,
                'shipping_cost' => 0.00,
                'total_amount' => $profile->total_amount,
                'paid_amount' => 0.00,
                'due_amount' => $profile->total_amount,
                'notes' => "Auto-generated from Subscription #{$profile->subscription_number} ({$profile->profile_name})",
                'created_by' => $profile->created_by,
            ]);

            // Create line items for SalesOrder
            foreach ($profile->items as $item) {
                $productId = $item->product_id;
                $unitCost = 0.00;

                if ($productId && $item->product) {
                    $unitCost = (float) $item->product->cost_price;
                } elseif (!$productId) {
                    // Resolve or create non-inventory service product for this tenant
                    $slug = Str::slug($item->item_name ?: 'service-item');
                    $serviceProduct = Product::firstOrCreate(
                        [
                            'team_id' => $teamId,
                            'name' => $item->item_name ?: 'Service Retainer',
                        ],
                        [
                            'sku' => 'SRV-' . strtoupper(substr($slug, 0, 10)) . '-' . Str::random(4),
                            'cost_price' => 0.00,
                            'sale_price' => $item->unit_price,
                            'is_active' => true,
                        ]
                    );
                    $productId = $serviceProduct->id;
                }

                SalesOrderItem::create([
                    'team_id' => $teamId,
                    'sales_order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'unit_cost' => $unitCost,
                    'warranty_days' => 0,
                    'subtotal' => $item->subtotal,
                ]);
            }

            // Update recurring profile lifecycle
            $newCyclesCompleted = $profile->cycles_completed + 1;
            $nextDate = $profile->calculateNextDate($profile->next_issue_date);

            $updates = [
                'last_issued_at' => now(),
                'cycles_completed' => $newCyclesCompleted,
                'next_issue_date' => $nextDate->toDateString(),
            ];

            // Check if limits or end date reached
            if ($profile->total_cycles_limit && $newCyclesCompleted >= $profile->total_cycles_limit) {
                $updates['status'] = 'completed';
            } elseif ($profile->end_date && $nextDate->isAfter(Carbon::parse($profile->end_date))) {
                $updates['status'] = 'completed';
            }

            $profile->update($updates);

            return $order->load(['customer', 'items.product']);
        });
    }

    /**
     * Process all recurring profiles that are due for issuance today.
     */
    public function processAllDue(?int $teamId = null): array
    {
        $today = now()->toDateString();

        $query = RecurringInvoice::where('status', 'active')
            ->whereDate('next_issue_date', '<=', $today)
            ->with(['items.product', 'customer', 'warehouse']);

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        $dueProfiles = $query->get();
        $generated = [];
        $errors = [];

        foreach ($dueProfiles as $profile) {
            try {
                $order = $this->generateInvoice($profile);
                $generated[] = [
                    'profile_id' => $profile->id,
                    'subscription_number' => $profile->subscription_number,
                    'order_number' => $order->order_number,
                    'total_amount' => (float) $order->total_amount,
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'profile_id' => $profile->id,
                    'subscription_number' => $profile->subscription_number,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'total_due' => count($dueProfiles),
            'generated_count' => count($generated),
            'generated' => $generated,
            'errors' => $errors,
        ];
    }

    /**
     * Toggle profile lifecycle status (pause, resume, cancel).
     */
    public function toggleStatus(RecurringInvoice $profile, string $action): RecurringInvoice
    {
        return match ($action) {
            'pause' => tap($profile)->update(['status' => 'paused']),
            'resume' => tap($profile)->update([
                'status' => 'active',
                // If next_issue_date is in the past, align to today or calculate next
                'next_issue_date' => Carbon::parse($profile->next_issue_date)->isPast()
                    ? now()->toDateString()
                    : $profile->next_issue_date,
            ]),
            'cancel' => tap($profile)->update(['status' => 'cancelled']),
            default => throw new \InvalidArgumentException("Invalid action: {$action}"),
        };
    }

    /**
     * Generate unique subscription number for tenant.
     */
    public function generateSubscriptionNumber(int $teamId): string
    {
        $prefix = 'REC-' . Carbon::now()->format('Ymd') . '-';
        $last = RecurringInvoice::where('team_id', $teamId)
            ->where('subscription_number', 'LIKE', $prefix . '%')
            ->latest('id')
            ->first();

        $nextNum = $last
            ? str_pad((int) substr($last->subscription_number, -4) + 1, 4, '0', STR_PAD_LEFT)
            : '0001';

        return $prefix . $nextNum;
    }
}
