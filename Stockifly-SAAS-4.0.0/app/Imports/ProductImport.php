<?php

namespace App\Imports;

use App\Classes\Common;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomField;
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
     * If true, only extra columns whose header matches custom_fields.name
     * (for the current company) will be stored into product_custom_fields.
     */
    public function __construct(
        protected bool $useCustomFieldMaster = false
    ) {}

    /**
     * Columns treated as "standard". Any other non-empty column found in the CSV
     * will be stored as a custom field (product_custom_fields).
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

    public function array(array $products)
    {
        DB::transaction(function () use ($products) {
            $user = user();

            foreach ($products as $row) {
                // Normalize row keys so old/new templates both work
                $product = $this->normalizeRow($row);

                // Minimal required fields after normalization
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

                $productName = trim((string)$product['name']);
                if ($productName === '') {
                    continue;
                }

                // Uniqueness by name (same as original)
                $exists = Product::where('name', $productName)->count();
                if ($exists > 0) {
                    throw new ApiException('Product ' . $productName . ' Already Exists');
                }

                // Category
                $categoryName = trim((string)$product['category']);
                $category = Category::where('name', $categoryName)->first();
                if (!$category) {
                    throw new ApiException('Category Not Found... ' . $categoryName);
                }

                // Brand
                $brandName = trim((string)$product['brand']);
                $brand = Brand::where('name', $brandName)->first();
                if (!$brand) {
                    throw new ApiException('Brand Not Found... ' . $brandName);
                }

                // Unit
                $unitName = trim((string)$product['unit']);
                $unit = Unit::where('name', $unitName)->first();
                if (!$unit) {
                    throw new ApiException('Unit Not Found... ' . $unitName);
                }

                $barcodeSymbology = trim((string)$product['barcode_symbology']);
                if ($barcodeSymbology === "" || !in_array($barcodeSymbology, ['CODE128', 'CODE39'])) {
                    throw new ApiException('Barcode symoblogy must be CODE128 or CODE39');
                }

                $itemCode = trim((string)$product['item_code']);
                $isItemCodeAlreadyExists = Product::where('item_code', $itemCode)->count();
                if ($isItemCodeAlreadyExists > 0) {
                    throw new ApiException('Item Code ' . $itemCode . ' Already Exists');
                }

                // Tax
                $taxName = trim((string)($product['tax'] ?? ''));
                $tax = null;
                if ($taxName !== '') {
                    $tax = Tax::where('name', $taxName)->first();
                    if (!$tax) {
                        throw new ApiException('Tax Not Found');
                    }
                } elseif (!empty($product['tax_label']) || isset($product['tax_rate'])) {
                    // tax_label/tax_rate -> create or fetch
                    $label = (string)($product['tax_label'] ?? 'Tax');
                    $rate  = isset($product['tax_rate']) ? (float)$product['tax_rate'] : 0.0;
                    $tax = Tax::firstOrCreate(['name' => $label], ['rate' => $rate]);
                }

                // Tax-type validation when tax is present
                $purchaseTaxType = trim((string)$product['purchase_tax_type']);
                if ($tax && !in_array(strtolower($purchaseTaxType), ['exclusive', 'inclusive'])) {
                    throw new ApiException('Purchase Tax Type must be inclusive or exclusive');
                }
                $salesTaxType = trim((string)$product['sales_tax_type']);
                if ($tax && !in_array(strtolower($salesTaxType), ['exclusive', 'inclusive'])) {
                    throw new ApiException('Sales Tax Type must be inclusive or exclusive');
                }

                // Warehouse resolution
                $allWarehouses = Warehouse::select('id')->get();
                if (array_key_exists('warehouse', $product) && trim((string)$product['warehouse']) !== '') {
                    $warehouse = Warehouse::where('name', $product['warehouse'])->first();
                    $currentWarehouse = warehouse();
                    $createdWarehouseId = $warehouse && $warehouse->id ? $warehouse->id : $currentWarehouse->id;
                } else {
                    $warehouse = warehouse();
                    $createdWarehouseId = $warehouse->id;
                }

                // Create product
                $newProduct = new Product();
                $newProduct->name = $productName;
                $newProduct->warehouse_id = $createdWarehouseId;
                $newProduct->slug = Str::slug($productName, '-');
                $newProduct->barcode_symbology = $barcodeSymbology;
                $newProduct->item_code = $itemCode;
                $newProduct->category_id = $category->id;
                $newProduct->brand_id = $brand->id;
                $newProduct->unit_id = $unit->id;
                $newProduct->user_id = $user->id;
                $newProduct->save();

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

                // Create ProductDetails for all warehouses (original behavior)
                foreach ($allWarehouses as $allWarehouse) {
                    $newProductDetails = new ProductDetails();
                    $newProductDetails->warehouse_id = $allWarehouse->id;
                    $newProductDetails->product_id = $newProduct->id;
                    $newProductDetails->tax_id = $tax ? $tax->id : null;
                    $newProductDetails->purchase_tax_type = $purchaseTaxType !== '' ? strtolower($purchaseTaxType) : 'exclusive';
                    $newProductDetails->sales_tax_type = $salesTaxType !== '' ? strtolower($salesTaxType) : 'exclusive';
                    $newProductDetails->mrp = $mrp;
                    $newProductDetails->purchase_price = $purchasePrice;
                    $newProductDetails->sales_price = $salesPrice;
                    $newProductDetails->stock_quantitiy_alert = $stockQuantityAlert;
                    $newProductDetails->opening_stock = $openingStock;
                    $newProductDetails->opening_stock_date = $openingStockDate;
                    $newProductDetails->wholesale_price = $wholesalePrice;
                    $newProductDetails->wholesale_quantity = $wholesaleQuantity;
                    $newProductDetails->save();

                    Common::recalculateOrderStock($newProductDetails->warehouse_id, $newProduct->id);
                }

                // Save extra CSV columns as product_custom_fields (UPSERT)
                $this->saveCustomFields($row, $newProduct->id, (int)$createdWarehouseId);
            }
        });
    }

    /**
     * Normalize incoming row keys to what the importer expects.
     */
    protected function normalizeRow(array $r): array
    {
        if (!array_key_exists('description', $r)) {
            $r['description'] = '';
        }
        // unit_name/unit_code -> unit (use name)
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

        // ensure mandatory presence (defaults)
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
        if (in_array($m, ['with tax', 'inclusive', 'include'])) return 'inclusive';
        if (in_array($m, ['without tax', 'exclusive', 'exclude'])) return 'exclusive';
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
     * Persist unknown CSV columns into product_custom_fields (UPSERT).
     * If $useCustomFieldMaster is true, only save fields whose names exist in custom_fields for the current company.
     */
    protected function saveCustomFields(array $row, int $productId, int $warehouseId): void
    {
        // Build allow-list if requested
        $allowedNames = null;
        if ($this->useCustomFieldMaster) {
            $allowedNames = CustomField::query()
                ->where('company_id', company()->id)
                // ->where('active', 1) // uncomment to honor "active" flag
                ->pluck('name')
                ->map(fn($n) => strtolower(trim($n)))
                ->toArray();
        }

        foreach ($row as $col => $val) {
            $fieldName = trim((string)$col);

            // skip empty header keys and standard/known columns
            if ($fieldName === '' || in_array($fieldName, $this->standardColumns, true)) {
                continue;
            }
            if ($val === null) {
                continue;
            }

            // when using master, enforce allow-list
            if (is_array($allowedNames)) {
                if (!in_array(strtolower($fieldName), $allowedNames, true)) {
                    continue;
                }
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
        }
    }
}
