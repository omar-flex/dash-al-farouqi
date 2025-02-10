<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationLine extends Model
{
    protected $guarded = ['id'];

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

}
