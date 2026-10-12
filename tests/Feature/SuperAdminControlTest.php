<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Livewire\SuperAdminDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;
    protected Team $clientTeam;

    protected function setUp(): void
    {
        parent::setUp();

        // Create platform super administrator
        $this->superAdmin = User::factory()->withPersonalTeam()->create([
            'is_super_admin' => true,
        ]);

        // Create normal renting client
        $this->regularUser = User::factory()->withPersonalTeam()->create([
            'is_super_admin' => false,
        ]);
        $this->clientTeam = $this->regularUser->currentTeam;
    }

    public function test_non_super_admin_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(SuperAdminDashboard::class);
    }

    public function test_super_admin_can_suspend_and_reactivate_client_tenant(): void
    {
        $this->assertEquals('active', $this->clientTeam->status);

        // Suspend the tenant
        Livewire::actingAs($this->superAdmin)
            ->test(SuperAdminDashboard::class)
            ->call('toggleStatus', $this->clientTeam->id)
            ->assertSee("has been suspended");

        $this->clientTeam->refresh();
        $this->assertEquals('suspended', $this->clientTeam->status);
        $this->assertTrue($this->clientTeam->isSuspended());

        // Reactivate the tenant
        Livewire::actingAs($this->superAdmin)
            ->test(SuperAdminDashboard::class)
            ->call('toggleStatus', $this->clientTeam->id)
            ->assertSee("has been reactivated");

        $this->clientTeam->refresh();
        $this->assertEquals('active', $this->clientTeam->status);
        $this->assertFalse($this->clientTeam->isSuspended());
    }

    public function test_suspended_tenant_users_are_redirected_to_suspension_page(): void
    {
        // Suspend client team
        $this->clientTeam->update(['status' => 'suspended']);

        // Regular user attempts to access dashboard
        $response = $this->actingAs($this->regularUser)->get(route('dashboard'));

        $response->assertRedirect(route('tenant.suspended'));

        // Suspended page itself should be accessible
        $suspendedResponse = $this->actingAs($this->regularUser)->get(route('tenant.suspended'));
        $suspendedResponse->assertStatus(200);
        $suspendedResponse->assertSee('Organization Workspace Suspended');
    }

    public function test_super_admin_is_never_blocked_by_tenant_suspension(): void
    {
        // Suspend client team
        $this->clientTeam->update(['status' => 'suspended']);

        // Super Admin switches to suspended team and visits dashboard
        $this->superAdmin->forceFill(['current_team_id' => $this->clientTeam->id])->save();

        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_super_admin_can_switch_tenant_context(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(SuperAdminDashboard::class)
            ->call('switchToTenant', $this->clientTeam->id)
            ->assertRedirect(route('dashboard'));

        $this->superAdmin->refresh();
        $this->assertEquals($this->clientTeam->id, $this->superAdmin->current_team_id);
    }

    public function test_super_admin_personal_team_is_excluded_from_client_list(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(SuperAdminDashboard::class)
            ->assertSee($this->clientTeam->name)
            ->assertDontSee($this->superAdmin->personalTeam()->name);
    }
}
