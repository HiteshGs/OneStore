<?php

namespace App\Http\Requests\Api\Warehouse;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Vinkla\Hashids\Facades\Hashids;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    // Decode hashed id to numeric before validation
    protected function prepareForValidation(): void
{
    $raw = $this->input('parent_warehouse_id');
    if ($raw !== null && $raw !== '') {
        $decoded = is_numeric($raw)
            ? (int) $raw
            : (\Vinkla\Hashids\Facades\Hashids::decode($raw)[0] ?? null);

        $this->merge(['parent_warehouse_id' => $decoded]);
    }
}

public function rules()
{
    $company = company();

    return [
        'name'    => 'required',
        'slug'    => [
            'required',
            \Illuminate\Validation\Rule::unique('warehouses', 'slug')
                ->where(fn ($q) => $q->where('company_id', $company->id)),
        ],
        'email'                    => 'required|email',
        'phone'                    => 'required|numeric',
        'default_pos_order_status' => 'required',
        'customers_visibility'     => 'required',
        'suppliers_visibility'     => 'required',
        'products_visibility'      => 'required',
        'parent_warehouse_id'      => ['nullable','integer','exists:warehouses,id'],
    ];
}

}
