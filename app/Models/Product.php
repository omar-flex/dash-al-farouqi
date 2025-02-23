<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    public function Items()
    {
        return $this->hasMany(WarehouseItems::class,'product_id');
    }
}
