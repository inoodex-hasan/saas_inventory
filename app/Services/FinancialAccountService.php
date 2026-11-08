<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialAccountService
{
    /**
     * Ensure default accounts exist for a tenant team.
     */
    public function ensureDefaultAccounts(?int $teamId): void
    {
        if (!$teamId) {
            return;
        }

        $count = FinancialAccount::where('team_id', $teamId)->count();
        if ($count > 0) {
            return;
        }

        FinancialAccount::create([
            'team_id' => $teamId,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'institution_name' => 'Cash Desk',
            'account_number' => 'CASH-01',
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'is_default' => true,
            'is_active' => true,
            'notes' => 'Primary physical cash register for daily retail counter transactions',
            'created_by' => Auth::id(),
        ]);

        FinancialAccount::create([
            'team_id' => $teamId,
            'name' => 'BRAC Bank Operating A/C',
            'type' => 'bank',
            'institution_name' => 'BRAC Bank',
            'account_number' => '1501204892001',
            'account_type' => 'current',
            'branch' => 'Principal Branch',
            'routing_number' => '060261122',
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'is_default' => false,
            'is_active' => true,
            'notes' => 'Primary corporate bank account for vendor transfers and client EFT deposits',
            'created_by' => Auth::id(),
        ]);

        FinancialAccount::create([
            'team_id' => $teamId,
            'name' => 'bKash Merchant Primary',
            'type' => 'mfs',
            'institution_name' => 'bKash',
            'account_number' => '01711002233',
            'account_type' => 'merchant',
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'is_default' => false,
            'is_active' => true,
            'notes' => 'bKash Merchant wallet for QR and payment gateway collections',
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Get active accounts grouped or ordered for payment dropdowns.
     */
    public function getActiveAccountsForTeam(?int $teamId)
    {
        if (!$teamId) {
            return collect();
        }

        return FinancialAccount::where('team_id', $teamId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve the default financial account for a team.
     */
    public function getDefaultAccount(?int $teamId): ?FinancialAccount
    {
        if (!$teamId) {
            return null;
        }

        $account = FinancialAccount::where('team_id', $teamId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (!$account) {
            $account = FinancialAccount::where('team_id', $teamId)->where('is_active', true)->first();
        }

        if (!$account) {
            $this->ensureDefaultAccounts($teamId);
            $account = FinancialAccount::where('team_id', $teamId)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first() ?: FinancialAccount::where('team_id', $teamId)->where('is_active', true)->first();
        }

        return $account;
    }
}
