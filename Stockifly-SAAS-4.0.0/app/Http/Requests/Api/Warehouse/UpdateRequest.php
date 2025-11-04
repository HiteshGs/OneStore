<?php

namespace App\Http\Requests\Api\Warehouse;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Warehouse;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Decode the hashed parent_warehouse_id (if present) before rules run.
     */
    protected function prepareForValidation(): void
    {
        $raw = $this->input('parent_warehouse_id');

        if ($raw !== null && $raw !== '') {
            // If the UI sends a hash, decode; if it sends a plain int, keep it.
            $decoded = is_numeric($raw) ? (int) $raw : (Hashids::decode($raw)[0] ?? null);
            $this->merge(['parent_warehouse_id' => $decoded]);
        }
    }

    public function rules()
    {
        $company = company();

        // Current warehouse integer ID (decoded from route xid)
        $decodedFromRoute = Hashids::decode($this->route('warehouse'));
        $id = $decodedFromRoute[0] ?? null;

        return [
            'name'    => 'required',
            'slug'    => [
                'required',
                Rule::unique('warehouses', 'slug')
                    ->where(fn ($q) => $q->where('company_id', $company->id))
                    ->ignore($id), // ignore current row on update
            ],
            'email'                    => 'required|email',
            'phone'                    => 'required|numeric',
            'default_pos_order_status' => 'required',
            'customers_visibility'     => 'required',
            'suppliers_visibility'     => 'required',
            'products_visibility'      => 'required',

            // now validated as a decoded integer id
            'parent_warehouse_id'      => ['nullable','integer','exists:warehouses,id'],
        ];
    }

    /**
     * Extra validation: prevent self-parent and cycles.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $routeDecoded = Hashids::decode($this->route('warehouse'));
            $currentId = $routeDecoded[0] ?? null;

            $parentId = $this->input('parent_warehouse_id'); // already decoded in prepareForValidation
            if (!$parentId) {
                return;
            }

            // self-parent
            if ($currentId && (int)$parentId === (int)$currentId) {
                $v->errors()->add('parent_warehouse_id', 'A warehouse cannot be its own parent.');
                return;
            }

            // circular guard
            if ($currentId) {
                $cursor = Warehouse::with('parent')->find($parentId);
                while ($cursor) {
                    if ((int)$cursor->id === (int)$currentId) {
                        $v->errors()->add('parent_warehouse_id', 'Circular hierarchy is not allowed.');
                        break;
                    }
                    $cursor = $cursor->parent;
                }
            }
        });
    }
}
