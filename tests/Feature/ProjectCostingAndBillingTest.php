<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CostCategory;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectBill;
use App\Models\ProjectCost;
use App\Models\ProjectItem;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProjectCostingAndBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;
    private Client $client;
    private CostCategory $costCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->user->current_team_id = $this->team->id;
        $this->user->save();
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Central Depot',
            'code' => 'CD-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Cat6 Ethernet Cable Roll 305m',
            'sku' => 'CAB-CAT6-305M',
            'cost_price' => 5000,
            'unit_price' => 8500,
            'is_active' => true,
        ]);

        StockLevel::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        $this->client = Client::create([
            'team_id' => $this->team->id,
            'name' => 'Apex Holdings Ltd',
            'company_name' => 'Apex Group',
            'phone' => '+880 1711 223344',
            'email' => 'procurement@apex.com',
            'address' => 'Gulshan-2, Dhaka',
            'tax_number' => 'BIN-99221144',
            'is_active' => true,
        ]);

        $this->costCategory = CostCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Subcontractor Labor',
            'description' => 'Field installation and technician wages',
            'is_active' => true,
        ]);
    }

    public function test_user_can_view_projects_index_page_with_metrics_and_filters(): void
    {
        Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0001',
            'name' => 'Data Center Network Revamp',
            'status' => 'in_progress',
            'budget' => 150000,
            'items_total' => 25500,
            'costs_total' => 15000,
            'discount_amount' => 5000,
            'tax_amount' => 2000,
            'billed_total' => 37500,
            'paid_amount' => 20000,
            'due_amount' => 17500,
            'payment_status' => 'partially_paid',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.project_code', 'PRJ-20261014-0001')
            ->where('metrics.total_projects', 1)
            ->where('metrics.in_progress', 1)
            ->has('clients')
            ->has('filters')
        );
    }

    public function test_user_can_view_create_project_page(): void
    {
        $response = $this->get(route('projects.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Create')
            ->has('nextProjectCode')
            ->has('clients')
            ->has('costCategories')
            ->has('products')
            ->has('warehouses')
        );
    }

    public function test_user_can_create_project_with_existing_client_and_materials_deducting_stock(): void
    {
        $initialStock = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');

        $payload = [
            'client_mode' => 'existing',
            'client_id' => $this->client->id,
            'name' => 'Campus Wi-Fi Deployment',
            'status' => 'in_progress',
            'budget' => 100000,
            'discount_amount' => 2000,
            'tax_amount' => 1500,
            'paid_amount' => 5000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'description' => 'Installation of 50 access points and optical cabling',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'item_name' => 'Cat6 Ethernet Cable Roll 305m',
                    'quantity' => 3,
                    'unit_price' => 8500, // 3 * 8500 = 25500
                    'notes' => 'Allocated for Building A',
                ],
            ],
            'costs' => [
                [
                    'cost_category_id' => $this->costCategory->id,
                    'description' => 'Cabling Technician Team Wages',
                    'amount' => 8000,
                    'vendor_name' => 'Fast Wiring Ltd',
                    'receipt_reference' => 'VOUCH-001',
                ],
            ],
        ];

        $response = $this->post(route('projects.store'), $payload);

        $project = Project::latest('id')->first();
        $this->assertNotNull($project);
        $response->assertRedirect(route('projects.show', $project->id));

        $this->assertEquals($this->client->id, $project->client_id);
        $this->assertEquals('Campus Wi-Fi Deployment', $project->name);
        $this->assertEquals(25500, (float) $project->items_total);
        $this->assertEquals(8000, (float) $project->costs_total);

        // Billed Total: (25500 + 8000) - 2000 + 1500 = 33000
        $this->assertEquals(33000, (float) $project->billed_total);
        $this->assertEquals(5000, (float) $project->paid_amount);
        $this->assertEquals(28000, (float) $project->due_amount);
        $this->assertEquals('partially_paid', $project->payment_status);

        // Stock deduction check: 20 - 3 = 17
        $newStock = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals($initialStock - 3, $newStock);
    }

    public function test_user_can_create_project_with_new_inline_client(): void
    {
        $payload = [
            'client_mode' => 'new',
            'new_client_name' => 'Delta Tech Solutions',
            'new_client_company' => 'Delta Corp',
            'new_client_phone' => '+880 1999 888777',
            'new_client_email' => 'info@deltatech.com',
            'new_client_address' => 'Banani, Dhaka',
            'new_client_tax_number' => 'BIN-883311',
            'name' => 'Server Room Fire Suppression Setup',
            'status' => 'planning',
            'budget' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 0,
        ];

        $response = $this->post(route('projects.store'), $payload);

        $client = Client::where('name', 'Delta Tech Solutions')->first();
        $this->assertNotNull($client);
        $this->assertEquals($this->team->id, $client->team_id);

        $project = Project::latest('id')->first();
        $this->assertNotNull($project);
        $this->assertEquals($client->id, $project->client_id);
        $this->assertEquals(50000, (float) $project->budget);
        $this->assertEquals(50000, (float) $project->billed_total);
        $this->assertEquals('unpaid', $project->payment_status);
    }

    public function test_user_can_view_project_workbench_show_page_with_profitability_metrics(): void
    {
        $project = Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0002',
            'name' => 'Security Audit & Firewall Migration',
            'status' => 'in_progress',
            'budget' => 80000,
            'items_total' => 17000,
            'costs_total' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'billed_total' => 27000,
            'paid_amount' => 27000,
            'due_amount' => 0,
            'payment_status' => 'paid',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('projects.show', $project->id));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Show')
            ->where('project.project_code', 'PRJ-20261014-0002')
            ->has('analytics')
            ->has('products')
            ->has('warehouses')
            ->has('costCategories')
        );
    }

    public function test_user_can_add_and_remove_items_to_project_with_stock_restoration(): void
    {
        $project = Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0003',
            'name' => 'Smart Office IoT Installation',
            'status' => 'in_progress',
            'budget' => 60000,
            'items_total' => 0,
            'costs_total' => 0,
            'billed_total' => 60000,
            'paid_amount' => 0,
            'due_amount' => 60000,
            'payment_status' => 'unpaid',
            'created_by' => $this->user->id,
        ]);

        $stockBefore = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');

        // 1. Add item
        $addResponse = $this->post(route('projects.items.add', $project->id), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'item_name' => 'Cat6 Ethernet Cable Roll 305m',
            'quantity' => 2,
            'unit_price' => 8500, // 17000
            'notes' => 'Floor 2 wiring',
        ]);
        $addResponse->assertSessionHas('success');

        $stockAfterAdd = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals($stockBefore - 2, $stockAfterAdd);

        $item = ProjectItem::where('project_id', $project->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(17000, (float) $item->subtotal);

        // 2. Remove item
        $removeResponse = $this->delete(route('projects.items.remove', [$project->id, $item->id]));
        $removeResponse->assertSessionHas('success');

        $stockAfterRemove = (float) StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals($stockBefore, $stockAfterRemove);

        $this->assertDatabaseMissing('project_items', ['id' => $item->id]);
    }

    public function test_user_can_add_and_remove_direct_project_expenses(): void
    {
        $project = Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0004',
            'name' => 'HVAC Ducting & Server Chiller Upgrade',
            'status' => 'in_progress',
            'budget' => 200000,
            'items_total' => 0,
            'costs_total' => 0,
            'billed_total' => 200000,
            'paid_amount' => 0,
            'due_amount' => 200000,
            'payment_status' => 'unpaid',
            'created_by' => $this->user->id,
        ]);

        // Add Cost
        $response = $this->post(route('projects.costs.add', $project->id), [
            'cost_category_id' => $this->costCategory->id,
            'description' => 'Crane and heavy lifting equipment rental',
            'amount' => 12500,
            'cost_date' => now()->toDateString(),
            'vendor_name' => 'Metro Crane Services',
            'receipt_reference' => 'CRN-8812',
        ]);
        $response->assertSessionHas('success');

        $cost = ProjectCost::where('project_id', $project->id)->first();
        $this->assertNotNull($cost);
        $this->assertEquals(12500, (float) $cost->amount);

        $project->refresh();
        $this->assertEquals(12500, (float) $project->costs_total);

        // Remove Cost
        $delResponse = $this->delete(route('projects.costs.remove', [$project->id, $cost->id]));
        $delResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('project_costs', ['id' => $cost->id]);
        $project->refresh();
        $this->assertEquals(0, (float) $project->costs_total);
    }

    public function test_user_can_create_milestone_bill(): void
    {
        $project = Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0005',
            'name' => 'Telecom Tower Maintenance',
            'status' => 'in_progress',
            'budget' => 120000,
            'items_total' => 0,
            'costs_total' => 0,
            'billed_total' => 120000,
            'paid_amount' => 0,
            'due_amount' => 120000,
            'payment_status' => 'unpaid',
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('projects.bills.create', $project->id), [
            'title' => 'Milestone 1 - Foundation & Structural Anchor Audit',
            'amount' => 40000,
            'due_date' => now()->addDays(7)->toDateString(),
            'notes' => 'Payable upon engineer signoff',
        ]);

        $response->assertSessionHas('success');

        $bill = ProjectBill::where('project_id', $project->id)->first();
        $this->assertNotNull($bill);
        $this->assertStringStartsWith('PBILL-', $bill->bill_number);
        $this->assertEquals(40000, (float) $bill->amount);
    }

    public function test_user_can_record_payment_and_overpayment_is_prevented(): void
    {
        $project = Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0006',
            'name' => 'Solar Power Backup Installation',
            'status' => 'in_progress',
            'budget' => 50000,
            'items_total' => 0,
            'costs_total' => 0,
            'billed_total' => 50000,
            'paid_amount' => 10000,
            'due_amount' => 40000,
            'payment_status' => 'partially_paid',
            'created_by' => $this->user->id,
        ]);

        // Attempt overpayment
        $overResponse = $this->post(route('projects.pay', $project->id), [
            'amount' => 45000, // Due is only 40000
            'payment_method' => 'bank_transfer',
        ]);
        $overResponse->assertSessionHasErrors('amount');

        // Valid payment
        $validResponse = $this->post(route('projects.pay', $project->id), [
            'amount' => 40000,
            'payment_method' => 'bank_transfer',
            'notes' => 'Final settlement wire transfer',
        ]);
        $validResponse->assertSessionHas('success');

        $project->refresh();
        $this->assertEquals(50000, (float) $project->paid_amount);
        $this->assertEquals(0, (float) $project->due_amount);
        $this->assertEquals('paid', $project->payment_status);
    }

    public function test_user_can_transition_project_lifecycle_status(): void
    {
        $project = Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0007',
            'name' => 'Fiber Optic Splicing',
            'status' => 'planning',
            'budget' => 30000,
            'items_total' => 0,
            'costs_total' => 0,
            'billed_total' => 30000,
            'paid_amount' => 0,
            'due_amount' => 30000,
            'payment_status' => 'unpaid',
            'created_by' => $this->user->id,
        ]);

        $response = $this->post(route('projects.transition', $project->id), [
            'status' => 'completed',
        ]);
        $response->assertSessionHas('success');

        $project->refresh();
        $this->assertEquals('completed', $project->status);
    }

    public function test_user_can_update_project_details(): void
    {
        $project = Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0008',
            'name' => 'Initial Title',
            'status' => 'planning',
            'budget' => 20000,
            'items_total' => 0,
            'costs_total' => 0,
            'billed_total' => 20000,
            'paid_amount' => 0,
            'due_amount' => 20000,
            'payment_status' => 'unpaid',
            'created_by' => $this->user->id,
        ]);

        $response = $this->put(route('projects.update', $project->id), [
            'client_id' => $this->client->id,
            'name' => 'Updated Enterprise Title',
            'status' => 'in_progress',
            'budget' => 35000,
            'discount_amount' => 1000,
            'tax_amount' => 500,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'description' => 'Updated scope',
        ]);

        $response->assertRedirect(route('projects.show', $project->id));

        $project->refresh();
        $this->assertEquals('Updated Enterprise Title', $project->name);
        $this->assertEquals('in_progress', $project->status);
        $this->assertEquals(35000, (float) $project->budget);
        // (35000 - 1000 + 500) = 34500
        $this->assertEquals(34500, (float) $project->billed_total);
    }

    public function test_user_can_delete_project(): void
    {
        $project = Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0009',
            'name' => 'Cancelled Contract',
            'status' => 'cancelled',
            'budget' => 10000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->delete(route('projects.destroy', $project->id));
        $response->assertRedirect(route('projects.index'));

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_user_can_generate_project_bill_pdf(): void
    {
        $project = Project::create([
            'team_id' => $this->team->id,
            'client_id' => $this->client->id,
            'project_code' => 'PRJ-20261014-0010',
            'name' => 'Cloud Migration Statement',
            'status' => 'completed',
            'budget' => 45000,
            'items_total' => 0,
            'costs_total' => 0,
            'billed_total' => 45000,
            'paid_amount' => 45000,
            'due_amount' => 0,
            'payment_status' => 'paid',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('projects.pdf', $project->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_tenant_isolation_prevents_cross_tenant_project_access(): void
    {
        // Another tenant team
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $otherClient = Client::create([
            'team_id' => $otherTeam->id,
            'name' => 'Other Tenant Client',
        ]);

        $otherProject = Project::create([
            'team_id' => $otherTeam->id,
            'client_id' => $otherClient->id,
            'project_code' => 'PRJ-20261014-9999',
            'name' => 'Confidential Defense Project',
            'status' => 'planning',
            'budget' => 999999,
        ]);

        // Attempt accessing from $this->user's current team
        $response = $this->get(route('projects.show', $otherProject->id));
        $this->assertTrue(in_array($response->status(), [403, 404]));

        $pdfResponse = $this->get(route('projects.pdf', $otherProject->id));
        $this->assertTrue(in_array($pdfResponse->status(), [403, 404]));
    }
}
