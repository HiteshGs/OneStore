<?php

namespace App\SuperAdmin\Http\Requests\Front\Register;

use App\SuperAdmin\Http\Requests\Front\FrontCoreRequest;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Facades\Module;

class StoreRegisterRequest extends FrontCoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'company_name'     => 'required',
            'company_email'     => ['required', 'email', 'unique:users,email'],
            'company_phone'     => ['required', 'unique:users,phone'],
            'password'     => 'required|min:8',
            'confirm_password'     => 'required|same:password',
            'condition'     => 'required',
        ];

        $companyTableColumns = Schema::getColumnListing('companies');
        if (in_array('subdomain', $companyTableColumns) && Module::has('StockiflySaasSubdomain') && Module::isEnabled('StockiflySaasSubdomain') && app_type() == 'saas') {
            $rules['subdomain'] = [
                'required',
                'string',
                'regex:/^(?!-)[a-z0-9-]{1,63}(?<!-)$/', // Valid subdomain format
                'unique:companies,subdomain',
            ];
        }

        return $rules;
    }
}
