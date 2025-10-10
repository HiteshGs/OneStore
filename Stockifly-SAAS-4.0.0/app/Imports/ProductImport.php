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
     * @param bool $saveUnknownColumns         Save extra CSV columns into product_custom_fields
     * @param bool $useCustomFieldMaster       Only allow columns whose names exist in custom_fields for this company
     * @param bool $autoCreateCustomFieldNames If a header doesn’t exist in custom_fields and useCustomFieldMaster is true,
     *                                         create it automatically (company-scoped)
     */
    public function __construct(
        protected bool $saveUnknownColumns = true,
        protected bool $useCustomFieldMaster = false,
        protected bool $autoCreateCustomFieldNames = false
    ) {}

    /**
     * Known system columns. Anything else is treated as a candidate custom field.
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

    public function array(array $rows)
    {
        DB::transaction(function () use ($rows) {
            $user = user();

            foreach ($rows as $row) {
                // Normalize to expected keys
                $product = $this->normalizeRow($row);

                // required keys
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

                // uniqueness by name (your legacy behavior)
                if (Product::where('name', $productName)->exists()) {
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

                // Barcode
                $barcodeSymbology = trim((string)$product['barcode_symbology']);
                if ($barcodeSymbology === "" || !in_array($barcodeSymbology, ['CODE128', 'CODE39'])) {
                    throw new ApiException('Barcode symoblogy must be CODE128 or CODE39');
                }

                // Item code uniqueness
                $itemCode = trim((string)$product['item_code']);
                if (Product::where('item_code', $itemCode)->exists()) {
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
                    $label = (string)($product['tax_label'] ?? 'Tax');
                    $rate  = isset($product['tax_rate']) ? (float)$product['tax_rate'] : 0.0;
                    $tax = Tax::firstOrCreate(['name' => $label], ['rate' => $rate]);
                }

                // Tax types (validate only when tax present)
                $purchaseTaxType = trim((string)$product['purchase_tax_type']);
                if ($tax && !in_array(strtolower($purchaseTaxType), ['exclusive', 'inclusive'])) {
                    throw new ApiException('Purchase Tax Type must be inclusive or exclusive');
                }
                $salesTaxType = trim((string)$product['sales_tax_type']);
                if ($tax && !in_array(strtolower($salesTaxType), ['exclusive', 'inclusive'])) {
                    throw new ApiException('Sales Tax Type must be inclusive or exclusive');
                }

                // Warehouse (createdWarehouseId is used as the custom-field warehouse_id)
                $allWarehouses = Warehouse::select('id')->get();
                if (array_key_exists('warehouse', $product) && trim((string)$product['warehouse']) !== '') {
                    $w = Warehouse::where('name', $product['warehouse'])->first();
                    $createdWarehouseId = $w?->id ?? warehouse()->id;
                } else {
                    $createdWarehouseId = warehouse()->id;
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

                // Numbers
                $mrp            = $this->numericOrNull($product['mrp']);
                $purchasePrice  = $this->numericOrZero($product['purchase_price']);
                $salesPrice     = $this->numericOrZero($product['sales_price']);
                $wholesalePrice = $this->numericOrNull($product['wholesale_price']);

                $stockAlert     = trim((string)$product['stock_quantitiy_alert']);
                $stockAlert     = ($stockAlert !== '') ? (int)$stockAlert : null;

                $openingStock   = trim((string)$product['opening_stock']);
                $openingStock   = ($openingStock !== '') ? (int)$openingStock : null;

                $wholesaleQty   = trim((string)$product['wholesale_quantity']);
                $wholesaleQty   = ($wholesaleQty !== '') ? (int)$wholesaleQty : null;

                $openingDate    = trim((string)$product['opening_stock_date']);
                $openingDate    = ($openingDate !== '') ? $openingDate : null;

                // Details for ALL warehouses (your legacy behavior)
                foreach ($allWarehouses as $aw) {
                    $pd = new ProductDetails();
                    $pd->warehouse_id = $aw->id;
                    $pd->product_id = $newProduct->id;
                    $pd->tax_id = $tax?->id;
                    $pd->purchase_tax_type = $purchaseTaxType !== '' ? strtolower($purchaseTaxType) : 'exclusive';
                    $pd->sales_tax_type = $salesTaxType !== '' ? strtolower($salesTaxType) : 'exclusive';
                    $pd->mrp = $mrp;
                    $pd->purchase_price = $purchasePrice;
                    $pd->sales_price = $salesPrice;
                    $pd->stock_quantitiy_alert = $stockAlert;
                    $pd->opening_stock = $openingStock;
                    $pd->opening_stock_date = $openingDate;
                    $pd->wholesale_price = $wholesalePrice;
                    $pd->wholesale_quantity = $wholesaleQty;
                    $pd->save();

                    Common::recalculateOrderStock($pd->warehouse_id, $newProduct->id);
                }

                // Custom fields
                $this->saveCustomFields($row, $newProduct->id, (int)$createdWarehouseId);
            }
        });
    }

    /** Normalize incoming row */
    protected function normalizeRow(array $r): array
    {
        if (!array_key_exists('description', $r)) {
            $r['description'] = '';
        }
        if (!isset($r['unit']) || trim((string)$r['unit']) === '') {
            if (!empty($r['unit_name'])) $r['unit'] = $r['unit_name'];
        }
        if (!isset($r['stock_quantitiy_alert']) && isset($r['quantity_alert'])) {
            $r['stock_quantitiy_alert'] = $r['quantity_alert'];
        }
        if (!isset($r['opening_stock']) && isset($r['opening_stock_qty'])) {
            $r['opening_stock'] = $r['opening_stock_qty'];
        }
        if (!isset($r['purchase_tax_type']) && isset($r['purchase_price_tax_mode'])) {
            $r['purchase_tax_type'] = $this->mapTaxMode((string)$r['purchase_price_tax_mode']);
        }
        if (!isset($r['sales_tax_type']) && isset($r['sales_price_tax_mode'])) {
            $r['sales_tax_type'] = $this->mapTaxMode((string)$r['sales_price_tax_mode']);
        }
        if (!isset($r['tax']) && !empty($r['tax_label'])) {
            $r['tax'] = $r['tax_label'];
        }

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
            if (!array_key_exists($k, $r)) $r[$k] = $default;
        }

        return $r;
    }

    protected function mapTaxMode(string $mode): string
    {
        $m = strtolower(trim($mode));
        if (in_array($m, ['with tax','inclusive','include'])) return 'inclusive';
        if (in_array($m, ['without tax','exclusive','exclude'])) return 'exclusive';
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
     * Save extra CSV columns into product_custom_fields.
     * Honors the three constructor flags.
     */
    protected function saveCustomFields(array $row, int $productId, int $warehouseId): void
    {
        if (!$this->saveUnknownColumns) {
            return; // disabled by flag
        }

        // Build allow-list if requested
        $allowed = null;
        if ($this->useCustomFieldMaster) {
            $allowed = CustomField::query()
                ->pluck('name')         // CompanyScope already applied
                ->map(fn($n) => strtolower(trim($n)))
                ->toArray();
        }

        foreach ($row as $col => $val) {
            $fieldName = trim((string)$col);
            if ($fieldName === '' || in_array($fieldName, $this->standardColumns, true)) {
                continue; // skip system columns
            }
            if ($val === null) {
                continue; // skip empty values
            }

            // If whitelisting via master is on
            if (is_array($allowed)) {
                $needle = strtolower($fieldName);
                if (!in_array($needle, $allowed, true)) {
                    if ($this->autoCreateCustomFieldNames) {
                        // create it for current company
                        CustomField::firstOrCreate(['name' => $fieldName], [
                            'value'  => null,
                            'type'   => 'text',
                            'active' => 1,
                        ]);
                        // refresh allow-list so subsequent rows pass
                        $allowed[] = $needle;
                    } else {
                        continue; // skip if not allowed and no auto-create
                    }
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