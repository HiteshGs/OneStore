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
use Illuminate\Support\Facades\Log;
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
            ->where('product_details.warehouse_id', '=', $warehouseId)
            ->with('customFields') // Eager-load custom fields
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

            // Add custom fields
            $customFields = [];
            if ($product->customFields) {
                foreach ($product->customFields as $cf) {
                    $customFields[] = [
                        'id'    => $cf->xid,
                        'name'  => $cf->field_name,
                        'label' => $cf->field_name,
                        'value' => $cf->field_value,
                    ];

                    // If item has no hsn_code, fill from "HSN Code"
                    if (
                        (empty($product->hsn_code) || $product->hsn_code === null) &&
                        strcasecmp($cf->field_name, 'HSN Code') === 0
                    ) {
                        $product->hsn_code = $cf->field_value;
                    }
                }
            }

            $allProducs[] = [
                'item_id'    =>  '',
                'xid'    =>  $product->xid,
                'name'    =>  $product->name,
                'image'    =>  $product->image,
                'image_url'    =>  $product->image_url,
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
                'product_type'      => $product->product_type,
                'custom_fields' => $customFields, // Attach custom fields here
                'hsn_code' => $product->hsn_code, // Ensure HSN code is populated
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
    // Request data
    $request = request();
    
    // Log the incoming request data
    Log::debug('Request Payload:', $request->all());
    
    // Log specific data like all_payments and product_items to track their values
    Log::debug('All Payments:', $request->input('all_payments'));
    Log::debug('Product Items:', $request->input('product_items'));

    $loggedInUser = user();
    $warehouse = warehouse();
    $orderDetails = $request->details;
    $oldOrderId = "";
    $posDefaultStatus = $warehouse->default_pos_order_status;

    // Check and log the payments details
    $allPayments = $request->input('all_payments', []);
    if (!is_array($allPayments)) {
        $allPayments = [];
    }

    // If payments exist, log the sum and check for overpayment
    if (count($allPayments) > 0) {
        $total = collect($allPayments)->sum(function ($item) {
            return $item['amount'];
        });

        Log::debug('Total Payment Sum:', $total);

        if ($total > $orderDetails['subtotal']) {
            throw new ApiException('Paid amount should be less than or equal to Grand Total');
        }
    }

    // Create a new Order object and save the order
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

    Log::debug('Order Saved:', $order);

    $order->invoice_number = Common::getTransactionNumber($order->order_type, $order->id);
    $order->save();

    Common::storeAndUpdateOrder($order, $oldOrderId);

    // Update Warehouse History (log the history update)
    Common::updateWarehouseHistory('order', $order, "add_edit");
    Log::debug('Warehouse History Updated');

    // Iterate over the payments and save each payment
    foreach ($allPayments as $allPayment) {
        // Log each payment's details
        Log::debug('Processing Payment:', $allPayment);

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

            Log::debug('Payment Saved:', $payment);
        }
    }

    // Update Order Amount
    Common::updateOrderAmount($order->id);
    Log::debug('Order Amount Updated');

    // Fetch the saved order and related details
    $savedOrder = Order::select('id', 'unique_id', 'invoice_number', 'user_id', 'staff_user_id', 'order_date', 'discount', 'shipping', 'tax_amount', 'subtotal', 'total', 'paid_amount', 'due_amount', 'total_items', 'total_quantity')
        ->with(['user:id,name,email', 'items:id,order_id,product_id,unit_id,unit_price,subtotal,quantity,mrp,total_tax', 'items.product:id,name', 'items.unit:id,name,short_name', 'orderPayments:id,order_id,payment_id,amount', 'orderPayments.payment:id,payment_mode_id', 'orderPayments.payment.paymentMode:id,name', 'staffMember:id,name'])
        ->find($order->id);

    // Log the saved order
    Log::debug('Saved Order:', $savedOrder);

    // Calculate and log MRP and tax totals
    $totalMrp = 0;
    $totalTax = 0;
    foreach ($savedOrder->items as $orderItem) {
        $totalMrp += ($orderItem->quantity * $orderItem->mrp);
        $totalTax += $orderItem->total_tax;
    }

    Log::debug('Total MRP:', $totalMrp);
    Log::debug('Total Tax:', $totalTax);

    $savingOnMrp = $totalMrp - $savedOrder->total;
    $saving_percentage = $totalMrp > 0 ? number_format((float)($savingOnMrp / $totalMrp * 100), 2, '.', '') : 0;

    $savedOrder->saving_on_mrp = $savingOnMrp;
    $savedOrder->saving_percentage = $saving_percentage;
    $savedOrder->total_tax_on_items = $totalTax + $savedOrder->tax_amount;

    // Return the response with saved order data
    return ApiResponse::make('POS Data Saved', [
        'order' => $savedOrder,
    ]);
}

}
