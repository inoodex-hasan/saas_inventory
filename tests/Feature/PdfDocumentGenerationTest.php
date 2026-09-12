<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfDocumentGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private Team $teamA;
    private User $userB;
    private Team $teamB;
    private Warehouse $warehouseA;
    private Product $productA;
    private Customer $customerA;
    private Supplier $supplierA;
    private SalesOrder $salesOrderA;
    private PurchaseOrder $purchaseOrderA;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Tenant A
        $this->userA = User::factory()->withPersonalTeam()->create();
        $this->teamA = $this->userA->currentTeam;

        $this->warehouseA = Warehouse::create([
            'team_id' => $this->teamA->id,
            'code' => 'HUB-A',
            'name' => 'Alpha Warehouse Hub',
            'is_default' => true,
        ]);

        $this->productA = Product::create([
            'team_id' => $this->teamA->id,
            'name' => 'Industrial Barcode Scanner',
            'sku' => 'SCAN-001',
            'cost_price' => 75.00,
            'selling_price' => 149.99,
            'reorder_level' => 5,
            'reorder_quantity' => 20,
        ]);

        $this->customerA = Customer::create([
            'team_id' => $this->teamA->id,
            'name' => 'Acme Corporation',
            'email' => 'billing@acme.com',
            'phone' => '+1 555-0199',
        ]);

        $this->supplierA = Supplier::create([
            'team_id' => $this->teamA->id,
            'name' => 'Global Electronics Supply',
            'email' => 'orders@global.com',
        ]);

        $this->salesOrderA = SalesOrder::create([
            'team_id' => $this->teamA->id,
            'customer_id' => $this->customerA->id,
            'warehouse_id' => $this->warehouseA->id,
            'order_number' => 'SO-20260912-0001',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'order_date' => now(),
            'subtotal' => 299.98,
            'total_amount' => 299.98,
            'paid_amount' => 299.98,
        ]);

        SalesOrderItem::create([
            'team_id' => $this->teamA->id,
            'sales_order_id' => $this->salesOrderA->id,
            'product_id' => $this->productA->id,
            'quantity' => 2,
            'unit_price' => 149.99,
            'unit_cost' => 75.00,
            'subtotal' => 299.98,
        ]);

        $this->purchaseOrderA = PurchaseOrder::create([
            'team_id' => $this->teamA->id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouseA->id,
            'order_number' => 'PO-20260912-0001',
            'status' => 'ordered',
            'order_date' => now(),
            'subtotal' => 750.00,
            'total_amount' => 750.00,
        ]);

        PurchaseOrderItem::create([
            'team_id' => $this->teamA->id,
            'purchase_order_id' => $this->purchaseOrderA->id,
            'product_id' => $this->productA->id,
            'quantity' => 10,
            'received_quantity' => 0,
            'unit_cost' => 75.00,
            'subtotal' => 750.00,
        ]);

        // Setup Tenant B
        $this->userB = User::factory()->withPersonalTeam()->create();
        $this->teamB = $this->userB->currentTeam;
    }

    public function test_authenticated_tenant_can_stream_sales_invoice_pdf(): void
    {
        $this->actingAs($this->userA);

        $response = $this->get(route('sales.invoice', $this->salesOrderA->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_tenant_can_stream_sales_packing_slip_pdf(): void
    {
        $this->actingAs($this->userA);

        $response = $this->get(route('sales.packing-slip', $this->salesOrderA->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_tenant_can_stream_purchase_order_pdf(): void
    {
        $this->actingAs($this->userA);

        $response = $this->get(route('purchases.pdf', $this->purchaseOrderA->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_other_tenant_cannot_access_tenant_documents(): void
    {
        // Act as Tenant B trying to access Tenant A's document
        $this->actingAs($this->userB);

        $response = $this->get(route('sales.invoice', $this->salesOrderA->id));
        $response->assertStatus(404); // Scoped out by TenantScope or 403
    }
}
