<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CostCategory;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectBill;
use App\Models\ProjectCost;
use App\Models\ProjectItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Create a new project with optional initial items and costs.
     */
    public function createProject(array $data, array $items = [], array $costs = []): Project
    {
        return DB::transaction(function () use ($data, $items, $costs) {
            $teamId = Auth::user()?->current_team_id;

            // 1. Resolve Client
            $clientId = $data['client_id'] ?? null;
            if (($data['client_mode'] ?? 'existing') === 'new') {
                $client = Client::create([
                    'team_id' => $teamId,
                    'name' => $data['new_client_name'],
                    'company_name' => $data['new_client_company'] ?? $data['new_client_name'],
                    'phone' => $data['new_client_phone'] ?? null,
                    'email' => $data['new_client_email'] ?? null,
                    'address' => $data['new_client_address'] ?? null,
                    'tax_number' => $data['new_client_tax_number'] ?? null,
                    'is_active' => true,
                ]);
                $clientId = $client->id;
            }

            // 2. Generate Project Code
            $projectCode = $this->generateProjectCode($teamId);

            // 3. Financial calculations
            $budget = max(0, (float) ($data['budget'] ?? 0));
            $discountAmount = max(0, (float) ($data['discount_amount'] ?? 0));
            $taxAmount = max(0, (float) ($data['tax_amount'] ?? 0));

            $itemsTotal = 0.00;
            $preparedItems = [];

            foreach ($items as $item) {
                $qty = max(0.01, (float) ($item['quantity'] ?? 1));
                $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
                $subtotal = round($qty * $unitPrice, 2);
                $itemsTotal += $subtotal;

                $unitCost = 0.00;
                if (!empty($item['product_id'])) {
                    $prod = Product::find($item['product_id']);
                    if ($prod) {
                        $unitCost = (float) $prod->cost_price;
                    }
                }

                $preparedItems[] = [
                    'team_id' => $teamId,
                    'product_id' => $item['product_id'] ?? null,
                    'warehouse_id' => $item['warehouse_id'] ?? null,
                    'item_name' => $item['item_name'],
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $costsTotal = 0.00;
            $preparedCosts = [];
            foreach ($costs as $cost) {
                $amount = max(0, (float) ($cost['amount'] ?? 0));
                $costsTotal += $amount;

                $preparedCosts[] = [
                    'team_id' => $teamId,
                    'cost_category_id' => $cost['cost_category_id'] ?? null,
                    'description' => $cost['description'],
                    'amount' => $amount,
                    'cost_date' => $cost['cost_date'] ?? now()->toDateString(),
                    'vendor_name' => $cost['vendor_name'] ?? null,
                    'receipt_reference' => $cost['receipt_reference'] ?? null,
                    'notes' => $cost['notes'] ?? null,
                ];
            }

            // If items exist, billing base is items + costs; otherwise contract budget
            $baseAmount = ($itemsTotal > 0) ? ($itemsTotal + $costsTotal) : $budget;
            $billedTotal = max(0, round($baseAmount - $discountAmount + $taxAmount, 2));

            $initialPaid = max(0, (float) ($data['paid_amount'] ?? 0));
            $paidAmount = min($billedTotal, $initialPaid);
            $dueAmount = max(0, round($billedTotal - $paidAmount, 2));

            $paymentStatus = 'unpaid';
            if ($billedTotal > 0) {
                if ($paidAmount >= $billedTotal) {
                    $paymentStatus = 'paid';
                } elseif ($paidAmount > 0) {
                    $paymentStatus = 'partially_paid';
                }
            } elseif ($billedTotal == 0 && $initialPaid == 0) {
                $paymentStatus = 'paid';
            }

            // 4. Create Project
            $project = Project::create([
                'team_id' => $teamId,
                'client_id' => $clientId,
                'project_code' => $projectCode,
                'name' => $data['name'],
                'status' => $data['status'] ?? 'planning',
                'budget' => $budget,
                'items_total' => $itemsTotal,
                'costs_total' => $costsTotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'billed_total' => $billedTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // 5. Insert project items & deduct inventory
            foreach ($preparedItems as $pi) {
                $pi['project_id'] = $project->id;
                ProjectItem::create($pi);

                if (!empty($pi['product_id']) && !empty($pi['warehouse_id'])) {
                    $this->stockService->stockOut(
                        warehouseId: (int) $pi['warehouse_id'],
                        productId: (int) $pi['product_id'],
                        quantity: (float) $pi['quantity'],
                        referenceType: Project::class,
                        referenceId: $project->id,
                        reason: "Project #{$project->project_code} bill of materials: {$pi['item_name']}"
                    );
                }
            }

            // 6. Insert direct costs
            foreach ($preparedCosts as $pc) {
                $pc['project_id'] = $project->id;
                ProjectCost::create($pc);
            }

            return $project->load(['client', 'items.product', 'items.warehouse', 'costs.category', 'bills']);
        });
    }

    /**
     * Issue an inventory item / BOM material to a project.
     */
    public function addItem(Project $project, array $itemData): ProjectItem
    {
        return DB::transaction(function () use ($project, $itemData) {
            $teamId = $project->team_id;
            $qty = max(0.01, (float) ($itemData['quantity'] ?? 1));
            $unitPrice = max(0, (float) ($itemData['unit_price'] ?? 0));
            $subtotal = round($qty * $unitPrice, 2);

            $unitCost = 0.00;
            if (!empty($itemData['product_id'])) {
                $prod = Product::find($itemData['product_id']);
                if ($prod) {
                    $unitCost = (float) $prod->cost_price;
                }
            }

            $item = ProjectItem::create([
                'team_id' => $teamId,
                'project_id' => $project->id,
                'product_id' => $itemData['product_id'] ?? null,
                'warehouse_id' => $itemData['warehouse_id'] ?? null,
                'item_name' => $itemData['item_name'],
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'notes' => $itemData['notes'] ?? null,
            ]);

            if (!empty($itemData['product_id']) && !empty($itemData['warehouse_id'])) {
                $this->stockService->stockOut(
                    warehouseId: (int) $itemData['warehouse_id'],
                    productId: (int) $itemData['product_id'],
                    quantity: $qty,
                    referenceType: Project::class,
                    referenceId: $project->id,
                    reason: "Project #{$project->project_code} material issued: {$itemData['item_name']}"
                );
            }

            $this->recalculateProjectTotals($project);

            return $item;
        });
    }

    /**
     * Remove an item from a project and restore stock.
     */
    public function removeItem(Project $project, ProjectItem $item): void
    {
        DB::transaction(function () use ($project, $item) {
            if (!empty($item->product_id) && !empty($item->warehouse_id)) {
                $this->stockService->stockIn(
                    warehouseId: (int) $item->warehouse_id,
                    productId: (int) $item->product_id,
                    quantity: (float) $item->quantity,
                    referenceType: Project::class,
                    referenceId: $project->id,
                    reason: "Project #{$project->project_code} material returned to stock: {$item->item_name}"
                );
            }

            $item->delete();

            $this->recalculateProjectTotals($project);
        });
    }

    /**
     * Add direct non-inventory expense to project.
     */
    public function addCost(Project $project, array $costData): ProjectCost
    {
        return DB::transaction(function () use ($project, $costData) {
            $cost = ProjectCost::create([
                'team_id' => $project->team_id,
                'project_id' => $project->id,
                'cost_category_id' => $costData['cost_category_id'] ?? null,
                'description' => $costData['description'],
                'amount' => max(0, (float) ($costData['amount'] ?? 0)),
                'cost_date' => $costData['cost_date'] ?? now()->toDateString(),
                'vendor_name' => $costData['vendor_name'] ?? null,
                'receipt_reference' => $costData['receipt_reference'] ?? null,
                'notes' => $costData['notes'] ?? null,
            ]);

            $this->recalculateProjectTotals($project);

            return $cost;
        });
    }

    /**
     * Remove a direct expense from project.
     */
    public function removeCost(Project $project, ProjectCost $cost): void
    {
        DB::transaction(function () use ($project, $cost) {
            $cost->delete();
            $this->recalculateProjectTotals($project);
        });
    }

    /**
     * Create milestone bill for project.
     */
    public function createMilestoneBill(Project $project, array $billData): ProjectBill
    {
        return DB::transaction(function () use ($project, $billData) {
            $prefix = 'PBILL-' . Carbon::now()->format('Ymd') . '-';
            $last = ProjectBill::where('team_id', $project->team_id)->where('bill_number', 'LIKE', $prefix . '%')->latest('id')->first();
            $nextNum = $last ? str_pad((int) substr($last->bill_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
            $billNumber = $prefix . $nextNum;

            return ProjectBill::create([
                'team_id' => $project->team_id,
                'project_id' => $project->id,
                'bill_number' => $billNumber,
                'title' => $billData['title'],
                'amount' => max(0, (float) ($billData['amount'] ?? 0)),
                'due_date' => $billData['due_date'] ?? null,
                'status' => 'pending',
                'notes' => $billData['notes'] ?? null,
            ]);
        });
    }

    /**
     * Record a payment against the project.
     */
    public function recordPayment(Project $project, float $amount, ?string $paymentMethod = null, ?string $notes = null, ?int $financialAccountId = null): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Payment amount must be greater than zero.");
        }

        if ($amount > (float) $project->due_amount) {
            throw new \InvalidArgumentException("Payment amount exceeds outstanding due amount.");
        }

        DB::transaction(function () use ($project, $amount, $paymentMethod, $notes, $financialAccountId) {
            $account = null;
            if ($financialAccountId) {
                $account = \App\Models\FinancialAccount::where('team_id', $project->team_id)->find($financialAccountId);
            }

            $methodName = $account ? $account->name : ($paymentMethod ?? 'Cash');

            $project->paid_amount += $amount;
            $project->due_amount = max(0, round((float) $project->billed_total - (float) $project->paid_amount, 2));

            if ($project->due_amount <= 0 && $project->billed_total > 0) {
                $project->payment_status = 'paid';
            } else {
                $project->payment_status = 'partially_paid';
            }

            $project->save();

            if ($account) {
                $account->deposit($amount, [
                    'reference_type' => Project::class,
                    'reference_id' => $project->id,
                    'reference_number' => $project->project_code,
                    'description' => "Project payment received #{$project->project_code} - Client: " . ($project->client?->name ?? 'Direct') . ($notes ? " ({$notes})" : ""),
                ]);
            }
        });
    }

    /**
     * Transition project lifecycle status.
     */
    public function transitionStatus(Project $project, string $status): void
    {
        $validStatuses = ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid project status: '{$status}'.");
        }

        $project->update(['status' => $status]);
    }

    /**
     * Recalculate project totals after item/cost changes.
     */
    public function recalculateProjectTotals(Project $project): void
    {
        $itemsTotal = (float) $project->items()->sum('subtotal');
        $costsTotal = (float) $project->costs()->sum('amount');
        $budget = (float) $project->budget;
        $discountAmount = (float) $project->discount_amount;
        $taxAmount = (float) $project->tax_amount;

        $baseAmount = ($itemsTotal > 0) ? ($itemsTotal + $costsTotal) : $budget;
        $billedTotal = max(0, round($baseAmount - $discountAmount + $taxAmount, 2));
        $dueAmount = max(0, round($billedTotal - (float) $project->paid_amount, 2));

        $paymentStatus = 'unpaid';
        if ($billedTotal > 0) {
            if ($project->paid_amount >= $billedTotal) {
                $paymentStatus = 'paid';
            } elseif ($project->paid_amount > 0) {
                $paymentStatus = 'partially_paid';
            }
        } elseif ($billedTotal == 0) {
            $paymentStatus = 'paid';
        }

        $project->update([
            'items_total' => $itemsTotal,
            'costs_total' => $costsTotal,
            'billed_total' => $billedTotal,
            'due_amount' => $dueAmount,
            'payment_status' => $paymentStatus,
        ]);
    }

    /**
     * Generate unique project sequence PRJ-YYYYMMDD-0001
     */
    protected function generateProjectCode(?int $teamId): string
    {
        $prefix = 'PRJ-' . Carbon::now()->format('Ymd') . '-';

        $lastProject = Project::where('team_id', $teamId)
            ->where('project_code', 'LIKE', $prefix . '%')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if ($lastProject) {
            $lastNumber = (int) substr($lastProject->project_code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }
}
