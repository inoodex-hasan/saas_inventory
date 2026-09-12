<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Livewire\ProductManager;
use App\Services\CatalogImportExportService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected Warehouse $warehouse;
    protected CatalogImportExportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->service = app(CatalogImportExportService::class);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Import Hub 1',
            'code' => 'IMP-01',
            'is_active' => true,
        ]);
    }

    public function test_sample_template_download_generates_valid_csv(): void
    {
        $response = $this->actingAs($this->user);
        $download = $this->service->generateSampleCsv();

        $this->assertEquals('text/csv; charset=UTF-8', $download->headers->get('Content-Type'));
        $this->assertStringContainsString('product_import_sample_template.csv', $download->headers->get('Content-Disposition'));
    }

    public function test_catalog_export_streams_all_tenant_products(): void
    {
        $category = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Power Equipment',
        ]);

        Product::create([
            'team_id' => $this->team->id,
            'category_id' => $category->id,
            'name' => 'Generator 5000W',
            'sku' => 'GEN-5000',
            'unit' => 'pcs',
            'cost_price' => 450.00,
            'selling_price' => 799.00,
            'unit_price' => 799.00,
            'reorder_level' => 3,
            'is_active' => true,
        ]);

        $download = $this->service->exportCsv($this->team->id);

        $this->assertEquals('text/csv; charset=UTF-8', $download->headers->get('Content-Type'));
        $this->assertStringContainsString('catalog_export_', $download->headers->get('Content-Disposition'));
    }

    public function test_bulk_csv_import_creates_products_and_auto_resolves_categories(): void
    {
        $csvContent = <<<CSV
sku,name,category,unit,cost_price,selling_price,reorder_level,barcode,description,initial_stock
IM-SKU-101,Industrial Router 4G,Networking,pcs,60.00,120.00,5,880998811,High speed cellular router,15
IM-SKU-102,Network Switch 24P,Networking,pcs,110.00,210.00,8,880998812,Managed Gigabit Switch,10
CSV;

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_');
        file_put_contents($tempFile, $csvContent);

        $this->actingAs($this->user);

        $results = $this->service->importCsv(
            $tempFile,
            $this->team->id,
            $this->warehouse->id,
            $this->user->id
        );

        $this->assertEquals(2, $results['created']);
        $this->assertEquals(0, $results['updated']);
        $this->assertEquals(0, $results['skipped']);

        // Check products created in database
        $this->assertDatabaseHas('products', [
            'team_id' => $this->team->id,
            'sku' => 'IM-SKU-101',
            'name' => 'Industrial Router 4G',
            'cost_price' => 60.00,
            'unit_price' => 120.00,
        ]);

        // Check category auto-created
        $this->assertDatabaseHas('product_categories', [
            'team_id' => $this->team->id,
            'name' => 'Networking',
        ]);

        // Check opening stock allocated to warehouse
        $product = Product::where('sku', 'IM-SKU-101')->first();
        $this->assertNotNull($product);

        $stockLevel = StockLevel::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertNotNull($stockLevel);
        $this->assertEquals(15, (int) $stockLevel->quantity);

        @unlink($tempFile);
    }

    public function test_bulk_csv_import_gracefully_updates_duplicate_skus(): void
    {
        Product::create([
            'team_id' => $this->team->id,
            'name' => 'Old Product Name',
            'sku' => 'EXISTING-SKU',
            'unit' => 'pcs',
            'cost_price' => 10.00,
            'unit_price' => 20.00,
            'reorder_level' => 2,
            'is_active' => true,
        ]);

        $csvContent = <<<CSV
sku,name,category,unit,cost_price,selling_price,reorder_level,initial_stock
EXISTING-SKU,Updated Product Name,Electronics,pcs,15.00,35.00,10,0
CSV;

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_update_');
        file_put_contents($tempFile, $csvContent);

        $this->actingAs($this->user);

        $results = $this->service->importCsv(
            $tempFile,
            $this->team->id,
            null,
            $this->user->id
        );

        $this->assertEquals(0, $results['created']);
        $this->assertEquals(1, $results['updated']);

        $this->assertDatabaseHas('products', [
            'team_id' => $this->team->id,
            'sku' => 'EXISTING-SKU',
            'name' => 'Updated Product Name',
            'cost_price' => 15.00,
            'unit_price' => 35.00,
            'reorder_level' => 10,
        ]);

        @unlink($tempFile);
    }

    public function test_multi_tenant_isolation_in_csv_imports(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $csvContent = <<<CSV
sku,name,unit,cost_price,selling_price
TENANT-A-SKU,Exclusive Tenant A Tool,pcs,25.00,50.00
CSV;

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_iso_');
        file_put_contents($tempFile, $csvContent);

        $this->actingAs($this->user);
        $this->service->importCsv($tempFile, $this->team->id, null, $this->user->id);

        // Verify Tenant B has NO access to this SKU
        $this->actingAs($otherUser);
        $this->assertNull(Product::where('sku', 'TENANT-A-SKU')->first());

        @unlink($tempFile);
    }
}
