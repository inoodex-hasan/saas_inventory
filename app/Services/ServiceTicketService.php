<?php

namespace App\Services;

use App\Exceptions\InvalidStockOperationException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceTicketService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Create a new service ticket with optional initial spare parts.
     */
    public function createTicket(array $data, array $parts = []): ServiceTicket
    {
        return DB::transaction(function () use ($data, $parts) {
            $teamId = Auth::user()?->current_team_id;

            // 1. Resolve Customer
            $customerId = $data['customer_id'] ?? null;
            if (($data['customer_mode'] ?? 'existing') === 'new') {
                $customer = Customer::create([
                    'team_id' => $teamId,
                    'name' => $data['new_customer_name'],
                    'phone' => $data['new_customer_phone'],
                    'email' => $data['new_customer_email'] ?? null,
                    'address' => $data['new_customer_address'] ?? null,
                    'is_active' => true,
                ]);
                $customerId = $customer->id;
            }

            // 2. Generate Ticket Number
            $ticketNumber = $this->generateTicketNumber($teamId);

            // 3. Prepare initial financials
            $serviceCharge = max(0, (float) ($data['service_charge'] ?? 0));
            $discountAmount = max(0, (float) ($data['discount_amount'] ?? 0));
            $warrantyDays = max(0, (int) ($data['warranty_days'] ?? 0));

            $partsTotal = 0.00;
            $preparedParts = [];

            foreach ($parts as $part) {
                $qty = max(0.01, (float) ($part['quantity'] ?? 1));
                $unitPrice = max(0, (float) ($part['unit_price'] ?? 0));
                $subtotal = round($qty * $unitPrice, 2);
                $partsTotal += $subtotal;

                $unitCost = 0.00;
                if (!empty($part['product_id'])) {
                    $prod = Product::find($part['product_id']);
                    if ($prod) {
                        $unitCost = (float) $prod->cost_price;
                    }
                }

                $preparedParts[] = [
                    'team_id' => $teamId,
                    'product_id' => $part['product_id'] ?? null,
                    'warehouse_id' => $part['warehouse_id'] ?? null,
                    'part_name' => $part['part_name'],
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'notes' => $part['notes'] ?? null,
                ];
            }

            $totalAmount = max(0, round($serviceCharge + $partsTotal - $discountAmount, 2));
            $initialPaid = max(0, (float) ($data['paid_amount'] ?? 0));
            $paidAmount = min($totalAmount, $initialPaid);
            $dueAmount = max(0, round($totalAmount - $paidAmount, 2));

            $paymentStatus = 'unpaid';
            if ($totalAmount > 0) {
                if ($paidAmount >= $totalAmount) {
                    $paymentStatus = 'paid';
                } elseif ($paidAmount > 0) {
                    $paymentStatus = 'partially_paid';
                }
            } elseif ($totalAmount == 0 && $initialPaid == 0) {
                $paymentStatus = 'paid';
            }

            // 4. Create Service Ticket Record
            $ticket = ServiceTicket::create([
                'team_id' => $teamId,
                'customer_id' => $customerId,
                'ticket_number' => $ticketNumber,
                'device_name' => $data['device_name'],
                'device_brand' => $data['device_brand'] ?? null,
                'serial_no' => $data['serial_no'] ?? null,
                'accessories_received' => $data['accessories_received'] ?? null,
                'problem_description' => $data['problem_description'] ?? null,
                'initial_diagnosis' => $data['initial_diagnosis'] ?? null,
                'technician_id' => $data['technician_id'] ?? null,
                'technician_name' => $data['technician_name'] ?? null,
                'status' => $data['status'] ?? 'received',
                'priority' => $data['priority'] ?? 'normal',
                'service_charge' => $serviceCharge,
                'parts_total' => $partsTotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'warranty_days' => $warrantyDays,
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'estimated_completion_date' => $data['estimated_completion_date'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // 5. Create Part Items and Deduct Stock
            foreach ($preparedParts as $p) {
                $p['service_ticket_id'] = $ticket->id;
                ServiceTicketItem::create($p);

                if (!empty($p['product_id']) && !empty($p['warehouse_id'])) {
                    $this->stockService->stockOut(
                        warehouseId: (int) $p['warehouse_id'],
                        productId: (int) $p['product_id'],
                        quantity: (float) $p['quantity'],
                        referenceType: ServiceTicket::class,
                        referenceId: $ticket->id,
                        reason: "Service Ticket #{$ticket->ticket_number} spare part consumption: {$p['part_name']}"
                    );
                }
            }

            return $ticket->load(['customer', 'technician', 'items.product', 'items.warehouse']);
        });
    }

    /**
     * Add a spare part item to an existing ticket and deduct stock.
     */
    public function addPart(ServiceTicket $ticket, array $partData): ServiceTicketItem
    {
        return DB::transaction(function () use ($ticket, $partData) {
            $teamId = $ticket->team_id;
            $qty = max(0.01, (float) ($partData['quantity'] ?? 1));
            $unitPrice = max(0, (float) ($partData['unit_price'] ?? 0));
            $subtotal = round($qty * $unitPrice, 2);

            $unitCost = 0.00;
            if (!empty($partData['product_id'])) {
                $prod = Product::find($partData['product_id']);
                if ($prod) {
                    $unitCost = (float) $prod->cost_price;
                }
            }

            $item = ServiceTicketItem::create([
                'team_id' => $teamId,
                'service_ticket_id' => $ticket->id,
                'product_id' => $partData['product_id'] ?? null,
                'warehouse_id' => $partData['warehouse_id'] ?? null,
                'part_name' => $partData['part_name'],
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'notes' => $partData['notes'] ?? null,
            ]);

            // Deduct from stock if product and warehouse specified
            if (!empty($partData['product_id']) && !empty($partData['warehouse_id'])) {
                $this->stockService->stockOut(
                    warehouseId: (int) $partData['warehouse_id'],
                    productId: (int) $partData['product_id'],
                    quantity: $qty,
                    referenceType: ServiceTicket::class,
                    referenceId: $ticket->id,
                    reason: "Service Ticket #{$ticket->ticket_number} spare part consumption: {$partData['part_name']}"
                );
            }

            $this->recalculateTicketTotals($ticket);

            return $item;
        });
    }

    /**
     * Remove a spare part from a ticket and restore stock.
     */
    public function removePart(ServiceTicket $ticket, ServiceTicketItem $item): void
    {
        DB::transaction(function () use ($ticket, $item) {
            if (!empty($item->product_id) && !empty($item->warehouse_id)) {
                $this->stockService->stockIn(
                    warehouseId: (int) $item->warehouse_id,
                    productId: (int) $item->product_id,
                    quantity: (float) $item->quantity,
                    referenceType: ServiceTicket::class,
                    referenceId: $ticket->id,
                    reason: "Service Ticket #{$ticket->ticket_number} removed spare part: {$item->part_name}"
                );
            }

            $item->delete();

            $this->recalculateTicketTotals($ticket);
        });
    }

    /**
     * Transition ticket status lifecycle.
     */
    public function transitionStatus(ServiceTicket $ticket, string $newStatus): void
    {
        $validStatuses = ['received', 'diagnosing', 'waiting_parts', 'repaired', 'delivered', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status transition: '{$newStatus}'.");
        }

        $updates = ['status' => $newStatus];

        if ($newStatus === 'repaired' && empty($ticket->completed_date)) {
            $updates['completed_date'] = now()->toDateString();
        }

        if ($newStatus === 'delivered' && empty($ticket->delivered_date)) {
            $updates['delivered_date'] = now()->toDateString();
        }

        $ticket->update($updates);
    }

    /**
     * Record a customer payment against the ticket.
     */
    public function recordPayment(ServiceTicket $ticket, float $amount, ?string $paymentMethod = null, ?string $notes = null, ?int $financialAccountId = null): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Payment amount must be greater than zero.");
        }

        if ($amount > (float) $ticket->due_amount) {
            throw new \InvalidArgumentException("Payment amount exceeds outstanding due amount.");
        }

        DB::transaction(function () use ($ticket, $amount, $paymentMethod, $notes, $financialAccountId) {
            $account = null;
            if ($financialAccountId) {
                $account = \App\Models\FinancialAccount::where('team_id', $ticket->team_id)->find($financialAccountId);
            }

            $methodName = $account ? $account->name : ($paymentMethod ?? 'Cash');

            $ticket->paid_amount += $amount;
            $ticket->due_amount = max(0, round((float) $ticket->total_amount - (float) $ticket->paid_amount, 2));
            $ticket->payment_method = $methodName;
            if ($account) {
                $ticket->financial_account_id = $account->id;
            }

            if ($ticket->due_amount <= 0 && $ticket->total_amount > 0) {
                $ticket->payment_status = 'paid';
            } else {
                $ticket->payment_status = 'partially_paid';
            }

            $ticket->save();

            if ($account) {
                $account->deposit($amount, [
                    'reference_type' => ServiceTicket::class,
                    'reference_id' => $ticket->id,
                    'reference_number' => $ticket->ticket_number,
                    'description' => "Service ticket payment received #{$ticket->ticket_number}" . ($notes ? " ({$notes})" : ""),
                ]);
            }
        });
    }

    /**
     * Recalculate ticket totals after item modifications.
     */
    public function recalculateTicketTotals(ServiceTicket $ticket): void
    {
        $partsTotal = (float) $ticket->items()->sum('subtotal');
        $serviceCharge = (float) $ticket->service_charge;
        $discountAmount = (float) $ticket->discount_amount;

        $totalAmount = max(0, round($serviceCharge + $partsTotal - $discountAmount, 2));
        $dueAmount = max(0, round($totalAmount - (float) $ticket->paid_amount, 2));

        $paymentStatus = 'unpaid';
        if ($totalAmount > 0) {
            if ($ticket->paid_amount >= $totalAmount) {
                $paymentStatus = 'paid';
            } elseif ($ticket->paid_amount > 0) {
                $paymentStatus = 'partially_paid';
            }
        } elseif ($totalAmount == 0) {
            $paymentStatus = 'paid';
        }

        $ticket->update([
            'parts_total' => $partsTotal,
            'total_amount' => $totalAmount,
            'due_amount' => $dueAmount,
            'payment_status' => $paymentStatus,
        ]);
    }

    /**
     * Generate unique ticket sequence SRV-YYYYMMDD-0001
     */
    protected function generateTicketNumber(?int $teamId): string
    {
        $prefix = 'SRV-' . Carbon::now()->format('Ymd') . '-';

        $lastTicket = ServiceTicket::where('team_id', $teamId)
            ->where('ticket_number', 'LIKE', $prefix . '%')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->ticket_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }
}
