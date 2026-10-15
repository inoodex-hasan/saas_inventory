<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SuperAdminInertiaTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $clientUser;
    private Team $clientTeam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->withPersonalTeam()->create([
            'is_super_admin' => true,
        ]);

        $this->clientUser = User::factory()->withPersonalTeam()->create([
            'is_super_admin' => false,
        ]);
        $this->clientTeam = $this->clientUser->currentTeam;
    }

    public function test_super_admin_can_view_control_plane_via_inertia(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('metrics')
            ->has('tenants.data')
        );
    }

    public function test_super_admin_can_toggle_tenant_suspension_via_inertia(): void
    {
        $this->assertEquals('active', $this->clientTeam->status);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.tenants.toggle-status', $this->clientTeam));
        $response->assertRedirect();

        $this->clientTeam->refresh();
        $this->assertEquals('suspended', $this->clientTeam->status);

        // Reactivate
        $this->actingAs($this->superAdmin)->post(route('admin.tenants.toggle-status', $this->clientTeam));
        $this->clientTeam->refresh();
        $this->assertEquals('active', $this->clientTeam->status);
    }

    public function test_super_admin_can_switch_and_exit_tenant_context(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.tenants.switch', $this->clientTeam));
        $response->assertRedirect(route('dashboard'));

        $this->superAdmin->refresh();
        $this->assertEquals($this->clientTeam->id, $this->superAdmin->current_team_id);

        // Exit tenant context
        $exitResponse = $this->actingAs($this->superAdmin)->get(route('admin.exit-tenant'));
        $exitResponse->assertRedirect(route('admin.dashboard'));

        $this->superAdmin->refresh();
        $this->assertNull($this->superAdmin->current_team_id);
    }
}
