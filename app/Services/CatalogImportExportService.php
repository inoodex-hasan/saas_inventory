<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogImportExportService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Generate and stream a standard sample CSV template for product imports.
     */
    public function generateSampleCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="product_import_sample_template.csv"',
        ];

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'sku',
                'name',
                'category',
                'unit',
                'cost_price',
                'selling_price',
                'reorder_level',
                'barcode',
                'description',
                'initial_stock',
            ]);

            // Sample rows
            fputcsv($handle, [
                'SKU-1001',
                'Heavy Duty Drill 18V',
                'Power Tools',
                'pcs',
                '45.00',
                '89.99',
                '10',
                '8801234567890',
                'Cordless brushless high-torque drill',
                '25',
            ]);

            fputcsv($handle, [
                'SKU-1002',
                'Safety Goggles Clear Lens',
                'Safety Gear',
                'box',
                '5.50',
                '14.00',
                '20',
                '8801234567891',
                'Anti-fog ANSI Z87 certified eye protection',
                '50',
            ]);

            fclose($handle);
        }, 'product_import_sample_template.csv', $headers);
    }

    /**
     * Export all tenant products and current stock levels to a streamed CSV.
     */
    public function exportCsv(int $teamId): StreamedResponse
    {
        $filename = 'catalog_export_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($teamId) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'SKU',
                'Product Name',
                'Category',
                'Unit',
                'Cost Price',
                'Selling Price',
                'Reorder Level',
                'Total Stock On Hand',
                'Barcode',
                'Status',
            ]);

            Product::with(['category', 'stockLevels'])
                ->where('team_id', $teamId)
                ->orderBy('name')
                ->chunk(200, function ($products) use ($handle) {
                    foreach ($products as $p) {
                        fputcsv($handle, [
                            $p->sku,
                            $p->name,
                            $p->category->name ?? 'Uncategorized',
                            $p->unit,
                            number_format((float) $p->cost_price, 2, '.', ''),
                            number_format((float) ($p->unit_price ?: $p->selling_price), 2, '.', ''),
                            $p->reorder_level,
                            $p->total_stock,
                            $p->barcode ?? '',
                            $p->is_active ? 'Active' : 'Inactive',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, $headers);
    }

    /**
     * Parse and import CSV rows into the tenant's product catalog.
     */
    public function importCsv(string $filePath, int $teamId, ?int $initialWarehouseId = null, int $userId = 1): array
    {
        $summary = [
            'total_rows' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $summary['errors'][] = 'Uploaded file could not be read or does not exist.';
            return $summary;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $summary['errors'][] = 'Failed to open CSV file.';
            return $summary;
        }

        // Check for BOM and strip it
        $firstLine = fgets($handle);
        if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
            $firstLine = substr($firstLine, 3);
        }
        rewind($handle);

        $header = fgetcsv($handle);
        if (!$header) {
            $summary['errors'][] = 'CSV file appears to be empty.';
            fclose($handle);
            return $summary;
        }

        // Normalize header names (lowercase, trimmed, underscores)
        $normalizedHeaders = array_map(function ($col) {
            return strtolower(trim(str_replace([' ', '-'], '_', $col)));
        }, $header);

        $skuIdx = array_search('sku', $normalizedHeaders);
        $nameIdx = array_search('name', $normalizedHeaders);

        if ($skuIdx === false || $nameIdx === false) {
            $summary['errors'][] = 'CSV is missing required headers: "sku" and "name".';
            fclose($handle);
            return $summary;
        }

        $categoryIdx = array_search('category', $normalizedHeaders);
        $unitIdx = array_search('unit', $normalizedHeaders);
        $costIdx = array_search('cost_price', $normalizedHeaders);
        $sellingIdx = array_search('selling_price', $normalizedHeaders);
        if ($sellingIdx === false) {
            $sellingIdx = array_search('unit_price', $normalizedHeaders);
        }
        $reorderIdx = array_search('reorder_level', $normalizedHeaders);
        $barcodeIdx = array_search('barcode', $normalizedHeaders);
        $descIdx = array_search('description', $normalizedHeaders);
        $stockIdx = array_search('initial_stock', $normalizedHeaders);

        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $summary['total_rows']++;

            $sku = isset($row[$skuIdx]) ? trim($row[$skuIdx]) : '';
            $name = isset($row[$nameIdx]) ? trim($row[$nameIdx]) : '';

            if (!$sku || !$name) {
                $summary['skipped']++;
                $summary['errors'][] = "Row #{$rowNumber}: Skipped because SKU or Name was blank.";
                continue;
            }

            $categoryName = ($categoryIdx !== false && isset($row[$categoryIdx])) ? trim($row[$categoryIdx]) : null;
            $unit = ($unitIdx !== false && isset($row[$unitIdx]) && trim($row[$unitIdx])) ? trim($row[$unitIdx]) : 'pcs';
            $costPrice = ($costIdx !== false && isset($row[$costIdx]) && is_numeric(trim($row[$costIdx]))) ? (float) trim($row[$costIdx]) : 0.00;
            $sellingPrice = ($sellingIdx !== false && isset($row[$sellingIdx]) && is_numeric(trim($row[$sellingIdx]))) ? (float) trim($row[$sellingIdx]) : 0.00;
            $reorderLevel = ($reorderIdx !== false && isset($row[$reorderIdx]) && is_numeric(trim($row[$reorderIdx]))) ? (int) trim($row[$reorderIdx]) : 0;
            $barcode = ($barcodeIdx !== false && isset($row[$barcodeIdx])) ? trim($row[$barcodeIdx]) : null;
            $description = ($descIdx !== false && isset($row[$descIdx])) ? trim($row[$descIdx]) : null;
            $initialStock = ($stockIdx !== false && isset($row[$stockIdx]) && is_numeric(trim($row[$stockIdx]))) ? (float) trim($row[$stockIdx]) : 0.0;

            try {
                DB::transaction(function () use (
                    $teamId,
                    $sku,
                    $name,
                    $categoryName,
                    $unit,
                    $costPrice,
                    $sellingPrice,
                    $reorderLevel,
                    $barcode,
                    $description,
                    $initialStock,
                    $initialWarehouseId,
                    &$summary
                ) {
                    // 1. Resolve or create Category for this tenant
                    $categoryId = null;
                    if ($categoryName) {
                        $category = ProductCategory::withoutGlobalScopes()
                            ->where('team_id', $teamId)
                            ->where('name', $categoryName)
                            ->first();

                        if (!$category) {
                            $category = ProductCategory::create([
                                'team_id' => $teamId,
                                'name' => $categoryName,
                            ]);
                        }
                        $categoryId = $category->id;
                    }

                    // 2. Check if product already exists
                    $product = Product::withoutGlobalScopes()
                        ->where('team_id', $teamId)
                        ->where('sku', $sku)
                        ->first();

                    if ($product) {
                        $product->update([
                            'name' => $name,
                            'category_id' => $categoryId ?: $product->category_id,
                            'unit' => $unit,
                            'cost_price' => $costPrice,
                            'selling_price' => $sellingPrice,
                            'unit_price' => $sellingPrice,
                            'reorder_level' => $reorderLevel,
                            'barcode' => $barcode ?: $product->barcode,
                            'description' => $description ?: $product->description,
                        ]);
                        $summary['updated']++;
                    } else {
                        $product = Product::create([
                            'team_id' => $teamId,
                            'sku' => $sku,
                            'name' => $name,
                            'category_id' => $categoryId,
                            'unit' => $unit,
                            'cost_price' => $costPrice,
                            'selling_price' => $sellingPrice,
                            'unit_price' => $sellingPrice,
                            'reorder_level' => $reorderLevel,
                            'barcode' => $barcode,
                            'description' => $description,
                            'is_active' => true,
                        ]);
                        $summary['created']++;
                    }

                    // 3. Record Initial Stock if requested & warehouse is valid
                    if ($initialStock > 0 && $initialWarehouseId) {
                        $warehouseExists = Warehouse::withoutGlobalScopes()
                            ->where('team_id', $teamId)
                            ->where('id', $initialWarehouseId)
                            ->exists();

                        if ($warehouseExists) {
                            $this->stockService->stockIn(
                                $initialWarehouseId,
                                $product->id,
                                $initialStock,
                                $costPrice,
                                null,
                                null,
                                null,
                                'Bulk CSV Catalog Import'
                            );
                        }
                    }
                });
            } catch (\Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = "Row #{$rowNumber} (SKU: {$sku}): " . $e->getMessage();
            }
        }

        fclose($handle);
        return $summary;
    }
}
