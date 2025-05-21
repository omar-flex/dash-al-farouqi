<?php

namespace App\Models;

use Arr;
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
        return $this->hasManyThrough(Outbound::class, EnterRequest::class, 'customer_id');
    }

    public function getInbounds()
    {
        $id = Arr::get($this->attributes, 'id');
        $fromDate = request('from_date', now()->startOfYear()->format('Y-m-d'));
        $toDate = request('to_date', now()->format('Y-m-d'));

        return EnterRequest::whereIntegerInRaw('manifest_type_number', [7, 4])
            ->where('customer_id', $id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->whereIntegerInRaw('status_id', [EnterRequestStatus::AUTHORIZATION, EnterRequestStatus::APPROVED])
            ->when(request('customer_id'), function ($query) {
                return $query->where('customer_id', request('customer_id'));
            })
            ->orderBy('date')
            ->get();
    }

}
