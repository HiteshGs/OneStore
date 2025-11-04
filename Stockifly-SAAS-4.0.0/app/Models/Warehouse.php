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

    // include parent_warehouse_id/x_parent_warehouse_id in default payload
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
        'parent_warehouse_id',
        'x_parent_warehouse_id',
    ];

    protected $guarded = ['id', 'users', 'company_id', 'created_at', 'updated_at'];

    // hide raw ints
    protected $hidden = ['id', 'company_id', 'parent_warehouse_id'];

    // expose hashed ids + computed urls
    protected $appends = ['xid','x_company_id','logo_url','dark_logo_url','signature_url','x_parent_warehouse_id'];

    protected $filterable = ['id', 'name', 'email', 'phone', 'city', 'country', 'zipcode'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute'           => 'company_id',
        'getXParentWarehouseIdAttribute'   => 'parent_warehouse_id', // -> x_parent_warehouse_id
    ];

    protected $casts = [
        'company_id'                 => Hash::class . ':hash',
        'parent_warehouse_id'        => Hash::class . ':hash',
        'show_email_on_invoice'      => 'integer',
        'show_phone_on_invoice'      => 'integer',
        'online_store_enabled'       => 'integer',
        'is_default'                 => 'integer',
        'show_mrp_on_invoice'        => 'integer',
        'show_discount_tax_on_invoice' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function getLogoUrlAttribute()
    {
        $p = Common::getFolderPath('warehouseLogoPath');
        return $this->logo == null ? Common::getWarehouseImage('light', $this->company_id) : Common::getFileUrl($p, $this->logo);
    }

    public function getDarkLogoUrlAttribute()
    {
        $p = Common::getFolderPath('warehouseLogoPath');
        return $this->dark_logo == null ? Common::getWarehouseImage('dark', $this->company_id) : Common::getFileUrl($p, $this->dark_logo);
    }

    public function getSignatureUrlAttribute()
    {
        $p = Common::getFolderPath('warehouseLogoPath');
        return $this->signature == null ? null : Common::getFileUrl($p, $this->signature);
    }

    public function users()
    {
        return $this->belongsToMany(StaffMember::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_warehouse_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_warehouse_id');
    }
}
