<?php

namespace App\Livewire;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TenantSettingsManager extends Component
{
    public string $name = '';
    public ?string $legal_name = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $tax_id = null;
    public ?string $address_line1 = null;
    public ?string $address_line2 = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $postal_code = null;
    public ?string $country = null;
    public string $currency_symbol = '$';
    public string $currency_code = 'USD';
    public string $invoice_prefix = 'INV-';
    public string $po_prefix = 'PO-';

    public string $activeTab = 'profile'; // profile, localization, documents

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:100',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:30',
            'country' => 'nullable|string|max:100',
            'currency_symbol' => 'required|string|max:10',
            'currency_code' => 'required|string|max:10',
            'invoice_prefix' => 'required|string|max:20',
            'po_prefix' => 'required|string|max:20',
        ];
    }

    public function mount(): void
    {
        $team = Auth::user()->currentTeam;

        if (!$team) {
            abort(403, 'No active tenant workspace selected.');
        }

        $this->name = $team->name ?? '';
        $this->legal_name = $team->legal_name;
        $this->email = $team->email;
        $this->phone = $team->phone;
        $this->tax_id = $team->tax_id;
        $this->address_line1 = $team->address_line1;
        $this->address_line2 = $team->address_line2;
        $this->city = $team->city;
        $this->state = $team->state;
        $this->postal_code = $team->postal_code;
        $this->country = $team->country;
        $this->currency_symbol = $team->currency_symbol ?: '$';
        $this->currency_code = $team->currency_code ?: 'USD';
        $this->invoice_prefix = $team->invoice_prefix ?: 'INV-';
        $this->po_prefix = $team->po_prefix ?: 'PO-';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function save(): void
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        // Check if user has permission to update tenant settings (owner or admin)
        $isOwnerOrAdmin = $user->isSuperAdmin() 
            || $user->ownsTeam($team) 
            || $user->hasTeamRole($team, 'admin');

        if (!$isOwnerOrAdmin) {
            session()->flash('error', 'Only Company Owners and Administrators can update organization settings.');
            return;
        }

        $validated = $this->validate();

        $team->update($validated);

        session()->flash('success', 'Company profile and branding settings updated successfully!');
    }

    public function render()
    {
        return view('livewire.tenant-settings-manager')
            ->layout('layouts.app');
    }
}
