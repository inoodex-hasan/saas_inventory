<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter_status = '';
    public string $filter_supplier = '';

    // Create PO Modal
    public bool $isCreateModalOpen = false;
    public ?int $supplier_id = null;
    public ?int $warehouse_id = null;
    public string $order_date = '';
    public string $expected_delivery_date = '';
    public string $notes = '';
    public array $items = []; // [['product_id' => null, 'quantity_ordered' => 1, 'unit_cost' => 0.00]]

    // Receive Items Modal
    public bool $isReceiveModalOpen = false;
    public ?PurchaseOrder $receivingPo = null;
    public array $receivingItems = []; // [$itemId => ['quantity_to_receive' => 0, 'batch_number' => '', 'bin_location' => '']]

    // View PO Details Modal
    public ?PurchaseOrder $viewingPo = null;

    protected function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ];
    }

    public function mount(): void
    {
        $this->order_date = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->addItemRow();
        $defaultWh = Warehouse::where('is_default', true)->first() ?? Warehouse::first();
        if ($defaultWh) {
            $this->warehouse_id = $defaultWh->id;
        }
        $this->isCreateModalOpen = true;
    }

    public function addItemRow(): void
    {
        $this->items[] = [
            'product_id' => null,
            'quantity_ordered' => 1,
            'unit_cost' => 0.00,
        ];
    }

    public function removeItemRow(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function onProductSelected(int $index): void
    {
        $productId = $this->items[$index]['product_id'] ?? null;
        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $this->items[$index]['unit_cost'] = (float) $product->cost_price;
            }
        }
    }

    public function savePurchaseOrder(): void
    {
        $this->validate();

        $poService = app(PurchaseOrderService::class);

        $orderData = [
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'order_date' => $this->order_date,
            'expected_date' => $this->expected_delivery_date ?: null,
            'notes' => $this->notes,
        ];

        $orderItems = array_map(function ($it) {
            return [
                'product_id' => $it['product_id'],
                'quantity' => (float) $it['quantity_ordered'],
                'unit_cost' => (float) $it['unit_cost'],
            ];
        }, $this->items);

        $po = $poService->createOrder($orderData, $orderItems);

        session()->flash('message', "Purchase Order {$po->order_number} created successfully.");
        $this->closeModal();
    }

    public function markAsOrdered(int $poId): void
    {
        $teamId = Auth::user()->current_team_id;
        $po = PurchaseOrder::where('team_id', $teamId)->findOrFail($poId);
        $poService = app(PurchaseOrderService::class);
        $poService->markAsOrdered($po);

        session()->flash('message', "Purchase Order {$po->order_number} marked as Ordered.");
    }

    public function cancelOrder(int $poId): void
    {
        $teamId = Auth::user()->current_team_id;
        $po = PurchaseOrder::where('team_id', $teamId)->findOrFail($poId);
        $poService = app(PurchaseOrderService::class);
        $poService->cancelOrder($po);

        session()->flash('message', "Purchase Order {$po->order_number} cancelled.");
    }

    public function openReceiveModal(int $poId): void
    {
        $teamId = Auth::user()->current_team_id;
        $this->receivingPo = PurchaseOrder::with(['items.product', 'supplier', 'warehouse'])
            ->where('team_id', $teamId)
            ->findOrFail($poId);
        $this->receivingItems = [];

        foreach ($this->receivingPo->items as $item) {
            $remaining = (float) $item->quantity - (float) $item->received_quantity;
            $this->receivingItems[$item->id] = [
                'quantity_to_receive' => max(0, $remaining),
                'batch_number' => '',
                'bin_location' => '',
            ];
        }

        $this->isReceiveModalOpen = true;
    }

    public function processReceive(): void
    {
        if (!$this->receivingPo) {
            return;
        }

        $receivedPayload = [];
        $hasAnyQuantity = false;

        foreach ($this->receivingItems as $itemId => $data) {
            $qty = (float) ($data['quantity_to_receive'] ?? 0);
            if ($qty > 0) {
                $hasAnyQuantity = true;
                $receivedPayload[] = [
                    'item_id' => $itemId,
                    'quantity' => $qty,
                ];
            }
        }

        if (!$hasAnyQuantity) {
            session()->flash('error', 'Please enter a quantity greater than 0 for at least one item.');
            return;
        }

        $poService = app(PurchaseOrderService::class);
        $poService->receiveItems($this->receivingPo, $receivedPayload);

        session()->flash('message', "Goods received and stocked into {$this->receivingPo->warehouse->name} successfully.");
        $this->closeModal();
    }

    public function viewDetails(int $poId): void
    {
        $teamId = Auth::user()->current_team_id;
        $this->viewingPo = PurchaseOrder::with(['items.product', 'supplier', 'warehouse'])
            ->where('team_id', $teamId)
            ->findOrFail($poId);
    }

    public function closeModal(): void
    {
        $this->isCreateModalOpen = false;
        $this->isReceiveModalOpen = false;
        $this->viewingPo = null;
        $this->receivingPo = null;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->supplier_id = null;
        $this->warehouse_id = null;
        $this->order_date = now()->format('Y-m-d');
        $this->expected_delivery_date = '';
        $this->notes = '';
        $this->items = [];
        $this->resetValidation();
    }

    public function render()
    {
        $query = PurchaseOrder::with(['supplier', 'warehouse', 'items'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('order_number', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->filter_status, fn($q) => $q->where('status', $this->filter_status))
            ->when($this->filter_supplier, fn($q) => $q->where('supplier_id', $this->filter_supplier));

        $purchaseOrders = $query->latest('id')->paginate(12);
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = $this->isCreateModalOpen ? Warehouse::where('is_active', true)->orderBy('name')->get() : collect();
        $products = $this->isCreateModalOpen ? Product::where('is_active', true)->orderBy('name')->get() : collect();

        return view('livewire.purchase-order-manager', [
            'purchaseOrders' => $purchaseOrders,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
        ])->layout('layouts.app');
    }
}
