<?php

namespace App\Imports;

use App\Classes\Common;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCustomField;
use App\Models\ProductDetails;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Warehouse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Str;

class ProductImport implements ToArray, WithHeadingRow
{
    /**
     * Columns treated as "standard". Any other non-empty column found in the CSV
     * will be stored as a custom field (product_custom_fields) when
     * request('store_unknown_as_custom') is truthy.
     */
    protected array $standardColumns = [
        // product-level (old/new)
        'name','description','barcode_symbology','item_code',
        'category','brand','unit','unit_name','unit_code','warehouse','slug',
        'sku','barcode','image_url',

        // details-level (old/new)
        'tax','tax_label','tax_rate','mrp',
        'purchase_price','sales_price',
        'purchase_tax_type','sales_tax_type',
        'purchase_price_tax_mode','sales_price_tax_mode',
        'stock_quantitiy_alert','quantity_alert',
        'opening_stock','opening_stock_qty',
        'opening_stock_unit','opening_stock_date',
        'wholesale_price','wholesale_quantity',
    ];

    /** Summary counters for UI feedback */
    protected int $inserted = 0;
    protected int $customSaved = 0;
    protected array $autoCreated = [
        'categories' => [],
        'brands'     => [],
        'units'      => [],
        'taxes'      => [],
    ];

    public function incInserted(): void { $this->inserted++; }
    public function incCustomSaved(int $n = 1): void { $this->customSaved += $n; }
    public function noteAuto(string $type, string $name): void { $this->autoCreated[$type][$name] = true; }

    public function summary(): array
    {
        return [
            'inserted'    => $this->inserted,
            'customSaved' => $this->customSaved,
            'autoCreated' => [
                'categories' => array_keys($this->autoCreated['categories']),
                'brands'     => array_keys($this->autoCreated['brands']),
                'units'      => array_keys($this->autoCreated['units']),
                'taxes'      => array_keys($this->autoCreated['taxes']),
            ],
        ];
    }

    public function array(array $rows)
    {
        DB::transaction(function () use ($rows) {
            $user = user();

            // Flags from request
            $storeUnknownAsCustom = request()->boolean('store_unknown_as_custom', false);
            $autoCreateMasters    = request()->boolean('auto_create_masters', false);
            $autoCreateTaxes      = request()->boolean('auto_create_taxes', false);

            foreach ($rows as $row) {
                // Normalize row so both old and new CSVs work
                $product = $this->normalizeRow($row);

                // Minimal required fields after normalization (keep your original rules)
                $required = [
                    'name','barcode_symbology','item_code',
                    'category','brand','unit',
                    'mrp','purchase_price','sales_price',
                    'purchase_tax_type','sales_tax_type',
                    'opening_stock','opening_stock_date',
                    'wholesale_price','wholesale_quantity',
                    'stock_quantitiy_alert',
                ];
                foreach ($required as $key) {
                    if (!array_key_exists($key, $product)) {
                        throw new ApiException("Field missing from header: {$key}");
                    }
                }

                $productName = trim((string) $product['name']);
                if ($productName === '') {
                    // empty name row; ignore
                    continue;
                }

                // Name uniqueness (same as your original)
                if (Product::where('name', $productName)->exists()) {
                    throw new ApiException('Product ' . $productName . ' Already Exists');
                }

                // Category
                $categoryName = trim((string) $product['category']);
                $category = Category::where('name', $categoryName)->first();
                if (!$category) {
                    if ($autoCreateMasters) {
                        $category = Category::firstOrCreate(
                            ['name' => $categoryName, 'company_id' => company()->id],
                            ['slug' => Str::slug($categoryName)]
                        );
                        $this->noteAuto('categories', $categoryName);
                    } else {
                        throw new ApiException('Category Not Found... ' . $categoryName);
                    }
                }

                // Brand
                $brandName = trim((string) $product['brand']);
                $brand = Brand::where('name', $brandName)->first();
                if (!$brand) {
                    if ($autoCreateMasters) {
                        $brand = Brand::firstOrCreate(
                            ['name' => $brandName, 'company_id' => company()->id],
                            ['slug' => Str::slug($brandName)]
                        );
                        $this->noteAuto('brands', $brandName);
                    } else {
                        throw new ApiException('Brand Not Found... ' . $brandName);
                    }
                }

                // Unit (normalized to 'unit' = unit_name)
                $unitName = trim((string) $product['unit']);
                $unit = Unit::where('name', $unitName)->first();
                if (!$unit) {
                    if ($autoCreateMasters) {
                        $unit = Unit::firstOrCreate(
                            ['name' => $unitName, 'company_id' => company()->id],
                            ['short_name' => $unitName]
                        );
                        $this->noteAuto('units', $unitName);
                    } else {
                        throw new ApiException('Unit Not Found... ' . $unitName);
                    }
                }

                // Barcode symbology
                $barcodeSymbology = trim((string) $product['barcode_symbology']);
                if ($barcodeSymbology === "" || !in_array($barcodeSymbology, ['CODE128', 'CODE39'])) {
                    throw new ApiException('Barcode symoblogy must be CODE128 or CODE39');
                }

                // Item code uniqueness
                $itemCode = trim((string) $product['item_code']);
                if (Product::where('item_code', $itemCode)->exists()) {
                    throw new ApiException('Item Code ' . $itemCode . ' Already Exists');
                }

                // Tax resolve (by name, or by tax_label/tax_rate)
                $tax = $this->resolveTax($product, $autoCreateTaxes);

                // Tax types
                $purchaseTaxType = trim((string) $product['purchase_tax_type']);
                $salesTaxType    = trim((string) $product['sales_tax_type']);
                if ($tax) {
                    if (!in_array(strtolower($purchaseTaxType), ['exclusive','inclusive'])) {
                        throw new ApiException('Purchase Tax Type must be inclusive or exclusive');
                    }
                    if (!in_array(strtolower($salesTaxType), ['exclusive','inclusive'])) {
                        throw new ApiException('Sales Tax Type must be inclusive or exclusive');
                    }
                }

                // Warehouse for product owner record
                if (array_key_exists('warehouse', $product) && trim((string)$product['warehouse']) !== '') {
                    $inputWarehouse = Warehouse::where('name', $product['warehouse'])->first();
                    $currentWarehouse = warehouse();
                    $createdWarehouseId = $inputWarehouse && $inputWarehouse->id ? $inputWarehouse->id : $currentWarehouse->id;
                } else {
                    $createdWarehouseId = warehouse()->id;
                }

                // Create product
                $newProduct = new Product();
                $newProduct->name          = $productName;
                $newProduct->warehouse_id  = $createdWarehouseId;
                $newProduct->slug          = Str::slug($productName, '-');
                $newProduct->barcode_symbology = $barcodeSymbology;
                $newProduct->item_code     = $itemCode;
                $newProduct->category_id   = $category->id;
                $newProduct->brand_id      = $brand->id;
                $newProduct->unit_id       = $unit->id;
                $newProduct->user_id       = $user->id;
                // Optional fields if present
                if (!empty($product['description'])) {
                    $newProduct->description = (string)$product['description'];
                }
                if (!empty($product['image_url'])) {
                    $newProduct->image = (string)$product['image_url'];
                }
                $newProduct->save();
                $this->incInserted();

                // Numeric cleaning
                $mrp            = $this->numericOrNull($product['mrp']);
                $purchasePrice  = $this->numericOrZero($product['purchase_price']);
                $salesPrice     = $this->numericOrZero($product['sales_price']);
                $wholesalePrice = $this->numericOrNull($product['wholesale_price']);

                $stockQuantityAlert = trim((string)$product['stock_quantitiy_alert']);
                $stockQuantityAlert = ($stockQuantityAlert !== '') ? (int)$stockQuantityAlert : null;

                $openingStock = trim((string)$product['opening_stock']);
                $openingStock = ($openingStock !== '') ? (int)$openingStock : null;

                $wholesaleQuantity = trim((string)$product['wholesale_quantity']);
                $wholesaleQuantity = ($wholesaleQuantity !== '') ? (int)$wholesaleQuantity : null;

                $openingStockDate = trim((string)$product['opening_stock_date']);
                $openingStockDate = ($openingStockDate !== '') ? $openingStockDate : null;

                // Create ProductDetails for all warehouses (your original behavior)
                $allWarehouses = Warehouse::select('id')->get();
                foreach ($allWarehouses as $allWarehouse) {
                    $details = new ProductDetails();
                    $details->warehouse_id       = $allWarehouse->id;
                    $details->product_id         = $newProduct->id;
                    $details->tax_id             = $tax ? $tax->id : null;
                    $details->purchase_tax_type  = $purchaseTaxType !== '' ? strtolower($purchaseTaxType) : 'exclusive';
                    $details->sales_tax_type     = $salesTaxType !== '' ? strtolower($salesTaxType) : 'exclusive';
                    $details->mrp                = $mrp;
                    $details->purchase_price     = $purchasePrice;
                    $details->sales_price        = $salesPrice;
                    $details->stock_quantitiy_alert = $stockQuantityAlert;
                    $details->opening_stock      = $openingStock;
                    $details->opening_stock_date = $openingStockDate;
                    $details->wholesale_price    = $wholesalePrice;
                    $details->wholesale_quantity = $wholesaleQuantity;
                    $details->save();

                    Common::recalculateOrderStock($details->warehouse_id, $newProduct->id);
                }

                // Save extra CSV columns as product_custom_fields (UPSERT) if enabled
                if ($storeUnknownAsCustom) {
                    $saved = $this->saveCustomFields($row, $newProduct->id, (int)$createdWarehouseId);
                    if ($saved > 0) {
                        $this->incCustomSaved($saved);
                    }
                }
            }
        });
    }

    /**
     * Normalize incoming row keys to what the importer expects.
     */
    protected function normalizeRow(array $row): array
    {
        $r = $row; // keep original

        // Ensure description key
        if (!array_key_exists('description', $r)) {
            $r['description'] = '';
        }

        // unit_name/unit_code -> unit (prefer name)
        if (!isset($r['unit']) || trim((string)$r['unit']) === '') {
            if (!empty($r['unit_name'])) {
                $r['unit'] = $r['unit_name'];
            }
        }

        // quantity_alert -> stock_quantitiy_alert
        if (!isset($r['stock_quantitiy_alert']) && isset($r['quantity_alert'])) {
            $r['stock_quantitiy_alert'] = $r['quantity_alert'];
        }

        // opening_stock_qty -> opening_stock
        if (!isset($r['opening_stock']) && isset($r['opening_stock_qty'])) {
            $r['opening_stock'] = $r['opening_stock_qty'];
        }

        // purchase_price_tax_mode -> purchase_tax_type
        if (!isset($r['purchase_tax_type']) && isset($r['purchase_price_tax_mode'])) {
            $r['purchase_tax_type'] = $this->mapTaxMode((string)$r['purchase_price_tax_mode']);
        }

        // sales_price_tax_mode -> sales_tax_type
        if (!isset($r['sales_tax_type']) && isset($r['sales_price_tax_mode'])) {
            $r['sales_tax_type'] = $this->mapTaxMode((string)$r['sales_price_tax_mode']);
        }

        // If 'tax' missing but tax_label exists, set tax to label (rate handled later)
        if (!isset($r['tax']) && !empty($r['tax_label'])) {
            $r['tax'] = $r['tax_label'];
        }

        // Default fallbacks for expected keys
        foreach ([
            'purchase_tax_type' => 'exclusive',
            'sales_tax_type'    => 'exclusive',
            'mrp'               => 0,
            'purchase_price'    => 0,
            'sales_price'       => 0,
            'wholesale_price'   => null,
            'wholesale_quantity'=> null,
            'opening_stock'     => 0,
            'opening_stock_date'=> null,
            'stock_quantitiy_alert' => null,
        ] as $k => $default) {
            if (!array_key_exists($k, $r)) {
                $r[$k] = $default;
            }
        }

        return $r;
    }

    /**
     * Map UI text like "With Tax"/"Without Tax" to "inclusive"/"exclusive"
     */
    protected function mapTaxMode(string $mode): string
    {
        $m = strtolower(trim($mode));
        if (in_array($m, ['with tax','inclusive','include'])) {
            return 'inclusive';
        }
        if (in_array($m, ['without tax','exclusive','exclude'])) {
            return 'exclusive';
        }
        return 'exclusive';
    }

    protected function numericOrZero($val): float
    {
        if ($val === null || $val === '') return 0.0;
        $s = str_replace([',','-'], '', (string)$val);
        return is_numeric($s) ? (float)$s : 0.0;
    }

    protected function numericOrNull($val): ?float
    {
        if ($val === null || $val === '') return null;
        $s = str_replace([',','-'], '', (string)$val);
        return is_numeric($s) ? (float)$s : null;
    }

    /**
     * Resolve Tax by name (`tax`) or by (`tax_label`,`tax_rate`).
     * If $autoCreate is true, create missing taxes.
     */
    protected function resolveTax(array $product, bool $autoCreate): ?Tax
    {
        $taxNameRaw = trim((string)($product['tax'] ?? ''));
        $labelRaw   = trim((string)($product['tax_label'] ?? ''));
        $rateRaw    = $product['tax_rate'] ?? null;
        $rate       = is_numeric($rateRaw) ? (float)$rateRaw : null;

        // Case 1: explicit `tax` name provided
        if ($taxNameRaw !== '') {
            // If the "name" is actually numeric, treat it as a rate (e.g., "18")
            if (is_numeric($taxNameRaw)) {
                $rate  = (float)$taxNameRaw;
                $label = ($labelRaw !== '') ? $labelRaw : "Tax {$rate}%";
                $existing = Tax::where('name', $label)->first();
                if ($existing) return $existing;

                if (!$autoCreate) {
                    throw new ApiException('Tax Not Found');
                }

                $tax = Tax::firstOrCreate(
                    ['name' => $label, 'company_id' => company()->id],
                    ['rate' => $rate ?? 0.0]
                );
                $this->noteAuto('taxes', $label);
                return $tax;
            }

            $found = Tax::where('name', $taxNameRaw)->first();
            if ($found) return $found;

            if (!$autoCreate) {
                throw new ApiException('Tax Not Found');
            }

            $tax = Tax::firstOrCreate(
                ['name' => $taxNameRaw, 'company_id' => company()->id],
                ['rate' => $rate ?? 0.0]
            );
            $this->noteAuto('taxes', $taxNameRaw);
            return $tax;
        }

        // Case 2: label+rate path
        if ($labelRaw !== '' || $rate !== null) {
            $label = ($labelRaw !== '') ? $labelRaw : "Tax " . ($rate ?? 0.0) . "%";
            $found = Tax::where('name', $label)->first();
            if ($found) return $found;

            if (!$autoCreate) {
                throw new ApiException('Tax Not Found');
            }

            $tax = Tax::firstOrCreate(
                ['name' => $label, 'company_id' => company()->id],
                ['rate' => $rate ?? 0.0]
            );
            $this->noteAuto('taxes', $label);
            return $tax;
        }

        // No tax data provided
        return null;
    }

    /**
     * Persist unknown CSV columns into product_custom_fields (UPSERT).
     * Returns how many fields were saved/updated.
     */
    protected function saveCustomFields(array $row, int $productId, int $warehouseId): int
    {
        $saved = 0;

        foreach ($row as $col => $val) {
            $fieldName = trim((string)$col);

            // skip empty header keys and standard/known columns
            if ($fieldName === '' || in_array($fieldName, $this->standardColumns, true)) {
                continue;
            }

            // ignore null/empty values
            if ($val === null) {
                continue;
            }
            $fieldValue = is_scalar($val) ? (string)$val : json_encode($val);
            $fieldValue = trim($fieldValue);
            if ($fieldValue === '') {
                continue;
            }

            ProductCustomField::withoutGlobalScopes()->updateOrCreate(
                [
                    'product_id'   => $productId,
                    'warehouse_id' => $warehouseId,
                    'field_name'   => $fieldName,
                ],
                [
                    'field_value'  => mb_substr($fieldValue, 0, 191),
                ]
            );

            $saved++;
        }

        return $saved;
    }
}
