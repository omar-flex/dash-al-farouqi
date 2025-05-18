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
        return EnterRequest::whereIntegerInRaw('manifest_type_number', [7, 4])
            ->where('customer_id', $id)
            ->whereIntegerInRaw('status_id', [EnterRequestStatus::AUTHORIZATION, EnterRequestStatus::APPROVED])
            ->orderBy('date')
            ->get();
    }

}
