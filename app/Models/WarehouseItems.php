<?php

namespace App\Models;

use App\Services\Inventory\InventoryBalanceCalculator;
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
                ->whereIn('outbounds.status_id', InventoryBalanceCalculator::OFFICIAL_STATUSES)
                ->sum('quantity');
        }

        return 0;
    }

    public function SumOutboundCustomValue()
    {
        $id = Arr::get($this->attributes, 'id');
        if ($id) {
            return OutboundWarehouseItems::where('warehouse_item_id', $id)
                ->leftJoin('outbounds', 'outbounds.id', '=', 'outbound_warehouse_items.outbound_id')
                ->whereIn('outbounds.status_id', InventoryBalanceCalculator::OFFICIAL_STATUSES)
                ->sum('custom_value');
        }

        return 0;
    }

    public function SumOutboundGrossWeight()
    {
        $id = Arr::get($this->attributes, 'id');
        if ($id) {
            return OutboundWarehouseItems::where('warehouse_item_id', $id)
                ->leftJoin('outbounds', 'outbounds.id', '=', 'outbound_warehouse_items.outbound_id')
                ->whereIn('outbounds.status_id', InventoryBalanceCalculator::OFFICIAL_STATUSES)
                ->sum('outbound_warehouse_items.gross_weight');
        }

        return 0;
    }

    public function SumOutboundItemsOtherQuantity()
    {
        $id = Arr::get($this->attributes, 'id');
        if ($id) {
            return OutboundWarehouseItems::where('warehouse_item_id', $id)
                ->leftJoin('outbounds', 'outbounds.id', '=', 'outbound_warehouse_items.outbound_id')
                ->whereIn('outbounds.status_id', InventoryBalanceCalculator::OFFICIAL_STATUSES)
                ->sum('other_quantity');

        }

        return 0;
    }

    public function OutboundWarehouseItems()
    {
        return $this->hasMany(OutboundWarehouseItems::class, 'warehouse_item_id', 'id');
    }
}
