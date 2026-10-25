<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAndTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or update the Platform Super-Admin (SaaS Vendor / Operator)
        $superAdmin = User::firstOrCreate(
            ['email' => 'hello@inoodex.com'],
            [
                'name'     => 'Inoodex',
                'password' => Hash::make('hello@inoodex.com'),
            ]
        );
        $superAdmin->forceFill(['is_super_admin' => true])->save();
    }
}

