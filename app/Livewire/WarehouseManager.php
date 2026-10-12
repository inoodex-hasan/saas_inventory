<?php

namespace App\Livewire;

use App\Models\StockLevel;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class WarehouseManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $isModalOpen = false;
    public ?int $warehouse_id = null;

    // Form fields
    public string $name = '';
    public string $code = '';
    public string $address_line1 = '';
    public string $city = '';
    public string $state = '';
    public string $postal_code = '';
    public string $phone = '';
    public string $email = '';
    public bool $is_default = false;
    public bool $is_active = true;

    // Inspection
    public ?Warehouse $selectedWarehouse = null;
    public array $warehouseStock = [];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit(int $id): void
    {
        $teamId = Auth::user()->current_team_id;
        $warehouse = Warehouse::where('team_id', $teamId)->findOrFail($id);
        $this->warehouse_id = $warehouse->id;
        $this->name = $warehouse->name;
        $this->code = $warehouse->code ?? '';
        $this->address_line1 = $warehouse->address_line1 ?? '';
        $this->city = $warehouse->city ?? '';
        $this->state = $warehouse->state ?? '';
        $this->postal_code = $warehouse->postal_code ?? '';
        $this->phone = $warehouse->phone ?? '';
        $this->email = $warehouse->email ?? '';
        $this->is_default = (bool) $warehouse->is_default;
        $this->is_active = (bool) $warehouse->is_active;
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        $teamId = Auth::user()->current_team_id;

        // If marked default, unset other defaults in this team
        if ($this->is_default) {
            Warehouse::where('team_id', $teamId)->update(['is_default' => false]);
        }

        if ($this->warehouse_id) {
            $warehouse = Warehouse::where('team_id', $teamId)->findOrFail($this->warehouse_id);
            $warehouse->update([
                'name' => $this->name,
                'code' => $this->code,
                'address_line1' => $this->address_line1,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'phone' => $this->phone,
                'email' => $this->email,
                'is_default' => $this->is_default,
                'is_active' => $this->is_active,
            ]);
            session()->flash('message', 'Warehouse updated successfully.');
        } else {
            // First warehouse created is automatically default
            $isFirst = Warehouse::where('team_id', $teamId)->count() === 0;

            Warehouse::create([
                'team_id' => $teamId,
                'name' => $this->name,
                'code' => $this->code,
                'address_line1' => $this->address_line1,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'phone' => $this->phone,
                'email' => $this->email,
                'is_default' => $this->is_default || $isFirst,
                'is_active' => $this->is_active,
            ]);
            session()->flash('message', 'Warehouse created successfully.');
        }

        $this->closeModal();
    }

    public function setAsDefault(int $id): void
    {
        $teamId = Auth::user()->current_team_id;
        Warehouse::where('team_id', $teamId)->update(['is_default' => false]);
        
        $warehouse = Warehouse::where('team_id', $teamId)->findOrFail($id);
        $warehouse->update(['is_default' => true]);

        session()->flash('message', "{$warehouse->name} set as default warehouse hub.");
    }

    public function delete(int $id): void
    {
        $teamId = Auth::user()->current_team_id;
        $warehouse = Warehouse::where('team_id', $teamId)->findOrFail($id);
        
        // Prevent deleting default warehouse if others exist
        if ($warehouse->is_default && Warehouse::where('team_id', $teamId)->count() > 1) {
            session()->flash('error', 'Please set another warehouse as default before deleting this one.');
            return;
        }

        $warehouse->delete();
        session()->flash('message', 'Warehouse deleted successfully.');
    }

    public function inspectStock(int $id): void
    {
        $teamId = Auth::user()->current_team_id;
        $this->selectedWarehouse = Warehouse::where('team_id', $teamId)->findOrFail($id);
        
        $stockLevels = StockLevel::with('product')
            ->where('warehouse_id', $id)
            ->where('quantity', '>', 0)
            ->get();

        $items = [];
        foreach ($stockLevels as $level) {
            if (!$level->product) {
                continue;
            }
            $onHand = (float) $level->quantity;
            $reserved = (float) $level->reserved_quantity;
            $available = max(0, $onHand - $reserved);
            $cost = (float) $level->product->cost_price;

            $items[] = [
                'product_name' => $level->product->name,
                'sku' => $level->product->sku,
                'quantity_on_hand' => $onHand,
                'quantity_reserved' => $reserved,
                'quantity_available' => $available,
                'cost_price' => $cost,
                'value' => $onHand * $cost,
            ];
        }

        $this->warehouseStock = $items;
    }

    private function resetForm(): void
    {
        $this->warehouse_id = null;
        $this->name = '';
        $this->code = '';
        $this->address_line1 = '';
        $this->city = '';
        $this->state = '';
        $this->postal_code = '';
        $this->phone = '';
        $this->email = '';
        $this->is_default = false;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $warehouses = Warehouse::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('code', 'like', "%{$this->search}%")
                      ->orWhere('city', 'like', "%{$this->search}%")
                      ->orWhere('state', 'like', "%{$this->search}%");
                });
            })
            ->latest('id')
            ->paginate(9);

        return view('livewire.warehouse-manager', compact('warehouses'))->layout('layouts.app');
    }
}
