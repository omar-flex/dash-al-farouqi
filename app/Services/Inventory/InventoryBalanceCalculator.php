<?php

namespace App\Services\Inventory;

use App\Models\OutboundStatus;
use App\Models\OutboundWarehouseItems;
use App\Models\WarehouseItems;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryBalanceCalculator
{
    /** Workflow states that officially consume warehouse availability. */
    public const RESERVING_STATUSES = [
        OutboundStatus::VALIDATION,
        OutboundStatus::AUTHORIZATION,
        OutboundStatus::NEED_REVISION,
        OutboundStatus::APPROVED,
    ];

    /**
     * These states represent stock that has progressed beyond reservation and
     * is considered officially released in inventory reports.
     */
    public const OFFICIAL_STATUSES = [
        OutboundStatus::VALIDATION,
        OutboundStatus::AUTHORIZATION,
        OutboundStatus::NEED_REVISION,
        OutboundStatus::APPROVED,
    ];

    public function reservedTotalsQuery(): Builder
    {
        return $this->totalsQueryForStatuses(self::RESERVING_STATUSES);
    }

    public function officialTotalsQuery(): Builder
    {
        return $this->totalsQueryForStatuses(self::OFFICIAL_STATUSES);
    }

    /**
     * Recalculate stored availability from the ledger source of truth.
     *
     * This method is safe to call either on its own or from an existing
     * transaction. It always locks the stock and movement rows in a stable
     * order before deriving balances.
     *
     * @return Collection<int, WarehouseItems>
     */
    public function recalculateMany(iterable $warehouseItemIds): Collection
    {
        $ids = $this->normaliseIds($warehouseItemIds);

        if ($ids === []) {
            return collect();
        }

        return DB::transaction(function () use ($ids): Collection {
            $items = WarehouseItems::query()
                ->whereKey($ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($items->count() !== count($ids)) {
                $found = $items->modelKeys();
                $missing = array_values(array_diff($ids, $found));

                $exception = (new ModelNotFoundException)->setModel(WarehouseItems::class, $missing);
                throw $exception;
            }

            // Locking the concrete ledger rows prevents a concurrent update or
            // delete from invalidating the aggregate calculated below.
            OutboundWarehouseItems::query()
                ->whereIn('warehouse_item_id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $totals = $this->reservedTotalsQuery()
                ->whereIn('outbound_warehouse_items.warehouse_item_id', $ids)
                ->get()
                ->keyBy('warehouse_item_id');

            foreach ($items as $item) {
                $allocated = $totals->get($item->id);
                $remainingQuantity = $this->normaliseNumber(
                    (float) $item->quantity - (float) ($allocated->quantity ?? 0)
                );
                $remainingOtherQuantity = $item->other_quantity === null
                    ? null
                    : $this->normaliseNumber(
                        (float) $item->other_quantity - (float) ($allocated->other_quantity ?? 0)
                    );

                $item->forceFill([
                    'remaining_quantity' => $remainingQuantity,
                    'remaining_other_quantity' => $remainingOtherQuantity,
                    'is_status' => $remainingQuantity > 0,
                ])->save();
            }

            return $items->fresh();
        }, 5);
    }

    /**
     * Backwards-compatible descriptive alias used by repair tooling.
     *
     * @return Collection<int, WarehouseItems>
     */
    public function recalculateForWarehouseItemIds(iterable $warehouseItemIds): Collection
    {
        return $this->recalculateMany($warehouseItemIds);
    }

    /**
     * Calculate availability from ledger rows without trusting cached columns.
     *
     * @return array{quantity: float, other_quantity: float|null}
     */
    public function availableFor(WarehouseItems|int $item, ?int $excludingLedgerId = null): array
    {
        $warehouseItem = $item instanceof WarehouseItems
            ? $item
            : WarehouseItems::query()->findOrFail($item);

        $query = DB::table('outbound_warehouse_items')
            ->join('outbounds', 'outbounds.id', '=', 'outbound_warehouse_items.outbound_id')
            ->where('outbound_warehouse_items.warehouse_item_id', $warehouseItem->id)
            ->whereIn('outbounds.status_id', self::RESERVING_STATUSES);

        if ($excludingLedgerId !== null) {
            $query->where('outbound_warehouse_items.id', '<>', $excludingLedgerId);
        }

        $totals = $query->selectRaw(
            'COALESCE(SUM(outbound_warehouse_items.quantity), 0) as quantity, '
            .'COALESCE(SUM(outbound_warehouse_items.other_quantity), 0) as other_quantity'
        )->first();

        return [
            'quantity' => $this->normaliseNumber(
                (float) $warehouseItem->quantity - (float) ($totals->quantity ?? 0)
            ),
            'other_quantity' => $warehouseItem->other_quantity === null
                ? null
                : $this->normaliseNumber(
                    (float) $warehouseItem->other_quantity - (float) ($totals->other_quantity ?? 0)
                ),
        ];
    }

    private function totalsQueryForStatuses(array $statuses): Builder
    {
        return DB::table('outbound_warehouse_items')
            ->join('outbounds', 'outbounds.id', '=', 'outbound_warehouse_items.outbound_id')
            ->whereNotNull('outbound_warehouse_items.warehouse_item_id')
            ->whereIn('outbounds.status_id', $statuses)
            ->selectRaw(
                'outbound_warehouse_items.warehouse_item_id, '
                .'COALESCE(SUM(outbound_warehouse_items.quantity), 0) as quantity, '
                .'COALESCE(SUM(outbound_warehouse_items.other_quantity), 0) as other_quantity'
            )
            ->groupBy('outbound_warehouse_items.warehouse_item_id');
    }

    /** @return list<int> */
    private function normaliseIds(iterable $ids): array
    {
        return collect($ids)
            ->map(function ($id): int {
                if ($id instanceof WarehouseItems) {
                    return (int) $id->getKey();
                }

                return (int) $id;
            })
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function normaliseNumber(float $value): float
    {
        $rounded = round($value, 3);

        return abs($rounded) < 0.0005 ? 0.0 : $rounded;
    }
}
