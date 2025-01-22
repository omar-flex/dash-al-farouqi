<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $guarded = [];

    public function Locations()
    {
        return $this->hasMany(WarehouseLocation::class, 'warehouse_id', 'id');
    }

}
