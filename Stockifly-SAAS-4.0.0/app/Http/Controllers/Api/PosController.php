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
use Carbon\Carbon;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;

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
            'products.image',
            'products.product_type',
                'products.hsn_code', // ✅ ADD THIS

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


        $products =    $products->get();

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
        $order->staff_user_id = $loggedInUser->id;
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
            ->with(['user:id,name,email', 'items:id,order_id,product_id,unit_id,unit_price,subtotal,quantity,mrp,total_tax,single_unit_price,tax_rate,tax_type,product_name,product_image,product_hsn_code', 'items.product:id,name,image,hsn_code,product_type', 'items.unit:id,name,short_name', 'items.product.details:id,product_id,warehouse_id,current_stock', 'orderPayments:id,order_id,payment_id,amount', 'orderPayments.payment:id,payment_mode_id', 'orderPayments.payment.paymentMode:id,name', 'staffMember:id,name'])
            ->find($order->id);

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

        return ApiResponse::make('POS Data Saved', [
            'order' => $savedOrder,
        ]);
    }

    public function getInvoiceData($invoiceNumber)
    {
        try {
            // Get order with complete product details using the SQL query
            $orderData = \DB::table('orders as o')
                ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
                ->leftJoin('products as p', 'oi.product_id', '=', 'p.id')
                ->leftJoin('product_details as pd', function($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                         ->on('pd.warehouse_id', '=', 'o.warehouse_id');
                })
                ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
                ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
                ->leftJoin('units as u', 'oi.unit_id', '=', 'u.id')
                ->leftJoin('taxes as t', 'oi.tax_id', '=', 't.id')
                ->where('o.invoice_number', $invoiceNumber)
                ->select(
                    'o.id as order_id',
                    'o.invoice_number',
                    'o.order_date',
                    'o.total',
                    'o.subtotal',
                    'o.tax_amount',
                    'o.discount',
                    'o.shipping',
                    'o.paid_amount',
                    'o.due_amount',
                    'oi.id as order_item_id',
                    'oi.quantity',
                    'oi.unit_price',
                    'oi.single_unit_price',
                    'oi.tax_rate',
                    'oi.tax_type',
                    'oi.total_tax',
                    'oi.subtotal as item_subtotal',
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
                    'u.name as unit_name',
                    'u.short_name as unit_short_name',
                    't.rate as tax_percentage'
                )
                ->get();

            \Log::info("📊 getInvoiceData - Invoice: {$invoiceNumber}, Total rows from DB: " . $orderData->count());

            if ($orderData->isEmpty()) {
                throw new ApiException('Order not found');
            }

            // Get order basic info
            $firstRow = $orderData->first();
            
            // Get additional order details
            $order = Order::where('invoice_number', $invoiceNumber)->first();
            
            if (!$order) {
                throw new ApiException('Order not found');
            }

            // Format the response
            $response = [
                'order' => [
                    'id' => $order->id,
                    'xid' => $order->xid,
                    'invoice_number' => $firstRow->invoice_number,
                    'order_date' => $firstRow->order_date,
                    'total' => $firstRow->total,
                    'subtotal' => $firstRow->subtotal,
                    'tax_amount' => $firstRow->tax_amount,
                    'discount' => $firstRow->discount,
                    'shipping' => $firstRow->shipping,
                    'paid_amount' => $firstRow->paid_amount,
                    'due_amount' => $firstRow->due_amount,
                    'items' => []
                ],
                'warehouse' => $order->warehouse,
                'user' => $order->user,
                'staff_member' => $order->staffMember,
                'order_payments' => $order->orderPayments()->with(['payment', 'payment.paymentMode'])->get()
            ];

            // Group items
            foreach ($orderData as $row) {
                $response['order']['items'][] = [
                    'id' => $row->order_item_id,
                    'quantity' => $row->quantity,
                    'unit_price' => $row->unit_price,
                    'single_unit_price' => $row->single_unit_price,
                    'tax_rate' => $row->tax_rate,
                    'tax_type' => $row->tax_type,
                    'total_tax' => $row->total_tax,
                    'subtotal' => $row->item_subtotal,
                    'mrp' => $row->mrp,
                    'product' => [
                        'id' => $row->product_id,
                        'name' => $row->product_name,
                        'item_code' => $row->item_code,
                        'slug' => $row->slug,
                        'hsn_code' => $row->hsn_code,
                        'description' => $row->description,
                        'category_name' => $row->category_name,
                        'brand_name' => $row->brand_name,
                    ],
                    'unit' => [
                        'name' => $row->unit_name,
                        'short_name' => $row->unit_short_name,
                    ],
                    'product_details' => [
                        'current_stock' => $row->current_stock,
                        'sales_price' => $row->sales_price,
                        'purchase_price' => $row->purchase_price,
                    ],
                    'tax_percentage' => $row->tax_percentage
                ];
            }

            \Log::info("✅ getInvoiceData - Response items count: " . count($response['order']['items']));

            return ApiResponse::make('Invoice data fetched successfully', $response);

        } catch (\Exception $e) {
            \Log::error('Error in getInvoiceData: ' . $e->getMessage());
            throw new ApiException('Failed to fetch invoice data: ' . $e->getMessage());
        }
    }
}
