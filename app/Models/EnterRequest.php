<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnterRequest extends Model
{
    protected $guarded = [];

    public function Customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(ClearanceCompany::class, 'clearance_company_id', 'id');
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

    public function Cars()
    {
        return $this->hasMany(EnterRequestCar::class);
    }

    public function Files()
    {
        return $this->hasMany(EnterRequestFile::class);
    }

    public function WarehouseItems()
    {
        return $this->hasMany(WarehouseItems::class, 'enter_request_id');
    }

    public function Outbounds()
    {
        return $this->hasMany(Outbound::class, 'enter_request_id');
    }

    public function LastOutbound()
    {
        return $this->hasOne(Outbound::class, 'enter_request_id')
            ->orderBy('outbounds.date', 'desc')
            ->whereIn('outbounds.status_id', [OutboundStatus::AUTHORIZATION, OutboundStatus::APPROVED])
            ->first();
    }


}
