<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Scopes\TenantScope;
use App\Models\Team;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Dummy test model for tenant isolation verification
class DummyTenantItem extends Model
{
    use BelongsToTenant;

    protected $table = 'dummy_tenant_items';
    protected $guarded = [];
}

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Create a temporary table for testing BelongsToTenant trait
        Schema::create('dummy_tenant_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('dummy_tenant_items');
        parent::tearDown();
    }

    public function test_user_registration_seeds_roles_and_assigns_owner_role(): void
    {
        $creator = new CreateNewUser();
        $user = $creator->create([
            'name' => 'Alice Tenant',
            'email' => 'alice@tenant.test',
            'phone' => '01711000006',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertNotNull($user->current_team_id);
        
        setPermissionsTeamId($user->current_team_id);
        $this->assertTrue($user->hasRole('owner'));
        $this->assertTrue($user->hasPermissionTo('manage-products'));
    }

    public function test_tenant_data_is_automatically_isolated_by_team_id(): void
    {
        // 1. Create Tenant A & User A
        $creator = new CreateNewUser();
        $userA = $creator->create([
            'name' => 'User A',
            'email' => 'userA@test.com',
            'phone' => '01711000007',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $teamA = $userA->currentTeam;

        // 2. Create Tenant B & User B
        $userB = $creator->create([
            'name' => 'User B',
            'email' => 'userB@test.com',
            'phone' => '01711000008',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $teamB = $userB->currentTeam;

        // 3. User A creates an item while logged in
        $this->actingAs($userA);
        $itemA = DummyTenantItem::create(['name' => 'Warehouse Alpha Product']);
        
        // Assert team_id is automatically assigned to team A
        $this->assertEquals($teamA->id, $itemA->team_id);

        // Assert User A sees 1 item
        $this->assertEquals(1, DummyTenantItem::count());

        // 4. User B logs in
        $this->actingAs($userB);

        // Assert User B CANNOT see User A's item (Tenant Isolation)
        $this->assertEquals(0, DummyTenantItem::count());

        // 5. User B creates an item
        $itemB = DummyTenantItem::create(['name' => 'Warehouse Beta Product']);
        $this->assertEquals($teamB->id, $itemB->team_id);
        $this->assertEquals(1, DummyTenantItem::count());
        $this->assertEquals('Warehouse Beta Product', DummyTenantItem::first()->name);

        // 6. Global scope bypass allows admin query across tenants
        $totalItems = DummyTenantItem::withoutGlobalScope(TenantScope::class)->count();
        $this->assertEquals(2, $totalItems);
    }
}
