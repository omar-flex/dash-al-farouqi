<?php

namespace App\Services\Inventory;

use App\Models\EnterRequestStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProductTraceabilityReport
{
    private const INBOUND_STATUSES = [
        EnterRequestStatus::VALIDATION,
        EnterRequestStatus::AUTHORIZATION,
        EnterRequestStatus::NEED_REVISION,
        EnterRequestStatus::APPROVED,
    ];

    public function productsForCustomer(int $customerId): EloquentBuilder
    {
        return Product::query()->whereHas('Items.EnterRequest', fn (EloquentBuilder $query) => $query
            ->where('customer_id', $customerId)
            ->whereIntegerInRaw('status_id', self::INBOUND_STATUSES));
    }

    public function movements(array $filters): Builder
    {
        $stock = DB::table('warehouse_items as stock')
            ->join('enter_requests as inbound', 'inbound.id', '=', 'stock.enter_request_id')
            ->leftJoin('customers as customer', 'customer.id', '=', 'inbound.customer_id')
            ->leftJoin('location_lines as line', 'line.id', '=', 'stock.location_line_id')
            ->leftJoin('warehouse_locations as hall', 'hall.id', '=', 'line.location_id')
            ->leftJoin('warehouses as warehouse', function ($join): void {
                $join->on('warehouse.id', '=', DB::raw('COALESCE(hall.warehouse_id, inbound.warehouse_id)'));
            })
            ->where('stock.product_id', $filters['product_id'])
            ->where('inbound.customer_id', $filters['customer_id'])
            ->whereIntegerInRaw('inbound.status_id', self::INBOUND_STATUSES)
            ->when($filters['warehouse_id'] ?? null, fn (Builder $query, $id) => $query->where('warehouse.id', $id))
            ->when(isset($filters['batch_number']), fn (Builder $query) => $query->where('stock.batch_number', $filters['batch_number']))
            ->select([
                'inbound.id as inbound_id',
                'inbound.bound_number as inbound_reference',
                'customer.name as customer_name',
                'stock.batch_number',
                'warehouse.code as warehouse_code',
                'hall.code as hall_code',
                'line.code as line_code',
                'stock.level',
                'stock.pallet',
            ]);

        $inbounds = (clone $stock)
            ->where('inbound.date', '<=', $filters['to_date'])
            ->selectRaw('inbound.date as movement_date, 1 as direction, stock.id as movement_id, NULL as outbound_id, NULL as outbound_reference, NULL as recorded_location')
            ->addSelect('inbound.status_id', 'stock.quantity', 'stock.other_quantity', 'stock.other_quantity as balance_other_quantity');

        $outbounds = $stock
            ->join('outbound_warehouse_items as movement', 'movement.warehouse_item_id', '=', 'stock.id')
            ->join('outbounds as outbound', function ($join): void {
                $join->on('outbound.id', '=', 'movement.outbound_id')
                    ->on('outbound.enter_request_id', '=', 'inbound.id');
            })
            ->whereIntegerInRaw('outbound.status_id', InventoryBalanceCalculator::OFFICIAL_STATUSES)
            ->where('outbound.date', '<=', $filters['to_date'])
            ->selectRaw('outbound.date as movement_date, -1 as direction')
            ->addSelect(
                'movement.id as movement_id',
                'outbound.id as outbound_id',
                'outbound.outbound_number as outbound_reference',
                'movement.location as recorded_location',
                'outbound.status_id',
                'movement.quantity',
                'movement.other_quantity',
            )
            ->selectRaw('CASE WHEN stock.other_quantity IS NOT NULL THEN movement.other_quantity END as balance_other_quantity');

        return DB::query()->fromSub($inbounds->unionAll($outbounds), 'movements');
    }

    public function summary(Builder $movements, ?string $fromDate): object
    {
        return (clone $movements)->selectRaw(
            'COALESCE(SUM(CASE WHEN movement_date < COALESCE(?, movement_date) THEN direction * quantity END), 0) as opening_qty,
             SUM(CASE WHEN movement_date < COALESCE(?, movement_date) THEN direction * balance_other_quantity END) as opening_other_qty,
             COALESCE(SUM(CASE WHEN movement_date >= COALESCE(?, movement_date) AND direction = 1 THEN quantity END), 0) as inbound_qty,
             SUM(CASE WHEN movement_date >= COALESCE(?, movement_date) AND direction = 1 THEN other_quantity END) as inbound_other_qty,
             COALESCE(SUM(CASE WHEN movement_date >= COALESCE(?, movement_date) AND direction = -1 THEN quantity END), 0) as outbound_qty,
             SUM(CASE WHEN movement_date >= COALESCE(?, movement_date) AND direction = -1 THEN other_quantity END) as outbound_other_qty,
             COALESCE(SUM(direction * quantity), 0) as closing_qty,
             SUM(direction * balance_other_quantity) as closing_other_qty',
            array_fill(0, 6, $fromDate),
        )->first();
    }

    public function inPeriod(Builder $movements, ?string $fromDate): Builder
    {
        return (clone $movements)
            ->when($fromDate, fn (Builder $query, $date) => $query->where('movement_date', '>=', $date))
            ->orderByDesc('movement_date')
            ->orderByDesc('direction')
            ->orderByDesc('movement_id');
    }

    public static function location(object $movement): string
    {
        return $movement->recorded_location ?: implode('-', array_filter([
            $movement->warehouse_code,
            $movement->hall_code,
            $movement->line_code,
            $movement->level,
            $movement->pallet,
        ], fn ($value) => $value !== null && $value !== ''));
    }
}
