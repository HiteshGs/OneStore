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
        return [
            'expense_category_id' => 'required',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_mode_id' => ['nullable', 'exists:payment_modes,id'],
        ];
    }
}