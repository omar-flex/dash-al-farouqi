<?php

namespace App\Models;

use Arr;
use Illuminate\Database\Eloquent\Model;

class WarehouseItems extends Model
{
    protected $guarded = [];

    public function Product()
    {
        return $this->belongsTo(Product::class);
    }

    public function LocationLine()
    {
        return $this->belongsTo(LocationLine::class);
    }

    public function EnterRequest()
    {
        return $this->belongsTo(EnterRequest::class);
    }

    public function SumOutboundItems()
    {
        $id = Arr::get($this->attributes, 'id');
        if ($id) {
            return OutboundWarehouseItems::where('warehouse_item_id', $id)
                ->leftJoin('outbounds', 'outbounds.id', '=', 'outbound_warehouse_items.outbound_id')
                ->whereIn('outbounds.status_id', [OutboundStatus::AUTHORIZATION, OutboundStatus::APPROVED])
                ->sum('quantity');
        }
        return 0;
    }
}
