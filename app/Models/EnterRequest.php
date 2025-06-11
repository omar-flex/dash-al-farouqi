<?php

namespace App\Models;

use Arr;
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
        return $this->hasMany(WarehouseItems::class, 'enter_request_id')
            ->when(request()->routeIs('warehouses.report.products'), function ($query) {
                $query->when(request('warehouse_id'), function ($query) {
                    return $query->whereHas('LocationLine.Location', function ($query) {
                        $query->where('warehouse_id', request('warehouse_id'));
                    });
                })->when(request('product_id'), function ($query) {
                    return $query->where('product_id', request('product_id'));
                })->when(request('enter_request_id'), function ($query) {
                    return $query->where('enter_request_id', request('enter_request_id'));
                });
            });
    }

    public function Outbounds()
    {
        return $this->hasMany(Outbound::class, 'enter_request_id');
    }

    public function LastOutbound()
    {
        return Outbound::where('enter_request_id', Arr::get($this->attributes, 'id'))
            ->orderBy('date', 'desc')
            ->whereIn('status_id', [OutboundStatus::AUTHORIZATION, OutboundStatus::APPROVED])
            ->first();
    }


}
