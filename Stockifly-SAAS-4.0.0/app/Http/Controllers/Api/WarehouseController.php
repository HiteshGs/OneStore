<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Warehouse\StoreRequest;
use App\Http\Requests\Api\Warehouse\UpdateRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;

class WarehouseController extends Controller
{
    /**
     * GET /api/v1/warehouses
     * Supports:
     *  - ?per_page=200
     *  - ?fields=xid,name
     *  - ?order=name
     *  - ?direction=asc|desc   (optional, default desc)
     *  - ?search=...           (optional simple search)
     */
    public function index(Request $request)
    {
        $query = Warehouse::query(); // CompanyScope already applied

        // Optional search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        // Optional select specific fields (xid,name etc.)
        if ($fields = $request->get('fields')) {
            $columns = array_filter(array_map('trim', explode(',', $fields)));

            // always include primary key so Hash cast etc. works
            if (!in_array('id', $columns, true)) {
                $columns[] = 'id';
            }

            $query->select($columns);
        }

        // Ordering
        $order     = $request->get('order', 'id');
        $direction = $request->get('direction', 'desc');

        $query->orderBy($order, $direction);

        $perPage = (int) $request->get('per_page', 20);

        // If per_page = -1, return all (for dropdowns)
        if ($perPage === -1) {
            $items = $query->get();

            // Vue side already handles both {data:[...]} and paginator structure,
            // but returning {data: [...]} is a bit cleaner here.
            return response()->json([
                'data' => $items,
            ]);
        }

        // Standard Laravel paginator: { data: [...], current_page, last_page, ... }
        $paginator = $query->paginate($perPage);

        return response()->json($paginator);
    }

    /**
     * POST /api/v1/warehouses
     * Uses StoreRequest for validation.
     */
    public function store(StoreRequest $request)
    {
        $company = company();

        $data = $request->validated();
        $data['company_id'] = $company->id;

        // Because of casts:
        //  - company_id => Hash cast (hash:decode on set)
        //  - parent_warehouse_id => Hash cast
        // you can pass hashed IDs from frontend and they will be decoded.
        $warehouse = Warehouse::create($data)->fresh();

        // Frontend expects `res.xid`, so return model directly.
        return response()->json($warehouse);
    }

    /**
     * GET /api/v1/warehouses/{warehouse}
     * {warehouse} is hashed (xid)
     */
    public function show($warehouse)
    {
        $idArray = Hashids::decode($warehouse);
        $id      = $idArray[0] ?? null;

        $model = Warehouse::findOrFail($id);

        return response()->json($model);
    }

    /**
     * PUT/PATCH /api/v1/warehouses/{warehouse}
     * Uses UpdateRequest for validation.
     */
    public function update(UpdateRequest $request, $warehouse)
    {
        $idArray = Hashids::decode($warehouse);
        $id      = $idArray[0] ?? null;

        $model = Warehouse::findOrFail($id);

        $data = $request->validated();

        // Again, parent_warehouse_id will be decoded by the cast.
        $model->update($data);

        return response()->json($model->fresh());
    }

    /**
     * DELETE /api/v1/warehouses/{warehouse}
     * If you support delete.
     */
    public function destroy($warehouse)
    {
        $idArray = Hashids::decode($warehouse);
        $id      = $idArray[0] ?? null;

        $model = Warehouse::findOrFail($id);
        $model->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Warehouse deleted successfully',
        ]);
    }
}
