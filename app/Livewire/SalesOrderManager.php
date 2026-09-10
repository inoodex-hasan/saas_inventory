<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class SalesOrderManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter_status = '';
    public string $filter_payment_status = '';
    public string $filter_customer = '';

    // Create SO Modal
    public bool $isCreateModalOpen = false;
    public ?int $customer_id = null;
    public ?int $warehouse_id = null;
    public string $order_date = '';
    public string $shipping_address = '';
    public string $notes = '';
    public array $items = []; // [['product_id' => null, 'quantity' => 1, 'unit_price' => 0.00, 'available_stock' => 0]]

    // Payment Modal
    public bool $isPaymentModalOpen = false;
    public ?SalesOrder $payingOrder = null;
    public float $paymentAmount = 0.00;

    // View SO Details Modal
    public ?SalesOrder $viewingOrder = null;

    protected function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'shipping_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
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
            'quantity' => 1,
            'unit_price' => 0.00,
            'available_stock' => 0,
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
                $this->items[$index]['unit_price'] = (float) $product->selling_price;
                
                // Check stock in current warehouse
                if ($this->warehouse_id) {
                    $stock = StockLevel::where('warehouse_id', $this->warehouse_id)
                        ->where('product_id', $productId)
                        ->first();
                    $this->items[$index]['available_stock'] = $stock ? $stock->available_quantity : 0;
                }
            }
        }
    }

    public function onWarehouseChanged(): void
    {
        foreach ($this->items as $idx => $item) {
            if (!empty($item['product_id']) && $this->warehouse_id) {
                $stock = StockLevel::where('warehouse_id', $this->warehouse_id)
                    ->where('product_id', $item['product_id'])
                    ->first();
                $this->items[$idx]['available_stock'] = $stock ? $stock->available_quantity : 0;
            }
        }
    }

    public function onCustomerSelected(): void
    {
        if ($this->customer_id) {
            $customer = Customer::find($this->customer_id);
            if ($customer && $customer->shipping_address) {
                $this->shipping_address = $customer->shipping_address;
            }
        }
    }

    public function saveSalesOrder(): void
    {
        $this->validate();

        $soService = app(SalesOrderService::class);

        $orderData = [
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'order_date' => $this->order_date,
            'notes' => $this->notes,
        ];

        $orderItems = array_map(function ($it) {
            return [
                'product_id' => $it['product_id'],
                'quantity' => (float) $it['quantity'],
                'unit_price' => (float) $it['unit_price'],
            ];
        }, $this->items);

        $order = $soService->createOrder($orderData, $orderItems);

        session()->flash('message', "Sales Order {$order->order_number} created in Draft.");
        $this->closeModal();
    }

    public function confirmOrder(int $orderId): void
    {
        try {
            $order = SalesOrder::findOrFail($orderId);
            $soService = app(SalesOrderService::class);
            $soService->confirmOrder($order);

            session()->flash('message', "Order {$order->order_number} confirmed! Required inventory reserved atomically.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function dispatchOrder(int $orderId): void
    {
        try {
            $order = SalesOrder::findOrFail($orderId);
            $soService = app(SalesOrderService::class);
            $soService->dispatchOrder($order);

            session()->flash('message', "Order {$order->order_number} dispatched! Physical stock deducted from {$order->warehouse->name}.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelOrder(int $orderId): void
    {
        try {
            $order = SalesOrder::findOrFail($orderId);
            $soService = app(SalesOrderService::class);
            $soService->cancelOrder($order);

            session()->flash('message', "Order {$order->order_number} cancelled and reservations released.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openPaymentModal(int $orderId): void
    {
        $this->payingOrder = SalesOrder::findOrFail($orderId);
        $this->paymentAmount = max(0, (float) $this->payingOrder->total_amount - (float) $this->payingOrder->paid_amount);
        $this->isPaymentModalOpen = true;
    }

    public function recordPayment(): void
    {
        if (!$this->payingOrder || $this->paymentAmount <= 0) {
            session()->flash('error', 'Please enter a valid positive payment amount.');
            return;
        }

        $soService = app(SalesOrderService::class);
        $soService->recordPayment($this->payingOrder, $this->paymentAmount);

        session()->flash('message', "Payment of $" . number_format($this->paymentAmount, 2) . " recorded for {$this->payingOrder->order_number}.");
        $this->closeModal();
    }

    public function viewDetails(int $orderId): void
    {
        $this->viewingOrder = SalesOrder::with(['items.product', 'customer', 'warehouse'])->findOrFail($orderId);
    }

    public function closeModal(): void
    {
        $this->isCreateModalOpen = false;
        $this->isPaymentModalOpen = false;
        $this->viewingOrder = null;
        $this->payingOrder = null;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->customer_id = null;
        $this->warehouse_id = null;
        $this->order_date = now()->format('Y-m-d');
        $this->shipping_address = '';
        $this->notes = '';
        $this->items = [];
        $this->paymentAmount = 0.00;
        $this->resetValidation();
    }

    public function render()
    {
        $query = SalesOrder::with(['customer', 'warehouse', 'items'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('order_number', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->filter_status, fn($q) => $q->where('status', $this->filter_status))
            ->when($this->filter_payment_status, fn($q) => $q->where('payment_status', $this->filter_payment_status))
            ->when($this->filter_customer, fn($q) => $q->where('customer_id', $this->filter_customer));

        $salesOrders = $query->latest('id')->paginate(12);
        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('livewire.sales-order-manager', [
            'salesOrders' => $salesOrders,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'products' => $products,
        ])->layout('layouts.app');
    }
}
