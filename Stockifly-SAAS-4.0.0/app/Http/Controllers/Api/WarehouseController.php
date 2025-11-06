<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Warehouse\IndexRequest;
use App\Http\Requests\Api\Warehouse\StoreRequest;
use App\Http\Requests\Api\Warehouse\UpdateRequest;
use App\Http\Requests\Api\Warehouse\DeleteRequest;
use App\Http\Requests\Api\Warehouse\UpdateOnlineStoreStatusRequest;
use App\Models\Customer;
use App\Models\FrontWebsiteSettings;
use App\Models\Product;
use App\Models\ProductDetails;
use App\Models\User;
use App\Models\UserDetails;
use App\Models\Warehouse;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;

class WarehouseController extends ApiBaseController
{
    protected $model = Warehouse::class;

    protected $indexRequest  = IndexRequest::class;
    protected $storeRequest  = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    /**
     * Limit list by user + always include parent for list/show.
     */
    public function modifyIndex($query)
    {
        $loggedUser = user();

        if ($loggedUser && !$loggedUser->hasRole('admin')) {
            if ($loggedUser->user_type === 'staff_members') {
                $query = $query->where(function ($q) use ($loggedUser) {
                    foreach ($loggedUser->userWarehouses as $i => $uw) {
                        $i === 0
                            ? $q->where('warehouses.id', $uw->warehouse_id)
                            : $q->orWhere('warehouses.id', $uw->warehouse_id);
                    }
                });
            } else {
                $query = $query->where('warehouses.id', $loggedUser->warehouse_id);
            }
        }

        return $query->with(['parent:id,name']);
    }

    /**
     * BEFORE create: resolve parent_warehouse_id (hashed) and set it on the model.
     */
    public function storing(Warehouse $warehouse)
    {
        // The UI sends a HASH in parent_warehouse_id; decode it to int.
        $rawParent = request('parent_warehouse_id');
        $decoded   = $rawParent ? $this->getIdFromHash($rawParent) : null;

        if ($decoded && !Warehouse::whereKey($decoded)->exists()) {
            throw new ApiException('Invalid parent warehouse.');
        }

        // IMPORTANT: write to the real column
        $warehouse->parent_warehouse_id = $decoded;

        return $warehouse;
    }

    /**
     * AFTER create: initialize related records.
     */
    public function stored(Warehouse $warehouse)
    {
        $company          = company();
        $companyWarehouse = $company->warehouse;

        // Front website settings skeleton for this warehouse
        $frontSetting = new FrontWebsiteSettings();
        $frontSetting->warehouse_id      = $warehouse->id;
        $frontSetting->featured_categories = [];
        $frontSetting->featured_products   = [];
        $frontSetting->features_lists      = [];
        $frontSetting->pages_widget        = [];
        $frontSetting->contact_info_widget = [];
        $frontSetting->links_widget        = [];
        $frontSetting->top_banners         = [];
        $frontSetting->bottom_banners_1    = [];
        $frontSetting->bottom_banners_2    = [];
        $frontSetting->bottom_banners_3    = [];
        $frontSetting->save();

        // Copy ProductDetails defaults from the company default warehouse
        $allProducts = Product::select('id')
            ->where('product_type', 'single')
            ->whereNotNull('parent_id')
            ->get();

        foreach ($allProducts as $p) {
            $default = ProductDetails::withoutGlobalScope('current_warehouse')
                ->where('warehouse_id', $companyWarehouse->id)
                ->where('product_id',  $p->id)
                ->first();

            // Guard against null; avoid an exception that would look like "unknown error"
            if (!$default) {
                continue;
            }

            $pd = new ProductDetails();
            $pd->warehouse_id        = $warehouse->id;
            $pd->product_id          = $p->id;
            $pd->tax_id              = $default->tax_id;
            $pd->mrp                 = $default->mrp;
            $pd->purchase_price      = $default->purchase_price;
            $pd->sales_price         = $default->sales_price;
            $pd->purchase_tax_type   = $default->purchase_tax_type;
            $pd->sales_tax_type      = $default->sales_tax_type;
            $pd->stock_quantitiy_alert = $default->stock_quantitiy_alert;
            $pd->wholesale_price     = $default->wholesale_price;
            $pd->wholesale_quantity  = $default->wholesale_quantity;
            $pd->save();

            Common::recalculateOrderStock($pd->warehouse_id, $p->id);
        }

        // Bootstrap UserDetails for customers/suppliers for this warehouse
        $allCustomerSuppliers = Customer::withoutGlobalScope('type')
            ->where('user_type', 'suppliers')
            ->orWhere('user_type', 'customers')
            ->get();

        foreach ($allCustomerSuppliers as $u) {
            $ud = new UserDetails();
            // Use integer PK; do NOT store hash here unless your schema expects it
            $ud->warehouse_id  = $warehouse->id;
            $ud->user_id       = $u->id;
            $ud->credit_period = 30;
            $ud->save();
        }
    }

    /**
     * BEFORE update: validate and set parent_warehouse_id; prevent self/loops.
     */
    public function updating(Warehouse $warehouse)
    {
        $rawParent = request('parent_warehouse_id');                 // hash or null
        $decoded   = $rawParent ? $this->getIdFromHash($rawParent) : null;

        // self-parent
        if ($decoded && (int) $decoded === (int) $warehouse->id) {
            throw new ApiException('A warehouse cannot be its own parent.');
        }

        // circular guard
        if ($decoded) {
            $cursor = Warehouse::with('parent')->find($decoded);
            while ($cursor) {
                if ((int) $cursor->id === (int) $warehouse->id) {
                    throw new ApiException('Circular hierarchy is not allowed.');
                }
                $cursor = $cursor->parent;
            }
        }

        $warehouse->parent_warehouse_id = $decoded ?: null;

        return $warehouse;
    }

    /**
     * AFTER update: refresh session warehouse if you edited the active one.
     */
    public function updated(Warehouse $warehouse)
    {
        $sessionWarehouse = warehouse();

        if ($sessionWarehouse && $sessionWarehouse->id == $warehouse->id) {
            session(['warehouse' => $warehouse]);
        }

        return $warehouse;
    }

    /**
     * BEFORE delete: safety checks.
     */
    public function destroying(Warehouse $warehouse)
    {
        $company = company();

        if ($warehouse->id == $company->warehouse_id) {
            throw new ApiException('Default warehouse cannot be deleted');
        }

        $warehouseStaffMemberCount = User::where('warehouse_id', $warehouse->id)->count();
        if ($warehouseStaffMemberCount > 0) {
            throw new ApiException(
                'This warehouse has active staff member(s). ' .
                'Either delete or change their warehouse before deleting this'
            );
        }

        return $warehouse;
    }

    /**
     * Small helper endpoint for async selects in the UI.
     */
    public function options(\Illuminate\Http\Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $query  = Warehouse::select('id', 'name')->orderBy('name');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->get()->map(fn ($w) => [
            'value' => $w->xid,   // hashed id for the UI
            'label' => $w->name,
        ])->values();

        return ApiResponse::make('Success', $items);
    }

    /**
     * Toggle online store status.
     */
    public function updateOnlineStoreStatus(UpdateOnlineStoreStatusRequest $request)
    {
        $id = $this->getIdFromHash($request->warehouse_id);

        $warehouse = Warehouse::findOrFail($id);
        $warehouse->online_store_enabled = (int) $request->status;
        $warehouse->save();

        return ApiResponse::make('Success', []);
    }
}
