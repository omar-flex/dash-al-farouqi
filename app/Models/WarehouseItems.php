<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseItems extends Model
{
    protected $guarded = [];

    public function Product()
    {
        return $this->belongsTo(Product::class);
    }
}
