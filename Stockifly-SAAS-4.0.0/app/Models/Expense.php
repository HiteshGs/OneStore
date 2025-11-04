<?php

namespace App\Models;

use App\Casts\Hash;
use App\Classes\Common;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use Vinkla\Hashids\Facades\Hashids;

class Expense extends BaseModel
{
    protected $table = 'expenses';

    protected $default = ['xid'];

    protected $dates = ['date'];

    // keep these as-is
    protected $guarded = ['id', 'warehouse_id', 'created_at', 'updated_at'];

    // HIDE the raw FK and expose x_*
    protected $hidden = ['id', 'warehouse_id', 'user_id', 'expense_category_id', 'payment_mode_id'];

    // append x_payment_mode_id like others
    protected $appends = [
        'xid',
        'x_warehouse_id',
        'x_user_id',
        'x_expense_category_id',
        'x_payment_mode_id',
        'bill_url'
    ];

    // allow filtering by this FK
    protected $filterable = ['warehouse_id', 'expense_category_id', 'user_id', 'payment_mode_id'];

    // map getter => column for hashids
    protected $hashableGetterFunctions = [
        'getXUserIdAttribute' => 'user_id',
        'getXWarehouseIdAttribute' => 'warehouse_id',
        'getXExpenseCategoryIdAttribute' => 'expense_category_id',
        'getXPaymentModeIdAttribute' => 'payment_mode_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'warehouse_id' => Hash::class . ':hash',
        'user_id' => Hash::class . ':hash',
        'expense_category_id' => Hash::class . ':hash',
        'payment_mode_id' => Hash::class . ':hash',
        'amount' => 'double',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function getBillUrlAttribute()
    {
        $expenseBillPath = Common::getFolderPath('expenseBillPath');

        return $this->bill == null ? null : Common::getFileUrl($expenseBillPath, $this->bill);
    }

    public function expenseCategory()
    {
        return $this->hasOne(ExpenseCategory::class, 'id', 'expense_category_id');
    }

    public function user()
    {
        return $this->hasOne(StaffMember::class, 'id', 'user_id');
    }

    public function warehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'warehouse_id');
    }

    // NEW: payment mode relation (follow your existing hasOne pattern)
    public function paymentMode()
    {
        return $this->hasOne(PaymentMode::class, 'id', 'payment_mode_id');
    }
}
