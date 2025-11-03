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

    public function rules()
    {
        $company = company();

        // Current warehouse integer ID (decoded from route xid)
        $convertedId = Hashids::decode($this->route('warehouse'));
        $id = isset($convertedId[0]) ? $convertedId[0] : null;

        return [
            'name'    => 'required',
            'slug'    => [
                'required',
                Rule::unique('warehouses', 'slug')
                    ->where(function ($query) use ($company, $id) {
                        return $query->where('company_id', $company->id)
                                     ->where('id', '!=', $id);
                    }),
            ],
            'email'                     => 'required|email',
            'phone'                     => 'required|numeric',
            'default_pos_order_status'  => 'required',
            'customers_visibility'      => 'required',
            'suppliers_visibility'      => 'required',
            'products_visibility'       => 'required',

            // NEW: hashed xid (nullable)
            'parent_id'                 => ['nullable', 'string'],
        ];
    }

    /**
     * Extra validation: decode parent_id, prevent self-parent and cycles.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $routeDecoded = Hashids::decode($this->route('warehouse'));
            $currentId = isset($routeDecoded[0]) ? $routeDecoded[0] : null;

            $rawParent = $this->input('parent_id');
            if (!$rawParent) {
                return;
            }

            $decoded = Hashids::decode($rawParent);
            $parentId = isset($decoded[0]) ? $decoded[0] : null;

            if (!$parentId) {
                $v->errors()->add('parent_id', 'Invalid parent warehouse.');
                return;
            }

            // Block self-parenting
            if ($currentId && $parentId === $currentId) {
                $v->errors()->add('parent_id', 'A warehouse cannot be its own parent.');
                return;
            }

            // Block circular hierarchy (parent is a descendant of current)
            if ($currentId) {
                $cursor = Warehouse::with('parent')->find($parentId);
                while ($cursor) {
                    if ((int) $cursor->id === (int) $currentId) {
                        $v->errors()->add('parent_id', 'Circular hierarchy is not allowed.');
                        break;
                    }
                    $cursor = $cursor->parent; // uses relation
                }
            }
        });
    }
}
