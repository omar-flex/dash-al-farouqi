<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    public function UnitMeasure()
    {
        return $this->belongsTo(UnitMeasure::class, 'unit_measure_id', 'id');
    }
    public function Items()
    {
        return $this->hasMany(WarehouseItems::class,'product_id');
    }
}
