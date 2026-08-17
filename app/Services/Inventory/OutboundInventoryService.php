<?php

namespace App\Services\Inventory;

use App\Models\Outbound;
use App\Models\OutboundCar;
use App\Models\OutboundFile;
use App\Models\OutboundStatus;
use App\Models\OutboundWarehouseItems;
use App\Models\WarehouseItems;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutboundInventoryService
{
    private const COMPARISON_TOLERANCE = 0.0005;

    public function __construct(private readonly InventoryBalanceCalculator $balanceCalculator) {}

    /**
     * Return stock selectable for an outbound without trusting cached balances.
     * Current selections remain visible even when they consume the final unit.
     *
     * @return Collection<int, WarehouseItems>
     */
    public function selectableWarehouseItems(Outbound $outbound): Collection
    {
        $reservedTotals = $this->balanceCalculator
            ->reservedTotalsQuery()
            ->where('outbounds.id', '<>', $outbound->id);

        return WarehouseItems::query()
            ->leftJoinSub($reservedTotals, 'reserved_totals', function ($join): void {
                $join->on('reserved_totals.warehouse_item_id', '=', 'warehouse_items.id');
            })
            ->where('warehouse_items.enter_request_id', $outbound->enter_request_id)
            ->where(function ($query) use ($outbound): void {
                $query->whereRaw(
                    '(warehouse_items.quantity - COALESCE(reserved_totals.quantity, 0)) > 0'
                )->orWhereExists(function ($movementQuery) use ($outbound): void {
                    $movementQuery->selectRaw('1')
                        ->from('outbound_warehouse_items as current_outbound_items')
                        ->whereColumn(
                            'current_outbound_items.warehouse_item_id',
                            'warehouse_items.id',
                        )
                        ->where('current_outbound_items.outbound_id', $outbound->id);
                });
            })
            ->select('warehouse_items.*')
            ->selectRaw(
                'ROUND(warehouse_items.quantity - COALESCE(reserved_totals.quantity, 0), 3) '
                .'as calculated_available_quantity'
            )
            ->selectRaw(
                'CASE WHEN warehouse_items.other_quantity IS NULL THEN NULL '
                .'ELSE ROUND(warehouse_items.other_quantity - COALESCE(reserved_totals.other_quantity, 0), 3) END '
                .'as calculated_available_other_quantity'
            )
            ->with('Product.UnitMeasure', 'LocationLine.Location.Warehouse')
            ->orderBy('warehouse_items.id')
            ->get();
    }

    /**
     * Replace an outbound's complete inventory allocation atomically.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function sync(Outbound $outbound, array $items, string $action): Outbound
    {
        return DB::transaction(function () use ($outbound, $items, $action): Outbound {
            $lockedOutbound = Outbound::query()
                ->whereKey($outbound->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $normalisedItems = $this->normaliseItems($items, $action);

            if ((int) $lockedOutbound->status_id === OutboundStatus::VALIDATION) {
                return $this->assertIdempotentSubmittedPayload($lockedOutbound, $normalisedItems, $action);
            }

            $this->assertProductsCanBeEdited($lockedOutbound);

            $oldWarehouseItemIds = OutboundWarehouseItems::query()
                ->where('outbound_id', $lockedOutbound->id)
                ->whereNotNull('warehouse_item_id')
                ->pluck('warehouse_item_id');

            $newWarehouseItemIds = collect($normalisedItems)->pluck('warehouse_item_id');
            $affectedWarehouseItemIds = $oldWarehouseItemIds
                ->merge($newWarehouseItemIds)
                ->map(fn ($id): int => (int) $id)
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $warehouseItems = WarehouseItems::query()
                ->whereKey($affectedWarehouseItemIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Every stock mutation locks the stock row first. Locking all related
            // ledger rows next keeps concurrent outbounds from overselling it.
            OutboundWarehouseItems::query()
                ->whereIn('warehouse_item_id', $affectedWarehouseItemIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $existingMovements = OutboundWarehouseItems::query()
                ->where('outbound_id', $lockedOutbound->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $cars = $this->lockAndValidateCars($lockedOutbound, $normalisedItems);
            $this->validateWarehouseOwnership($lockedOutbound, $normalisedItems, $warehouseItems);
            $this->validateMovementOwnership($normalisedItems, $existingMovements);
            $this->validateAvailability($lockedOutbound, $normalisedItems, $warehouseItems);
            $this->validatePackageTotal($lockedOutbound, $normalisedItems, $action);

            $warehouseItems->load('LocationLine.Location.Warehouse');
            $this->persistMovements(
                $lockedOutbound,
                $normalisedItems,
                $warehouseItems,
                $cars,
                $existingMovements,
                $action,
            );

            if ($action === 'submit') {
                $lockedOutbound->update(['status_id' => OutboundStatus::VALIDATION]);
            }

            $this->balanceCalculator->recalculateMany($affectedWarehouseItemIds);

            return $lockedOutbound->fresh(['OutboundWarehouseItems']);
        }, 5);
    }

    /**
     * Delete an outbound and its dependent records, returning physical file paths
     * for the controller to remove after the database transaction commits.
     *
     * @return list<string>
     */
    public function deleteOutbound(Outbound $outbound): array
    {
        return DB::transaction(function () use ($outbound): array {
            $lockedOutbound = Outbound::query()
                ->whereKey($outbound->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertNotApproved($lockedOutbound);

            $warehouseItemIds = OutboundWarehouseItems::query()
                ->where('outbound_id', $lockedOutbound->id)
                ->whereNotNull('warehouse_item_id')
                ->pluck('warehouse_item_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            WarehouseItems::query()
                ->whereKey($warehouseItemIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $movements = OutboundWarehouseItems::query()
                ->where('outbound_id', $lockedOutbound->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $files = OutboundFile::query()
                ->where('outbound_id', $lockedOutbound->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            OutboundCar::query()
                ->where('outbound_id', $lockedOutbound->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $paths = $files->pluck('path')->filter()->values()->all();

            $movements->each->delete();
            $files->each->delete();
            OutboundCar::query()->where('outbound_id', $lockedOutbound->id)->delete();

            $this->balanceCalculator->recalculateMany($warehouseItemIds);
            $lockedOutbound->delete();

            return $paths;
        }, 5);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function normaliseItems(array $items, string $action): array
    {
        if (! in_array($action, ['draft', 'submit'], true)) {
            $this->fail('action', 'The action must be draft or submit.');
        }

        if ($items === []) {
            $this->fail('items', 'At least one warehouse item is required.');
        }

        $normalised = [];
        $pairs = [];
        $movementIds = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $this->fail("items.$index", 'The warehouse item payload is invalid.');
            }

            $movementId = $this->optionalPositiveInteger($item['id'] ?? null, "items.$index.id");
            $warehouseItemId = $this->positiveInteger(
                $item['warehouse_item_id'] ?? null,
                "items.$index.warehouse_item_id",
            );
            $carId = $this->positiveInteger(
                $item['outbound_car_id'] ?? null,
                "items.$index.outbound_car_id",
            );
            $quantity = $this->positiveNumber($item['quantity'] ?? null, "items.$index.quantity");
            $otherQuantity = $this->optionalNonNegativeNumber(
                $item['other_quantity'] ?? null,
                "items.$index.other_quantity",
            );

            $pair = $warehouseItemId.'-'.$carId;
            if (isset($pairs[$pair])) {
                $this->fail(
                    "items.$index.warehouse_item_id",
                    'Duplicate warehouse item-car pairs are not allowed.',
                );
            }
            $pairs[$pair] = true;

            if ($movementId !== null && isset($movementIds[$movementId])) {
                $this->fail("items.$index.id", 'The same outbound item cannot be submitted more than once.');
            }
            if ($movementId !== null) {
                $movementIds[$movementId] = true;
            }

            $normalised[] = [
                'id' => $movementId,
                'warehouse_item_id' => $warehouseItemId,
                'outbound_car_id' => $carId,
                'quantity' => $quantity,
                'other_quantity' => $otherQuantity,
                '_index' => $index,
            ];
        }

        return $normalised;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function lockAndValidateCars(Outbound $outbound, array $items): Collection
    {
        $carIds = collect($items)
            ->pluck('outbound_car_id')
            ->unique()
            ->sort()
            ->values();

        $cars = OutboundCar::query()
            ->whereKey($carIds->all())
            ->where('outbound_id', $outbound->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            if (! $cars->has($item['outbound_car_id'])) {
                $this->fail(
                    "items.{$item['_index']}.outbound_car_id",
                    'The selected car does not belong to this outbound.',
                );
            }
        }

        return $cars;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  Collection<int, WarehouseItems>  $warehouseItems
     */
    private function validateWarehouseOwnership(Outbound $outbound, array $items, Collection $warehouseItems): void
    {
        foreach ($items as $item) {
            /** @var WarehouseItems|null $warehouseItem */
            $warehouseItem = $warehouseItems->get($item['warehouse_item_id']);

            if (! $warehouseItem || (int) $warehouseItem->enter_request_id !== (int) $outbound->enter_request_id) {
                $this->fail(
                    "items.{$item['_index']}.warehouse_item_id",
                    'The selected warehouse item does not belong to this inbound manifest.',
                );
            }

            if ((float) $warehouseItem->quantity <= 0) {
                $this->fail(
                    "items.{$item['_index']}.warehouse_item_id",
                    'The selected warehouse item has no releasable source quantity.',
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  Collection<int, OutboundWarehouseItems>  $existingMovements
     */
    private function validateMovementOwnership(array $items, Collection $existingMovements): void
    {
        $existingIds = $existingMovements->modelKeys();

        foreach ($items as $item) {
            if ($item['id'] !== null && ! in_array($item['id'], $existingIds, true)) {
                $this->fail(
                    "items.{$item['_index']}.id",
                    'The selected outbound item does not belong to this outbound.',
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  Collection<int, WarehouseItems>  $warehouseItems
     */
    private function validateAvailability(Outbound $outbound, array $items, Collection $warehouseItems): void
    {
        $requestedByWarehouseItem = collect($items)->groupBy('warehouse_item_id');
        $reservedByOtherOutbounds = $this->balanceCalculator
            ->reservedTotalsQuery()
            ->whereIn('outbound_warehouse_items.warehouse_item_id', $requestedByWarehouseItem->keys()->all())
            ->where('outbounds.id', '<>', $outbound->id)
            ->get()
            ->keyBy('warehouse_item_id');

        foreach ($requestedByWarehouseItem as $warehouseItemId => $requestedItems) {
            /** @var WarehouseItems $warehouseItem */
            $warehouseItem = $warehouseItems->get($warehouseItemId);
            $reserved = $reservedByOtherOutbounds->get($warehouseItemId);
            $requestedQuantity = (float) $requestedItems->sum('quantity');
            $availableQuantity = (float) $warehouseItem->quantity - (float) ($reserved->quantity ?? 0);
            $firstIndex = $requestedItems->first()['_index'];

            if ($requestedQuantity - $availableQuantity > self::COMPARISON_TOLERANCE) {
                $this->fail(
                    "items.$firstIndex.quantity",
                    "Requested quantity ($requestedQuantity) exceeds the available quantity ($availableQuantity).",
                );
            }

            $requestedOtherQuantity = (float) $requestedItems->sum(
                fn (array $item): float => (float) ($item['other_quantity'] ?? 0),
            );

            if ($warehouseItem->other_quantity === null) {
                if ($requestedOtherQuantity > self::COMPARISON_TOLERANCE) {
                    $this->fail(
                        "items.$firstIndex.other_quantity",
                        'This warehouse item does not have a secondary quantity.',
                    );
                }

                continue;
            }

            $availableOtherQuantity = (float) $warehouseItem->other_quantity
                - (float) ($reserved->other_quantity ?? 0);

            if ($requestedOtherQuantity - $availableOtherQuantity > self::COMPARISON_TOLERANCE) {
                $this->fail(
                    "items.$firstIndex.other_quantity",
                    "Requested secondary quantity ($requestedOtherQuantity) exceeds the available quantity ($availableOtherQuantity).",
                );
            }
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function validatePackageTotal(Outbound $outbound, array $items, string $action): void
    {
        $mustMatchPackageTotal = $action === 'submit'
            || in_array((int) $outbound->status_id, InventoryBalanceCalculator::OFFICIAL_STATUSES, true);

        if (! $mustMatchPackageTotal) {
            return;
        }

        $requestedQuantity = round((float) collect($items)->sum('quantity'), 3);
        $packageQuantity = round((float) $outbound->quantity_packages, 3);

        if (abs($requestedQuantity - $packageQuantity) > self::COMPARISON_TOLERANCE) {
            $this->fail(
                'items',
                "Total item quantity ($requestedQuantity) must equal the outbound package quantity ($packageQuantity).",
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  Collection<int, WarehouseItems>  $warehouseItems
     * @param  Collection<int, OutboundCar>  $cars
     * @param  Collection<int, OutboundWarehouseItems>  $existingMovements
     */
    private function persistMovements(
        Outbound $outbound,
        array $items,
        Collection $warehouseItems,
        Collection $cars,
        Collection $existingMovements,
        string $action,
    ): void {
        $existingById = $existingMovements->keyBy('id');
        $existingByPair = $existingMovements->keyBy(
            fn (OutboundWarehouseItems $movement): string => $movement->warehouse_item_id.'-'.$movement->outbound_car_id,
        );
        $usedMovementIds = [];
        $resolved = [];

        foreach ($items as $item) {
            $pair = $item['warehouse_item_id'].'-'.$item['outbound_car_id'];
            $movement = $item['id'] !== null && ! isset($usedMovementIds[$item['id']])
                ? $existingById->get($item['id'])
                : null;

            if (! $movement) {
                $pairMovement = $existingByPair->get($pair);
                if ($pairMovement && ! isset($usedMovementIds[$pairMovement->id])) {
                    $movement = $pairMovement;
                }
            }

            if ($movement) {
                $usedMovementIds[$movement->id] = true;
            }

            $resolved[] = [$item, $movement];
        }

        $existingMovements
            ->reject(fn (OutboundWarehouseItems $movement): bool => isset($usedMovementIds[$movement->id]))
            ->each->delete();

        foreach ($resolved as [$item, $movement]) {
            /** @var WarehouseItems $warehouseItem */
            $warehouseItem = $warehouseItems->get($item['warehouse_item_id']);
            /** @var OutboundCar $car */
            $car = $cars->get($item['outbound_car_id']);

            $attributes = [
                'quantity' => $item['quantity'],
                'other_quantity' => $item['other_quantity'],
                'location' => $this->warehouseLocation($warehouseItem),
                'warehouse_item_id' => $warehouseItem->id,
                'custom_value' => $this->proportionalValue($warehouseItem->custom_value, $warehouseItem, $item['quantity']),
                'gross_weight' => $this->proportionalValue($warehouseItem->gross_weight, $warehouseItem, $item['quantity']),
                'net_weight' => $this->proportionalValue($warehouseItem->net_weight, $warehouseItem, $item['quantity']),
                'cpm' => $this->proportionalValue($warehouseItem->cpm, $warehouseItem, $item['quantity']),
                'cpm_capacity' => $this->proportionalValue($warehouseItem->cpm_capacity, $warehouseItem, $item['quantity']),
                'is_status' => $action === 'submit'
                    || in_array((int) $outbound->status_id, InventoryBalanceCalculator::OFFICIAL_STATUSES, true),
                'outbound_id' => $outbound->id,
                'outbound_car_id' => $car->id,
            ];

            if ($movement) {
                $movement->update($attributes);
            } else {
                OutboundWarehouseItems::query()->create($attributes);
            }
        }
    }

    private function warehouseLocation(WarehouseItems $warehouseItem): string
    {
        return collect([
            $warehouseItem->LocationLine?->Location?->Warehouse?->code,
            $warehouseItem->LocationLine?->Location?->code,
            $warehouseItem->LocationLine?->code,
            $warehouseItem->level,
            $warehouseItem->pallet,
        ])->filter(fn ($value): bool => $value !== null && $value !== '')
            ->implode('-');
    }

    private function proportionalValue(mixed $value, WarehouseItems $warehouseItem, float $quantity): ?float
    {
        if ($value === null) {
            return null;
        }

        return ((float) $value / (float) $warehouseItem->quantity) * $quantity;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function assertIdempotentSubmittedPayload(Outbound $outbound, array $items, string $action): Outbound
    {
        if ($action !== 'submit') {
            $this->fail('outbound', 'Items in validation can only accept an identical submit retry.');
        }

        $existingMovements = OutboundWarehouseItems::query()
            ->where('outbound_id', $outbound->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($existingMovements->count() !== count($items)) {
            $this->fail('items', 'The submitted items differ from the release already in validation.');
        }

        $existingByPair = $existingMovements->keyBy(
            fn (OutboundWarehouseItems $movement): string => $movement->warehouse_item_id.'-'.$movement->outbound_car_id,
        );

        foreach ($items as $item) {
            $pair = $item['warehouse_item_id'].'-'.$item['outbound_car_id'];
            /** @var OutboundWarehouseItems|null $movement */
            $movement = $existingByPair->get($pair);

            if (! $movement
                || ($item['id'] !== null && (int) $movement->id !== $item['id'])
                || ! $this->sameNumber((float) $movement->quantity, $item['quantity'])
                || ! $this->sameNullableNumber($movement->other_quantity, $item['other_quantity'])) {
                $this->fail('items', 'The submitted items differ from the release already in validation.');
            }
        }

        return $outbound->fresh(['OutboundWarehouseItems']);
    }

    private function assertProductsCanBeEdited(Outbound $outbound): void
    {
        $this->assertNotApproved($outbound);

        if (! in_array((int) $outbound->status_id, [
            OutboundStatus::WH_RELEASE_PRODUCT,
            OutboundStatus::NEED_REVISION,
        ], true)) {
            $this->fail('outbound', 'Products cannot be edited at the current outbound status.');
        }
    }

    private function assertNotApproved(Outbound $outbound): void
    {
        if ((int) $outbound->status_id === OutboundStatus::APPROVED) {
            $this->fail('outbound', 'Approved outbounds cannot be modified or deleted.');
        }
    }

    private function positiveInteger(mixed $value, string $key): int
    {
        $normalised = filter_var($value, FILTER_VALIDATE_INT);
        if ($normalised === false || $normalised <= 0) {
            $this->fail($key, 'The selected identifier is invalid.');
        }

        return $normalised;
    }

    private function optionalPositiveInteger(mixed $value, string $key): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->positiveInteger($value, $key);
    }

    private function positiveNumber(mixed $value, string $key): float
    {
        if (! is_numeric($value)) {
            $this->fail($key, 'The quantity must be greater than zero.');
        }

        $normalised = round((float) $value, 3);
        if ($normalised <= 0) {
            $this->fail($key, 'The quantity must be at least 0.001.');
        }

        return $normalised;
    }

    private function optionalNonNegativeNumber(mixed $value, string $key): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            $this->fail($key, 'The secondary quantity must be zero or greater.');
        }

        $normalised = round((float) $value, 3);
        if ($normalised < 0) {
            $this->fail($key, 'The secondary quantity must be zero or greater.');
        }

        return $normalised;
    }

    private function sameNumber(float $first, float $second): bool
    {
        return abs(round($first, 3) - round($second, 3)) <= self::COMPARISON_TOLERANCE;
    }

    private function sameNullableNumber(mixed $first, mixed $second): bool
    {
        if ($first === null || $second === null) {
            return $first === null && $second === null;
        }

        return $this->sameNumber((float) $first, (float) $second);
    }

    private function fail(string $key, string $message): void
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
