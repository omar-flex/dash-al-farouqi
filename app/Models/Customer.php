<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = ['id'];

    public function Inbounds()
    {
        return $this->hasMany(EnterRequest::class, 'customer_id');
    }

    public function Outbounds()
    {
        return $this->hasManyThrough(Outbound::class,EnterRequest::class, 'customer_id');
    }

}
