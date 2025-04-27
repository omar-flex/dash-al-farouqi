<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outbound extends Model
{
    protected $guarded = [];

    public function EnterRequest()
    {
        return $this->belongsTo(EnterRequest::class);
    }

    public function Warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function Country()
    {
        return $this->belongsTo(Country::class);
    }

    public function Status()
    {
        return $this->belongsTo(EnterRequestStatus::class, 'status_id');
    }

    public function Files()
    {
        return $this->hasMany(OutboundFile::class);
    }

    public function Customer()
    {
        return $this->hasOneThrough(Customer::class,EnterRequest::class);
    }

    public function Cars()
    {
        return $this->hasMany(OutboundCar::class,'outbound_id','id');
    }
}
