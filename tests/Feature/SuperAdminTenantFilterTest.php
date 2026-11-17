<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTenantFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_filter_tenants_by_verified_status()
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $verifiedOwner = User::factory()->create([
            'is_super_admin' => false,
            'phone_verified_at' => now(),
        ]);
        $verifiedTeam = Team::factory()->create([
            'user_id' => $verifiedOwner->id,
            'name' => 'Verified Team',
        ]);

        $unverifiedOwner = User::factory()->create([
            'is_super_admin' => false,
            'phone_verified_at' => null,
            'email_verified_at' => null,
        ]);
        $unverifiedTeam = Team::factory()->create([
            'user_id' => $unverifiedOwner->id,
            'name' => 'Unverified Team',
        ]);

        // 1. Filter: verified
        $responseVerified = $this->actingAs($superAdmin)->get(route('admin.dashboard', ['verified' => 'verified']));
        $responseVerified->assertOk();
        $responseVerified->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('filters.verified', 'verified')
            ->has('tenants.data', 1)
            ->where('tenants.data.0.id', $verifiedTeam->id)
        );

        // 2. Filter: unverified
        $responseUnverified = $this->actingAs($superAdmin)->get(route('admin.dashboard', ['verified' => 'unverified']));
        $responseUnverified->assertOk();
        $responseUnverified->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('filters.verified', 'unverified')
            ->has('tenants.data', 1)
            ->where('tenants.data.0.id', $unverifiedTeam->id)
        );

        // 3. No filter (all tenants)
        $responseAll = $this->actingAs($superAdmin)->get(route('admin.dashboard'));
        $responseAll->assertOk();
        $responseAll->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('tenants.data', 2)
        );
    }
}
