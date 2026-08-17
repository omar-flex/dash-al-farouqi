<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;

class InventoryAuditor
{
    private const EPSILON = 0.0005;

    private const CONSTRAINT_BLOCKERS = [
        'balance_mismatch',
        'other_balance_mismatch',
        'availability_status_mismatch',
        'outbound_total_mismatch',
        'over_allocation',
        'other_over_allocation',
        'warehouse_item_missing_enter_request',
        'non_positive_warehouse_quantity',
        'negative_warehouse_other_quantity',
        'ledger_missing_warehouse_item',
        'ledger_missing_outbound',
        'ledger_missing_car',
        'ledger_car_outbound_mismatch',
        'ledger_enter_request_mismatch',
        'duplicate_ledger_assignment',
        'invalid_outbound_status',
        'non_positive_ledger_quantity',
        'negative_ledger_other_quantity',
    ];

    public function __construct(
        private readonly InventoryBalanceCalculator $balanceCalculator,
    ) {}

    /**
     * Run a read-only inventory audit.
     *
     * @param  list<int>|null  $warehouseItemIds
     * @return array<string, mixed>
     */
    public function run(?array $warehouseItemIds = null): array
    {
        $ids = $warehouseItemIds === null
            ? null
            : collect($warehouseItemIds)->map(fn ($id) => (int) $id)->filter()->unique()->sort()->values()->all();

        $issues = [];
        $this->auditBalances($issues, $ids);
        $this->auditLedgerReferences($issues, $ids);
        $this->auditDuplicateAssignments($issues, $ids);
        $this->auditOutboundTotals($issues, $ids);
        $this->auditOutboundStatuses($issues, $ids);

        usort($issues, fn (array $left, array $right): int => [
            $left['code'],
            $left['warehouse_item_id'] ?? 0,
            $left['ledger_id'] ?? 0,
            $left['outbound_id'] ?? 0,
        ] <=> [
            $right['code'],
            $right['warehouse_item_id'] ?? 0,
            $right['ledger_id'] ?? 0,
            $right['outbound_id'] ?? 0,
        ]);

        $byCode = collect($issues)->countBy('code')->sortKeys()->all();
        $blockerCount = collect($issues)
            ->whereIn('code', self::CONSTRAINT_BLOCKERS)
            ->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'read_only' => true,
            'scope' => [
                'warehouse_item_ids' => $ids,
            ],
            'policy' => [
                'reserving_outbound_status_ids' => InventoryBalanceCalculator::RESERVING_STATUSES,
                'official_outbound_status_ids' => InventoryBalanceCalculator::OFFICIAL_STATUSES,
                'decimal_scale' => 3,
            ],
            'summary' => [
                'issue_count' => count($issues),
                'by_code' => $byCode,
                'constraint_blocker_count' => $blockerCount,
                'constraints_ready' => $blockerCount === 0,
            ],
            'issues' => $issues,
        ];
    }

    /** @param list<array<string, mixed>> $issues */
    private function auditBalances(array &$issues, ?array $ids): void
    {
        $query = DB::table('warehouse_items as wi')
            ->leftJoinSub($this->balanceCalculator->reservedTotalsQuery(), 'reserved', function ($join): void {
                $join->on('reserved.warehouse_item_id', '=', 'wi.id');
            })
            ->leftJoin('enter_requests as er', 'er.id', '=', 'wi.enter_request_id')
            ->select([
                'wi.id',
                'wi.quantity',
                'wi.other_quantity',
                'wi.remaining_quantity',
                'wi.remaining_other_quantity',
                'wi.is_status',
                'wi.enter_request_id',
                'er.id as existing_enter_request_id',
                DB::raw('COALESCE(reserved.quantity, 0) as reserved_quantity'),
                DB::raw('COALESCE(reserved.other_quantity, 0) as reserved_other_quantity'),
            ])
            ->orderBy('wi.id');

        if ($ids !== null) {
            $query->whereIn('wi.id', $ids);
        }

        foreach ($query->get() as $item) {
            if ($item->quantity === null || (float) $item->quantity <= 0) {
                $issues[] = $this->issue(
                    'non_positive_warehouse_quantity',
                    'critical',
                    'Received warehouse quantity must be greater than zero.',
                    warehouseItemId: (int) $item->id,
                    actual: $item->quantity,
                );
            }
            if ($item->other_quantity !== null && (float) $item->other_quantity < 0) {
                $issues[] = $this->issue(
                    'negative_warehouse_other_quantity',
                    'critical',
                    'Received secondary-unit quantity cannot be negative.',
                    warehouseItemId: (int) $item->id,
                    actual: $item->other_quantity,
                );
            }

            $expected = $this->normalise((float) $item->quantity - (float) $item->reserved_quantity);
            $actual = $item->remaining_quantity === null ? null : $this->normalise((float) $item->remaining_quantity);

            if ($actual === null || ! $this->sameNumber($expected, $actual)) {
                $issues[] = $this->issue(
                    'balance_mismatch',
                    'error',
                    'Stored remaining quantity does not match the movement ledger.',
                    warehouseItemId: (int) $item->id,
                    expected: $expected,
                    actual: $actual,
                    context: [
                        'received_quantity' => $this->normalise((float) $item->quantity),
                        'reserved_quantity' => $this->normalise((float) $item->reserved_quantity),
                    ],
                );
            }

            if ($expected < -self::EPSILON) {
                $issues[] = $this->issue(
                    'over_allocation',
                    'critical',
                    'Reserved movement quantity exceeds received stock.',
                    warehouseItemId: (int) $item->id,
                    expected: $this->normalise((float) $item->quantity),
                    actual: $this->normalise((float) $item->reserved_quantity),
                );
            }

            if ($item->other_quantity !== null) {
                $expectedOther = $this->normalise(
                    (float) $item->other_quantity - (float) $item->reserved_other_quantity
                );
                $actualOther = $item->remaining_other_quantity === null
                    ? null
                    : $this->normalise((float) $item->remaining_other_quantity);

                if ($actualOther === null || ! $this->sameNumber($expectedOther, $actualOther)) {
                    $issues[] = $this->issue(
                        'other_balance_mismatch',
                        'error',
                        'Stored secondary-unit balance does not match the movement ledger.',
                        warehouseItemId: (int) $item->id,
                        expected: $expectedOther,
                        actual: $actualOther,
                        context: [
                            'received_other_quantity' => $this->normalise((float) $item->other_quantity),
                            'reserved_other_quantity' => $this->normalise((float) $item->reserved_other_quantity),
                        ],
                    );
                }

                if ($expectedOther < -self::EPSILON) {
                    $issues[] = $this->issue(
                        'other_over_allocation',
                        'critical',
                        'Reserved secondary-unit quantity exceeds received stock.',
                        warehouseItemId: (int) $item->id,
                        expected: $this->normalise((float) $item->other_quantity),
                        actual: $this->normalise((float) $item->reserved_other_quantity),
                    );
                }
            }

            $expectedStatus = $expected > self::EPSILON;
            if ((bool) $item->is_status !== $expectedStatus) {
                $issues[] = $this->issue(
                    'availability_status_mismatch',
                    'warning',
                    'Warehouse availability flag does not match the calculated balance.',
                    warehouseItemId: (int) $item->id,
                    expected: $expectedStatus,
                    actual: (bool) $item->is_status,
                );
            }

            if ($item->enter_request_id === null || $item->existing_enter_request_id === null) {
                $issues[] = $this->issue(
                    'warehouse_item_missing_enter_request',
                    'error',
                    'Warehouse stock row is not linked to an existing inbound declaration.',
                    warehouseItemId: (int) $item->id,
                    actual: $item->enter_request_id,
                );
            }
        }
    }

    /** @param list<array<string, mixed>> $issues */
    private function auditLedgerReferences(array &$issues, ?array $ids): void
    {
        $query = DB::table('outbound_warehouse_items as movement')
            ->leftJoin('warehouse_items as wi', 'wi.id', '=', 'movement.warehouse_item_id')
            ->leftJoin('outbounds as outbound', 'outbound.id', '=', 'movement.outbound_id')
            ->leftJoin('outbound_cars as car', 'car.id', '=', 'movement.outbound_car_id')
            ->select([
                'movement.id',
                'movement.quantity',
                'movement.other_quantity',
                'movement.warehouse_item_id',
                'movement.outbound_id',
                'movement.outbound_car_id',
                'wi.id as existing_warehouse_item_id',
                'wi.enter_request_id as warehouse_enter_request_id',
                'outbound.id as existing_outbound_id',
                'outbound.enter_request_id as outbound_enter_request_id',
                'car.id as existing_car_id',
                'car.outbound_id as car_outbound_id',
            ])
            ->orderBy('movement.id');

        if ($ids !== null) {
            $query->whereIn('movement.warehouse_item_id', $ids);
        }

        foreach ($query->get() as $movement) {
            $base = [
                'ledgerId' => (int) $movement->id,
                'warehouseItemId' => $movement->warehouse_item_id === null ? null : (int) $movement->warehouse_item_id,
                'outboundId' => $movement->outbound_id === null ? null : (int) $movement->outbound_id,
                'outboundCarId' => $movement->outbound_car_id === null ? null : (int) $movement->outbound_car_id,
            ];

            if ($movement->existing_warehouse_item_id === null) {
                $issues[] = $this->issue(
                    'ledger_missing_warehouse_item',
                    'critical',
                    'Movement is not linked to an existing warehouse item.',
                    ...$base,
                );
            }

            if ($movement->existing_outbound_id === null) {
                $issues[] = $this->issue(
                    'ledger_missing_outbound',
                    'critical',
                    'Movement is not linked to an existing outbound declaration.',
                    ...$base,
                );
            }

            if ($movement->existing_car_id === null) {
                $issues[] = $this->issue(
                    'ledger_missing_car',
                    'critical',
                    'Movement is not linked to an existing outbound vehicle.',
                    ...$base,
                );
            } elseif ($movement->existing_outbound_id !== null
                && (int) $movement->car_outbound_id !== (int) $movement->outbound_id) {
                $issues[] = $this->issue(
                    'ledger_car_outbound_mismatch',
                    'critical',
                    'Movement vehicle belongs to a different outbound declaration.',
                    ...$base,
                    expected: (int) $movement->outbound_id,
                    actual: (int) $movement->car_outbound_id,
                );
            }

            if ($movement->existing_warehouse_item_id !== null
                && $movement->existing_outbound_id !== null
                && (int) $movement->warehouse_enter_request_id !== (int) $movement->outbound_enter_request_id) {
                $issues[] = $this->issue(
                    'ledger_enter_request_mismatch',
                    'critical',
                    'Movement stock and outbound declaration belong to different inbound declarations.',
                    ...$base,
                    expected: $movement->warehouse_enter_request_id,
                    actual: $movement->outbound_enter_request_id,
                );
            }

            if ($movement->quantity === null || (float) $movement->quantity <= 0) {
                $issues[] = $this->issue(
                    'non_positive_ledger_quantity',
                    'critical',
                    'Movement quantity must be greater than zero.',
                    ...$base,
                    actual: $movement->quantity,
                );
            }

            if ($movement->other_quantity !== null && (float) $movement->other_quantity < 0) {
                $issues[] = $this->issue(
                    'negative_ledger_other_quantity',
                    'critical',
                    'Movement secondary quantity cannot be negative.',
                    ...$base,
                    actual: $movement->other_quantity,
                );
            }
        }
    }

    /** @param list<array<string, mixed>> $issues */
    private function auditDuplicateAssignments(array &$issues, ?array $ids): void
    {
        $query = DB::table('outbound_warehouse_items')
            ->selectRaw(
                'warehouse_item_id, outbound_car_id, COUNT(*) as movement_count, '
                .'GROUP_CONCAT(id) as movement_ids'
            )
            ->whereNotNull('warehouse_item_id')
            ->whereNotNull('outbound_car_id')
            ->groupBy('warehouse_item_id', 'outbound_car_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('warehouse_item_id')
            ->orderBy('outbound_car_id');

        if ($ids !== null) {
            $query->whereIn('warehouse_item_id', $ids);
        }

        foreach ($query->get() as $duplicate) {
            $issues[] = $this->issue(
                'duplicate_ledger_assignment',
                'critical',
                'More than one movement uses the same warehouse-item and vehicle pair.',
                warehouseItemId: (int) $duplicate->warehouse_item_id,
                outboundCarId: (int) $duplicate->outbound_car_id,
                actual: (int) $duplicate->movement_count,
                context: [
                    'movement_ids' => array_map('intval', explode(',', (string) $duplicate->movement_ids)),
                ],
            );
        }
    }

    /** @param list<array<string, mixed>> $issues */
    private function auditOutboundTotals(array &$issues, ?array $ids): void
    {
        $query = DB::table('outbounds as outbound')
            ->leftJoin('outbound_warehouse_items as movement', 'movement.outbound_id', '=', 'outbound.id')
            ->whereIn('outbound.status_id', InventoryBalanceCalculator::RESERVING_STATUSES)
            ->selectRaw(
                'outbound.id, outbound.quantity_packages, '
                .'COALESCE(SUM(movement.quantity), 0) as movement_quantity'
            )
            ->groupBy('outbound.id', 'outbound.quantity_packages')
            ->orderBy('outbound.id');

        if ($ids !== null) {
            // Scope which outbounds are audited without limiting the rows used
            // by SUM; an outbound can legitimately contain several stock rows.
            $query->whereExists(function ($scope) use ($ids): void {
                $scope->selectRaw('1')
                    ->from('outbound_warehouse_items as scoped_movement')
                    ->whereColumn('scoped_movement.outbound_id', 'outbound.id')
                    ->whereIn('scoped_movement.warehouse_item_id', $ids);
            });
        }

        foreach ($query->get() as $outbound) {
            if ($outbound->quantity_packages === null) {
                continue;
            }

            $expected = $this->normalise((float) $outbound->quantity_packages);
            $actual = $this->normalise((float) $outbound->movement_quantity);

            if (! $this->sameNumber($expected, $actual)) {
                $issues[] = $this->issue(
                    'outbound_total_mismatch',
                    'error',
                    'Outbound package total does not match its movement lines.',
                    outboundId: (int) $outbound->id,
                    expected: $expected,
                    actual: $actual,
                );
            }
        }
    }

    /** @param list<array<string, mixed>> $issues */
    private function auditOutboundStatuses(array &$issues, ?array $ids): void
    {
        $query = DB::table('outbounds as outbound')
            ->leftJoin('outbound_statuses as status', 'status.id', '=', 'outbound.status_id')
            ->where(function ($query): void {
                $query->whereNull('outbound.status_id')->orWhereNull('status.id');
            })
            ->select(['outbound.id', 'outbound.status_id'])
            ->orderBy('outbound.id');

        if ($ids !== null) {
            $query->whereExists(function ($subquery) use ($ids): void {
                $subquery->selectRaw('1')
                    ->from('outbound_warehouse_items as scoped_movement')
                    ->whereColumn('scoped_movement.outbound_id', 'outbound.id')
                    ->whereIn('scoped_movement.warehouse_item_id', $ids);
            });
        }

        foreach ($query->get() as $outbound) {
            $issues[] = $this->issue(
                'invalid_outbound_status',
                'critical',
                'Outbound status does not reference outbound_statuses.',
                outboundId: (int) $outbound->id,
                actual: $outbound->status_id,
            );
        }
    }

    /** @return array<string, mixed> */
    private function issue(
        string $code,
        string $severity,
        string $message,
        ?int $warehouseItemId = null,
        ?int $ledgerId = null,
        ?int $outboundId = null,
        ?int $outboundCarId = null,
        mixed $expected = null,
        mixed $actual = null,
        array $context = [],
    ): array {
        return [
            'code' => $code,
            'severity' => $severity,
            'message' => $message,
            'warehouse_item_id' => $warehouseItemId,
            'ledger_id' => $ledgerId,
            'outbound_id' => $outboundId,
            'outbound_car_id' => $outboundCarId,
            'expected' => $expected,
            'actual' => $actual,
            'context' => $context,
        ];
    }

    private function sameNumber(float $left, float $right): bool
    {
        return abs($left - $right) < self::EPSILON;
    }

    private function normalise(float $number): float
    {
        $rounded = round($number, 3);

        return abs($rounded) < self::EPSILON ? 0.0 : $rounded;
    }
}
