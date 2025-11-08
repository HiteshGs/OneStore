<?php

namespace App\Http\Requests\Api\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Vinkla\Hashids\Facades\Hashids;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    // 🔹 decode hashids before validation
    protected function prepareForValidation(): void
    {
        if ($this->has('payment_mode_id') && $this->input('payment_mode_id') !== null) {
            $raw = $this->input('payment_mode_id');
            $decoded = is_numeric($raw) ? (int) $raw : (Hashids::decode($raw)[0] ?? null);
            $this->merge(['payment_mode_id' => $decoded]);
        }
    }

    public function rules()
{
    $company = company();
    $convertedId = Hashids::decode($this->route('warehouse'));
    $id = $convertedId[0];

    $rules = [
        'name'    => 'required',
        'slug'    => [
            'required',
            Rule::unique('warehouses', 'slug')->where(function ($query) use ($company, $id) {
                return $query->where('company_id', $company->id)
                    ->where('id', '!=', $id);
            })
        ],
        'email'                    => 'required|email',
        'phone'                    => 'required|numeric',
        'default_pos_order_status' => 'required',
        'customers_visibility'     => 'required',
        'suppliers_visibility'     => 'required',
        'products_visibility'      => 'required',

        // NEW:
        'parent_warehouse_id'      => 'nullable',
    ];

    return $rules;
}

}