<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundWarehouseItems extends Model
{
    protected $guarded = [];

    public function WarehouseItem()
    {
        return $this->belongsTo(WarehouseItems::class);
    }
}
