<?php

namespace App\Models;

use App\Classes\Common;
use Illuminate\Notifications\Notifiable;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class Warehouse extends BaseModel
{
    use Notifiable;

    protected $table = 'warehouses';

    // What fields are returned by default in API responses
    protected $default = [
        'xid',
        'name',
        'company_id',
        'slug',
        'logo',
        'logo_url',
        'dark_logo',
        'dark_logo_url',
        'online_store_enabled',
        'barcode_type',

        // NEW - parent info
        'parent_warehouse_id',
        'x_parent_warehouse_id',
        'parent_name',
    ];

    protected $guarded = ['id', 'users', 'company_id', 'created_at', 'updated_at'];

    protected $hidden = ['id'];

    // Extra attributes always appended to JSON
    protected $appends = [
        'xid',
        'x_company_id',
        'logo_url',
        'dark_logo_url',
        'signature_url',

        // NEW
        'x_parent_warehouse_id',
        'parent_name',
    ];

    protected $filterable = [
        'id',
        'name',
        'email',
        'phone',
        'city',
        'country',
        'zipcode',

        // optional: let you filter by parent
        'parent_warehouse_id',
    ];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute'          => 'company_id',
        // NEW: hash for parent_warehouse_id
        'getXParentWarehouseIdAttribute'  => 'parent_warehouse_id',
    ];

    protected $casts = [
        'company_id'                  => Hash::class . ':hash',
        // NEW: cast parent_warehouse_id as hash as well
        'parent_warehouse_id'         => Hash::class . ':hash',

        'show_email_on_invoice'       => 'integer',
        'show_phone_on_invoice'       => 'integer',
        'online_store_enabled'        => 'integer',
        'is_default'                  => 'integer',
        'show_mrp_on_invoice'         => 'integer',
        'show_discount_tax_on_invoice'=> 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Parent warehouse (for your dropdown + display)
    public function parentWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'parent_warehouse_id');
    }

    // Optional: children if ever needed
    public function childrenWarehouses()
    {
        return $this->hasMany(Warehouse::class, 'parent_warehouse_id');
    }

    public function users()
    {
        return $this->belongsToMany(StaffMember::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getLogoUrlAttribute()
    {
        $warehouseLogoPath = Common::getFolderPath('warehouseLogoPath');

        return $this->logo == null
            ? Common::getWarehouseImage('light', $this->company_id)
            : Common::getFileUrl($warehouseLogoPath, $this->logo);
    }

    public function getDarkLogoUrlAttribute()
    {
        $warehouseLogoPath = Common::getFolderPath('warehouseLogoPath');

        return $this->dark_logo == null
            ? Common::getWarehouseImage('dark', $this->company_id)
            : Common::getFileUrl($warehouseLogoPath, $this->dark_logo);
    }

    public function getSignatureUrlAttribute()
    {
        $warehouseLogoPath = Common::getFolderPath('warehouseLogoPath');

        return $this->signature == null
            ? null
            : Common::getFileUrl($warehouseLogoPath, $this->signature);
    }

    // NEW: hashed parent ID (this is used by hashableGetterFunctions)
    public function getXParentWarehouseIdAttribute()
    {
        return $this->getHashAttribute('parent_warehouse_id');
    }

    // NEW: parent_name for the table column
    public function getParentNameAttribute()
    {
        return $this->parentWarehouse ? $this->parentWarehouse->name : null;
    }
}