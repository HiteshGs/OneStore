<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Order\PosRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Settings;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class PosController extends ApiBaseController
{
    public function posProducts()
    {
        $request = request();
        $allProducs = [];
        $warehouse = warehouse();
        $warehouseId = $warehouse->id;

        $products = Product::select(
            'products.id',
            'products.name',
            'products.item_code',
            'products.image',
            'products.product_type',
            'products.hsn_code',

            'product_details.sales_price',
            'products.unit_id',
            'product_details.sales_tax_type',
            'product_details.tax_id',
            'product_details.current_stock',
            'taxes.rate'
        )
            ->join('product_details', 'product_details.product_id', '=', 'products.id')
            ->leftJoin('taxes', 'taxes.id', '=', 'product_details.tax_id')
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->where('product_details.warehouse_id', '=', $warehouseId);

        $products = $products->where(function ($query) {
            $query->where(function ($qry) {
                $qry->where('products.product_type', '!=', 'service')
                    ->where('product_details.current_stock', '>', 0);
            })->orWhere('products.product_type', '=', 'service');
        });

        if ($warehouse->products_visibility == 'warehouse') {
            $products->where('products.warehouse_id', '=', $warehouse->id);
        }

        // Category Filters
        if ($request->has('category_id') && $request->category_id != "") {
            $categoryId = $this->getIdFromHash($request->category_id);
            $products = $products->where('category_id', '=', $categoryId);
        }

        // Brand Filters
        if ($request->has('brand_id') && $request->brand_id != "") {
            $brandId = $this->getIdFromHash($request->brand_id);
            $products = $products->where('brand_id', '=', $brandId);
        }

        if ($request->has('search_term') && trim($request->search_term) != "") {
            $searchTerm = trim(strtolower($request->search_term));
            $products = $products->where(function ($query) use ($searchTerm) {
                $query->where(DB::raw('LOWER(products.name)'), 'LIKE', "%$searchTerm%")
                    ->orWhere(DB::raw('LOWER(products.item_code)'), 'LIKE', "$searchTerm%")
                    ->orWhere(DB::raw('LOWER(products.parent_item_code)'), 'LIKE', "$searchTerm%");
            });
        }

        if ($request->has('products') && is_array($request->products)) {
            $selectedProducts = [];
            foreach ($request->products as $selectedProduct) {
                $selectedProducts[] = $this->getIdFromHash($selectedProduct);
            }

            if (count($selectedProducts) > 0) {
                $products = $products->whereNotIn('products.id', $selectedProducts);
            }
        }

        $limit = (int) $request->input('limit', 25);
        $limit = $limit > 0 ? min($limit, 100) : 25;
        $offset = max((int) $request->input('offset', 0), 0);
        $total = (clone $products)->count();

        $products = $products
            ->orderBy('products.name')
            ->skip($offset)
            ->take($limit)
            ->get();

        foreach ($products as $product) {
            $stockQuantity = $product->current_stock;
            $unit = $product->unit_id != null ? Unit::find($product->unit_id) : null;
            $tax = $product->tax_id != null ? Tax::find($product->tax_id) : null;
            $taxType = $product->sales_tax_type;

            $unitPrice = $product->sales_price;
            $singleUnitPrice = $unitPrice;

            if ($product->rate != '') {
                $taxRate = $product->rate;

                if ($product->sales_tax_type == 'inclusive') {
                    $subTotal = $singleUnitPrice;
                    $singleUnitPrice =  ($singleUnitPrice * 100) / (100 + $taxRate);
                    $taxAmount = ($singleUnitPrice) * ($taxRate / 100);
                } else {
                    $taxAmount =  ($singleUnitPrice * ($taxRate / 100));
                    $subTotal = $singleUnitPrice + $taxAmount;
                }
            } else {
                $taxAmount = 0;
                $taxRate = 0;
                $subTotal = $singleUnitPrice;
            }

            $allProducs[] = [
                'item_id'    =>  '',
                'xid'    =>  $product->xid,
                'name'    =>  $product->name,
                'item_code'    =>  $product->item_code,
                'image'    =>  $product->image,
                'image_url'    =>  $product->image_url,
                'hsn_code' => $product->hsn_code,

                'discount_rate'    =>  0,
                'total_discount'    =>  0,
                'x_tax_id'    => $tax ? $tax->xid : null,
                'tax_type'    =>  $taxType,
                'tax_rate'    =>  $taxRate,
                'total_tax'    =>  $taxAmount,
                'x_unit_id'    =>  $unit ? $unit->xid : null,
                'unit'    =>  $unit,
                'unit_price'    =>  $unitPrice,
                'single_unit_price'    =>  $singleUnitPrice,
                'subtotal'    =>  $subTotal,
                'quantity'    =>  1,
                'stock_quantity'    =>  $stockQuantity,
                'unit_short_name'    =>  $unit ? $unit->short_name : '',
                'product_type'      => $product->product_type
            ];
        }

        $data = [
            'products' => $allProducs,
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total,
            'has_more' => count($allProducs) == $limit,
        ];

        return ApiResponse::make('Data fetched', $data);
    }

    public function addPosPayment(PosRequest $request)
    {
        return ApiResponse::make('Success');
    }

    public function savePosPayments()
    {

        $request = request();
        $loggedInUser = user();
        $warehouse = warehouse();
        $orderDetails = $request->details;
        $oldOrderId = "";
        $posDefaultStatus = $warehouse->default_pos_order_status;
        $selectedStaffUserId = $request->input('staff_user_id');
        $staffUserId = $loggedInUser->id;

        if ($selectedStaffUserId) {
            $selectedStaffUser = User::where('id', $selectedStaffUserId)
                ->where('user_type', 'staff_members')
                ->first();

            if ($selectedStaffUser) {
                $staffUserId = $selectedStaffUser->id;
            }
        }

        $allPayments = $request->input('all_payments', []);
        if (!is_array($allPayments)) {
            $allPayments = [];
        }

        if ($request->has('all_payments') && count($request->all_payments) > 0) {
            $allPayments = collect($request->all_payments);

            $total = $allPayments->sum(function ($item) {
                return $item['amount'];
            });

            if ($total > $orderDetails['subtotal']) {
                throw new ApiException('Paid amount should be less than or equal to Grand Total');
            }
        }

        $order = new Order();
        $order->order_type = "sales";
        $order->invoice_type = "pos";
        $order->unique_id = Common::generateOrderUniqueId();
        $order->invoice_number = "";
        $order->order_date = Carbon::now();
        $order->warehouse_id = $warehouse->id;
        $order->user_id = isset($orderDetails['user_id']) ? $orderDetails['user_id'] : null;
        $order->tax_id = isset($orderDetails['tax_id']) ? $orderDetails['tax_id'] : null;
        $order->tax_rate = $orderDetails['tax_rate'];
        $order->tax_amount = $orderDetails['tax_amount'];
        $order->discount = $orderDetails['discount'];
        $order->shipping = $orderDetails['shipping'];
        $order->subtotal = 0;
        $order->total = $orderDetails['subtotal'];
        $order->paid_amount = 0;
        $order->due_amount = $order->total;
        $order->order_status = $posDefaultStatus;
        $order->staff_user_id = $staffUserId;
        $order->save();

        $order->invoice_number = Common::getTransactionNumber($order->order_type, $order->id);
        $order->save();

        Common::storeAndUpdateOrder($order, $oldOrderId);

        // Updating Warehouse History
        Common::updateWarehouseHistory('order', $order, "add_edit");

        $allPayments = $request->input('all_payments', []);
        if (!is_array($allPayments)) {
            $allPayments = [];
        }

        foreach ($allPayments as $allPayment) {
            // Save Order Payment
            if ($allPayment['amount'] > 0 && $allPayment['payment_mode_id'] != '') {
                $payment = new Payment();
                $payment->warehouse_id = $warehouse->id;
                $payment->payment_type = "in";
                $payment->date = Carbon::now();
                $payment->amount = $allPayment['amount'];
                $payment->paid_amount = $allPayment['amount'];
                $payment->payment_mode_id = $allPayment['payment_mode_id'];
                $payment->notes = $allPayment['notes'];
                $payment->user_id = $order->user_id;
                $payment->save();

                // Generate and save payment number
                $paymentType = 'payment-' . $payment->payment_type;
                $payment->payment_number = Common::getTransactionNumber($paymentType, $payment->id);
                $payment->save();

                $orderPayment = new OrderPayment();
                $orderPayment->order_id = $order->id;
                $orderPayment->payment_id = $payment->id;
                $orderPayment->amount = $allPayment['amount'];
                $orderPayment->save();
            }
        }

        Common::updateOrderAmount($order->id);

        $savedOrder = Order::select('id', 'unique_id', 'invoice_number', 'user_id', 'staff_user_id', 'order_date', 'discount', 'shipping', 'tax_amount', 'subtotal', 'total', 'paid_amount', 'due_amount', 'total_items', 'total_quantity')
            ->with(['user:id,name,email', 'items:id,order_id,product_id,unit_id,unit_price,subtotal,quantity,mrp,total_tax', 'items.product:id,name,item_code,hsn_code', 'items.unit:id,name,short_name', 'orderPayments:id,order_id,payment_id,amount', 'orderPayments.payment:id,payment_mode_id', 'orderPayments.payment.paymentMode:id,name', 'staffMember:id,name'])
            ->find($order->id);

        if ($savedOrder && $savedOrder->staffMember) {
            $savedOrder->setAttribute('generated_by', [
                'xid' => $savedOrder->staffMember->xid,
                'name' => $savedOrder->staffMember->name,
            ]);
        }

        $totalMrp = 0;
        $totalTax = 0;
        foreach ($savedOrder->items as $orderItem) {
            $totalMrp += ($orderItem->quantity * $orderItem->mrp);
            $totalTax += $orderItem->total_tax;
        }

        $savingOnMrp = $totalMrp - $savedOrder->total;
        $saving_percentage = $totalMrp > 0 ? number_format((float)($savingOnMrp / $totalMrp * 100), 2, '.', '') : 0;

        $savedOrder->saving_on_mrp = $savingOnMrp;
        $savedOrder->saving_percentage = $saving_percentage;
        $savedOrder->total_tax_on_items = $totalTax + $savedOrder->tax_amount;
        $savedOrder->setRelation('items', $this->getInvoiceItemsByInvoiceNumber($savedOrder->invoice_number));

        return ApiResponse::make('POS Data Saved', [
            'order' => $savedOrder,
        ]);
    }

    private function getInvoiceItemsByInvoiceNumber($invoiceNumber)
    {
        return DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->leftJoin('products as p', 'oi.product_id', '=', 'p.id')
            ->leftJoin('product_details as pd', function ($join) {
                $join->on('p.id', '=', 'pd.product_id')
                    ->on('pd.warehouse_id', '=', 'o.warehouse_id');
            })
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin('units as u', 'oi.unit_id', '=', 'u.id')
            ->leftJoin('taxes as t', 'oi.tax_id', '=', 't.id')
            ->where('o.invoice_number', $invoiceNumber)
            ->orderBy('oi.id')
            ->select([
                'oi.id as order_item_id',
                'oi.quantity',
                'oi.unit_price',
                'oi.single_unit_price',
                'oi.tax_rate',
                'oi.tax_type',
                'oi.total_tax',
                'oi.subtotal',
                'oi.mrp',
                'p.id as product_id',
                'p.name as product_name',
                'p.item_code',
                'p.slug',
                'p.hsn_code',
                'p.description',
                'pd.current_stock',
                'pd.sales_price',
                'pd.purchase_price',
                'c.name as category_name',
                'b.name as brand_name',
                'u.id as unit_id',
                'u.name as unit_name',
                'u.short_name as unit_short_name',
                't.id as tax_id',
                't.rate as tax_percentage',
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'xid' => Common::getHashFromId($item->order_item_id),
                    'item_id' => Common::getHashFromId($item->order_item_id),
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'single_unit_price' => (float) $item->single_unit_price,
                    'tax_rate' => $item->tax_rate !== null ? (float) $item->tax_rate : (float) ($item->tax_percentage ?? 0),
                    'tax_type' => $item->tax_type,
                    'total_tax' => (float) $item->total_tax,
                    'subtotal' => (float) $item->subtotal,
                    'mrp' => (float) $item->mrp,
                    'product_name' => $item->product_name,
                    'name' => $item->product_name,
                    'item_code' => $item->item_code,
                    'hsn_code' => $item->hsn_code,
                    'x_product_id' => $item->product_id ? Common::getHashFromId($item->product_id) : null,
                    'x_unit_id' => $item->unit_id ? Common::getHashFromId($item->unit_id) : null,
                    'x_tax_id' => $item->tax_id ? Common::getHashFromId($item->tax_id) : null,
                    'unit' => [
                        'name' => $item->unit_name,
                        'short_name' => $item->unit_short_name,
                    ],
                    'product' => [
                        'xid' => $item->product_id ? Common::getHashFromId($item->product_id) : null,
                        'name' => $item->product_name,
                        'item_code' => $item->item_code,
                        'slug' => $item->slug,
                        'hsn_code' => $item->hsn_code,
                        'description' => $item->description,
                        'current_stock' => $item->current_stock,
                        'sales_price' => $item->sales_price,
                        'purchase_price' => $item->purchase_price,
                        'category_name' => $item->category_name,
                        'brand_name' => $item->brand_name,
                    ],
                ];
            });
    }

    public function invoiceItems()
    {
        $invoiceNumber = request()->input('invoice_number');

        if (!$invoiceNumber) {
            throw new ApiException('Invoice number is required');
        }

        return ApiResponse::make('POS invoice items fetched', [
            'items' => $this->getInvoiceItemsByInvoiceNumber($invoiceNumber),
        ]);
    }

    public function getStaffMembers()
    {
        $request = request();
        $warehouseSlug = $request->input('warehouse_slug');

        if (!$warehouseSlug) {
            throw new ApiException('Warehouse slug is required');
        }

        try {
            $staffMembers = User::select(
    'users.id as user_id',
    'users.name as user_name',
    'users.email as user_email',
    'users.user_type as user_type'
)
                ->join('user_warehouse', 'users.id', '=', 'user_warehouse.user_id')
                ->join('warehouses', 'user_warehouse.warehouse_id', '=', 'warehouses.id')
                ->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
                ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
                ->where('warehouses.slug', '=', $warehouseSlug)
                ->where(function ($query) {
                    $query->where('roles.name', 'like', '%sales-person%')
                        ->orWhereNull('roles.id');
                })
                ->orderBy('users.name')
                ->get();

            return ApiResponse::make('Staff members fetched', [
                'staff_members' => $staffMembers,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::make('Error fetching staff members: ' . $e->getMessage(), [], 500);
        }
    }
}
