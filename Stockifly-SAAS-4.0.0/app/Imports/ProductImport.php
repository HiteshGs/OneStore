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
     * will be stored as a custom field (product_custom_fields).
     */
    protected array $standardColumns = [
        // product-level
        'name',
        'description',
        'barcode_symbology',
        'item_code',
        'category',
        'brand',
        'unit',
        'warehouse',

        // details-level
        'tax',
        'mrp',
        'purchase_price',
        'sales_price',
        'purchase_tax_type',
        'sales_tax_type',
        'stock_quantitiy_alert',
        'opening_stock',
        'opening_stock_date',
        'wholesale_price',
        'wholesale_quantity',
    ];

    public function array(array $products)
    {
        DB::transaction(function () use ($products) {
            $user = user();

            foreach ($products as $product) {

                if (
                    !array_key_exists('name', $product) || !array_key_exists('barcode_symbology', $product) || !array_key_exists('item_code', $product) || !array_key_exists('description', $product) ||
                    !array_key_exists('category', $product) || !array_key_exists('brand', $product) || !array_key_exists('unit', $product) || !array_key_exists('tax', $product) ||
                    !array_key_exists('mrp', $product) || !array_key_exists('purchase_price', $product) || !array_key_exists('sales_price', $product) || !array_key_exists('purchase_tax_type', $product) ||
                    !array_key_exists('sales_tax_type', $product) || !array_key_exists('stock_quantitiy_alert', $product) || !array_key_exists('opening_stock', $product) || !array_key_exists('opening_stock_date', $product) ||
                    !array_key_exists('wholesale_price', $product) || !array_key_exists('wholesale_quantity', $product)
                ) {
                    throw new ApiException('Field missing from header.');
                }

                $productName = trim($product['name']);

                if ($productName != '') {
                    $productCount = Product::where('name', $productName)->count();
                    if ($productCount > 0) {
                        throw new ApiException('Product ' . $productName . ' Already Exists');
                    }

                    // Category
                    $categoryName = trim($product['category']);
                    $category = Category::where('name', $categoryName)->first();
                    if (!$category) {
                        throw new ApiException('Category Not Found... ' . $categoryName);
                    }

                    // Brand
                    $brandName = trim($product['brand']);
                    $brand = Brand::where('name', $brandName)->first();
                    if (!$brand) {
                        throw new ApiException('Brand Not Found... ' . $brandName);
                    }

                    // Unit
                    $unitName = trim($product['unit']);
                    $unit = Unit::where('name', $unitName)->first();
                    if (!$unit) {
                        throw new ApiException('Unit Not Found... ' . $unitName);
                    }

                    $barcodeSymbology = trim($product['barcode_symbology']);
                    if ($barcodeSymbology == "" || !in_array($barcodeSymbology, ['CODE128', 'CODE39'])) {
                        throw new ApiException('Barcode symoblogy must be CODE128 or CODE39');
                    }

                    $itemCode = trim($product['item_code']);
                    $isItemCodeAlreadyExists = Product::where('item_code', $itemCode)->count();
                    if ($isItemCodeAlreadyExists > 0) {
                        throw new ApiException('Item Code ' . $itemCode . ' Already Exists');
                    }

                    // Product Details

                    // Tax
                    $taxName = trim($product['tax']);
                    if ($taxName != "") {
                        $tax = Tax::where('name', $taxName)->first();

                        if (!$tax) {
                            throw new ApiException('Tax Not Found');
                        }
                    }

                    $purchaseTaxType = trim($product['purchase_tax_type']);
                    if ($taxName != "" && !in_array($purchaseTaxType, ['exclusive', 'inclusive', 'Inclusive', 'Exclusive'])) {
                        throw new ApiException('Purchase Tax Type must be inclusive or exclusive');
                    }

                    $salesTaxType = trim($product['sales_tax_type']);
                    if ($taxName != "" && !in_array($salesTaxType, ['exclusive', 'inclusive', 'Inclusive', 'Exclusive'])) {
                        throw new ApiException('Sales Tax Type must be inclusive or exclusive');
                    }

                    $openingStockDate = trim($product['opening_stock_date']);
                    $stockQuantityAlert = trim($product['stock_quantitiy_alert']);
                    $openingStock = trim($product['opening_stock']);
                    $wholesaleQuantity = trim($product['wholesale_quantity']);
                    $wholesalePrice = trim($product['wholesale_price']);
                    $allWarehouses = Warehouse::select('id')->get();

                    // Resolve the "target" warehouse to attach custom fields to
                    if (array_key_exists('warehouse', $product)) {
                        $namedWarehouse = Warehouse::where('name', $product['warehouse'])->first();
                        $currentWarehouse = warehouse();
                        $createdWarehouseId = $namedWarehouse && $namedWarehouse->id ? $namedWarehouse->id : $currentWarehouse->id;
                    } else {
                        $currentWarehouse = warehouse();
                        $createdWarehouseId = $currentWarehouse->id;
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

                    // MRP
                    $mrp = $product['mrp'] && $product['mrp'] != '' ? trim($product['mrp']) : null;
                    if ($mrp != null) {
                        $mrp = str_replace(',', '', $mrp);
                        $mrp = str_replace('-', '', $mrp);
                        $mrp = is_numeric($mrp) ? $mrp : null;
                    }

                    // Purchase Price
                    $purchasePrice = $product['purchase_price'] && $product['purchase_price'] != '' ?  trim($product['purchase_price']) : 0;
                    $purchasePrice = str_replace(',', '', $purchasePrice);
                    $purchasePrice = str_replace('-', '', $purchasePrice);
                    $purchasePrice = is_numeric($purchasePrice) ? $purchasePrice : 0;

                    // Sales Price
                    $salesPrice = $product['sales_price'] && $product['sales_price'] != '' ?  trim($product['sales_price']) : 0;
                    $salesPrice = str_replace(',', '', $salesPrice);
                    $salesPrice = str_replace('-', '', $salesPrice);
                    $salesPrice = is_numeric($salesPrice) ? $salesPrice : 0;

                    // Wholesale Price
                    $wholesalePrice = $product['sales_price'] && $product['sales_price'] != '' ?  trim($product['sales_price']) : null;
                    if ($wholesalePrice != '') {
                        $wholesalePrice = str_replace(',', '', $wholesalePrice);
                        $wholesalePrice = str_replace('-', '', $wholesalePrice);
                        $wholesalePrice = is_numeric($wholesalePrice) ? $wholesalePrice : null;
                    }

                    // Create ProductDetails for all warehouses
                    foreach ($allWarehouses as $allWarehouse) {
                        $newProductDetails = new ProductDetails();
                        $newProductDetails->warehouse_id = $allWarehouse->id;
                        $newProductDetails->product_id = $newProduct->id;
                        $newProductDetails->tax_id = $taxName == "" ? null : $tax->id;
                        $newProductDetails->purchase_tax_type = $purchaseTaxType != '' ? lcfirst($purchaseTaxType) : 'exclusive';
                        $newProductDetails->sales_tax_type = $salesTaxType != '' ? lcfirst($salesTaxType) : 'exclusive';
                        $newProductDetails->mrp = $mrp;
                        $newProductDetails->purchase_price = $purchasePrice;
                        $newProductDetails->sales_price = $salesPrice;
                        $newProductDetails->stock_quantitiy_alert = $stockQuantityAlert != "" ? (int) $stockQuantityAlert : null;
                        $newProductDetails->opening_stock = $openingStock != "" ? (int) $openingStock : null;
                        $newProductDetails->opening_stock_date = $openingStockDate != "" ? $openingStockDate : null;
                        $newProductDetails->wholesale_price = $wholesalePrice;
                        $newProductDetails->wholesale_quantity = $wholesaleQuantity == "" ? null : $wholesaleQuantity;
                        $newProductDetails->save();

                        Common::recalculateOrderStock($newProductDetails->warehouse_id, $newProduct->id);
                    }

                    /**
                     * NEW: Save extra CSV columns as product_custom_fields
                     * - Use the chosen/created warehouse ($createdWarehouseId)
                     * - Upsert on (product_id, warehouse_id, field_name)
                     */
                    $this->saveCustomFields($product, $newProduct->id, (int)$createdWarehouseId);
                }
            }
        });
    }

    /**
     * Persist unknown CSV columns into product_custom_fields (UPSERT).
     * Uses withoutGlobalScopes() because ProductCustomField has CompanyScope
     * and a current_warehouse global scope which would otherwise filter out rows.
     */
    protected function saveCustomFields(array $row, int $productId, int $warehouseId): void
    {
        foreach ($row as $col => $val) {
            $fieldName = trim((string)$col);

            // skip empty header keys and standard/known columns
            if ($fieldName === '' || in_array($fieldName, $this->standardColumns, true)) {
                continue;
            }

            if ($val === null) {
                continue;
            }

            $fieldValue = is_scalar($val) ? (string)$val : json_encode($val);
            $fieldValue = trim($fieldValue);
            if ($fieldValue === '') {
                continue;
            }

            // UPSERT by composite key
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
