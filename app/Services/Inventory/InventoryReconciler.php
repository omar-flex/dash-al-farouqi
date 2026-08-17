<?php

namespace App\Services\Inventory;

use App\Models\InventoryReconciliationAudit;
use App\Models\Outbound;
use App\Models\OutboundCar;
use App\Models\OutboundWarehouseItems;
use App\Models\WarehouseItems;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JsonException;

class InventoryReconciler
{
    private const SCHEMA_VERSION = 1;

    private const EPSILON = 0.0005;

    private const ACTIONS = ['create', 'update', 'relink', 'split'];

    private const WAREHOUSE_EXPECTED_FIELDS = [
        'id',
        'quantity',
        'remaining_quantity',
        'other_quantity',
        'remaining_other_quantity',
    ];

    private const OUTBOUND_EXPECTED_FIELDS = [
        'id',
        'quantity_packages',
        'status_id',
        'enter_request_id',
    ];

    private const CAR_EXPECTED_FIELDS = ['id', 'outbound_id'];

    private const LEDGER_EXPECTED_FIELDS = [
        'id',
        'warehouse_item_id',
        'outbound_id',
        'outbound_car_id',
        'quantity',
        'other_quantity',
        'location',
        'custom_value',
        'gross_weight',
        'net_weight',
        'cpm',
        'cpm_capacity',
        'is_status',
    ];

    private const LEDGER_AFTER_FIELDS = [
        'warehouse_item_id',
        'outbound_id',
        'outbound_car_id',
        'quantity',
        'other_quantity',
    ];

    private const PROPORTIONAL_FIELDS = [
        'custom_value',
        'gross_weight',
        'net_weight',
        'cpm',
        'cpm_capacity',
    ];

    public function __construct(
        private readonly InventoryBalanceCalculator $balanceCalculator,
    ) {}

    /**
     * Validate and preview a reconciliation without writing or locking rows.
     *
     * @return array<string, mixed>
     */
    public function preview(string $path, ?string $actor = null): array
    {
        [$document, $sourceHash, $sourceFile] = $this->loadDocument($path);
        $context = $this->prepare($document, $sourceHash, false);

        return $this->result(
            $document,
            $sourceHash,
            $sourceFile,
            $context,
            false,
            $actor,
        );
    }

    /**
     * Apply the entire reconciliation atomically. Every expected value is
     * checked again while rows are locked, so a stale plan never partially
     * writes inventory data.
     *
     * @return array<string, mixed>
     */
    public function apply(string $path, ?string $actor = null): array
    {
        [$document, $sourceHash, $sourceFile] = $this->loadDocument($path);

        return DB::transaction(function () use ($document, $sourceHash, $sourceFile, $actor): array {
            $context = $this->prepare($document, $sourceHash, true);

            if ($context['idempotent']) {
                return $this->result(
                    $document,
                    $sourceHash,
                    $sourceFile,
                    $context,
                    true,
                    $actor,
                );
            }

            $runId = (string) Str::uuid();
            $resultingIds = [];

            foreach ($document['operations'] as $operation) {
                $resultingIds[$operation['operation_id']] = $this->applyOperation($operation, $context);
            }

            foreach ($document['outbound_updates'] as $update) {
                /** @var Outbound $outbound */
                $outbound = $context['outbounds']->get((int) $update['id']);
                $outbound->forceFill([
                    'quantity_packages' => $this->number($update['quantity_packages']),
                ])->save();
            }

            $this->balanceCalculator->recalculateMany($context['warehouse_item_ids']);
            $this->assertFinalInvariants($document, $context, $resultingIds);

            foreach ($document['operations'] as $operation) {
                $operationId = $operation['operation_id'];
                $ledgerIds = $resultingIds[$operationId];

                InventoryReconciliationAudit::query()->create([
                    'run_id' => $runId,
                    'idempotency_key' => $this->idempotencyKey($document, $operation),
                    'source_hash' => $sourceHash,
                    'source_file' => $sourceFile,
                    'reference' => $document['reference'],
                    'operation_id' => $operationId,
                    'action' => $operation['action'],
                    'actor' => $actor,
                    'source_ledger_id' => Arr::get($operation, 'expected.ledger.id'),
                    'resulting_ledger_ids' => $ledgerIds,
                    'affected_warehouse_item_ids' => $this->operationWarehouseItemIds($operation),
                    'reason' => $operation['reason'],
                    'before' => $context['before'][$operationId],
                    'after' => $this->operationSnapshot($operation, $ledgerIds),
                    'metadata' => [
                        'approved_by' => $document['approved_by'],
                        'schema_version' => self::SCHEMA_VERSION,
                    ],
                ]);
            }

            $context['run_id'] = $runId;
            $context['resulting_ledger_ids'] = $resultingIds;

            return $this->result(
                $document,
                $sourceHash,
                $sourceFile,
                $context,
                true,
                $actor,
            );
        }, 5);
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: string}
     */
    private function loadDocument(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InventoryReconciliationException('The reconciliation JSON file is not readable.');
        }

        if (filesize($path) > 2 * 1024 * 1024) {
            throw new InventoryReconciliationException('The reconciliation JSON file exceeds the 2 MB safety limit.');
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new InventoryReconciliationException('The reconciliation JSON file could not be read.');
        }

        try {
            $document = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InventoryReconciliationException(
                'The reconciliation file is not valid JSON: '.$exception->getMessage(),
            );
        }

        if (! is_array($document)) {
            throw new InventoryReconciliationException('The reconciliation document must be a JSON object.');
        }

        $this->validateDocument($document);

        $realPath = realpath($path);

        return [$document, hash('sha256', $contents), $realPath === false ? $path : $realPath];
    }

    /** @param array<string, mixed> $document */
    private function validateDocument(array &$document): void
    {
        $document['outbound_updates'] ??= [];

        $validator = Validator::make($document, [
            'schema_version' => ['required', 'integer', 'in:'.self::SCHEMA_VERSION],
            'reference' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._:\/-]+$/'],
            'approved_by' => ['required', 'string', 'max:255'],
            'operations' => ['required', 'array', 'min:1'],
            'operations.*.operation_id' => ['required', 'string', 'max:255', 'distinct', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'operations.*.action' => ['required', 'string', 'in:'.implode(',', self::ACTIONS)],
            'operations.*.reason' => ['required', 'string', 'min:5', 'max:2000'],
            'operations.*.expected' => ['required', 'array'],
            'operations.*.expected.warehouse_items' => ['required', 'array', 'min:1'],
            'operations.*.expected.outbounds' => ['required', 'array', 'min:1'],
            'operations.*.expected.outbound_cars' => ['required', 'array', 'min:1'],
            'operations.*.after' => ['required', 'array'],
            'outbound_updates' => ['array'],
            'outbound_updates.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'outbound_updates.*.expected_quantity_packages' => ['present', 'nullable', 'numeric'],
            'outbound_updates.*.quantity_packages' => ['required', 'numeric', 'gt:0'],
        ]);

        if ($validator->fails()) {
            throw new InventoryReconciliationException(
                'The reconciliation document does not match schema version 1.',
                $validator->errors()->all(),
            );
        }

        $errors = [];
        $sourceLedgerIds = [];
        foreach ($document['operations'] as $index => $operation) {
            $prefix = "operations.$index";
            $this->validateExpectedRows(
                Arr::get($operation, 'expected.warehouse_items', []),
                self::WAREHOUSE_EXPECTED_FIELDS,
                "$prefix.expected.warehouse_items",
                $errors,
            );
            $this->validateExpectedRows(
                Arr::get($operation, 'expected.outbounds', []),
                self::OUTBOUND_EXPECTED_FIELDS,
                "$prefix.expected.outbounds",
                $errors,
            );
            $this->validateExpectedRows(
                Arr::get($operation, 'expected.outbound_cars', []),
                self::CAR_EXPECTED_FIELDS,
                "$prefix.expected.outbound_cars",
                $errors,
            );

            $action = $operation['action'];
            $expectedHasLedger = array_key_exists('ledger', $operation['expected']);
            if (! $expectedHasLedger) {
                $errors[] = "$prefix.expected.ledger must be present (null for create, an object otherwise).";
            } elseif ($action === 'create' && $operation['expected']['ledger'] !== null) {
                $errors[] = "$prefix.expected.ledger must be null for create.";
            } elseif ($action !== 'create') {
                if (! is_array($operation['expected']['ledger'])) {
                    $errors[] = "$prefix.expected.ledger must be an object for $action.";
                } else {
                    $this->validateRequiredFields(
                        $operation['expected']['ledger'],
                        self::LEDGER_EXPECTED_FIELDS,
                        "$prefix.expected.ledger",
                        $errors,
                    );
                    if (array_key_exists('id', $operation['expected']['ledger'])) {
                        $sourceLedgerIds[] = (int) $operation['expected']['ledger']['id'];
                    }
                }
            }

            if ($action === 'split') {
                $source = Arr::get($operation, 'after.source_ledger');
                if (! is_array($source)) {
                    $errors[] = "$prefix.after.source_ledger must be an object for split.";
                } else {
                    $this->validateRequiredFields($source, self::LEDGER_AFTER_FIELDS, "$prefix.after.source_ledger", $errors);
                }
            }

            $target = Arr::get($operation, 'after.ledger');
            if (! is_array($target)) {
                $errors[] = "$prefix.after.ledger must be an object.";
            } else {
                $this->validateRequiredFields($target, self::LEDGER_AFTER_FIELDS, "$prefix.after.ledger", $errors);
            }
        }

        if (count($sourceLedgerIds) !== count(array_unique($sourceLedgerIds))) {
            $errors[] = 'Each existing ledger row may be the source of at most one operation per file.';
        }

        $documentedOutboundIds = collect($document['operations'])
            ->flatMap(fn (array $operation): array => array_column(
                $operation['expected']['outbounds'],
                'id',
            ))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->all();
        foreach ($document['outbound_updates'] as $index => $update) {
            if (! in_array((int) $update['id'], $documentedOutboundIds, true)) {
                $errors[] = "outbound_updates.$index.id must have a complete expected.outbounds snapshot in an operation.";
            }
        }

        if ($errors !== []) {
            throw new InventoryReconciliationException(
                'The reconciliation document is missing required expected/after values.',
                $errors,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function prepare(array $document, string $sourceHash, bool $lock): array
    {
        if (! Schema::hasTable('inventory_reconciliation_audits')) {
            throw new InventoryReconciliationException(
                'Run database migrations before using inventory:reconcile; the immutable audit table is missing.',
            );
        }

        $operationKeys = collect($document['operations'])
            ->map(fn (array $operation): string => $this->idempotencyKey($document, $operation))
            ->all();

        $auditQuery = InventoryReconciliationAudit::query()
            ->whereIn('idempotency_key', $operationKeys)
            ->orderBy('id');
        if ($lock) {
            $auditQuery->lockForUpdate();
        }
        $existingAudits = $auditQuery->get()->keyBy('idempotency_key');

        if ($existingAudits->isNotEmpty()) {
            $conflicting = $existingAudits->first(fn ($audit): bool => $audit->source_hash !== $sourceHash);
            if ($conflicting !== null) {
                throw new InventoryReconciliationException(sprintf(
                    'Operation %s was already applied from a different file hash; use a new reference/operation_id after review.',
                    $conflicting->operation_id,
                ));
            }

            if ($existingAudits->count() !== count($operationKeys)) {
                throw new InventoryReconciliationException(
                    'Only part of this operation set already exists in the audit log; refusing a partial replay.',
                );
            }

            return [
                'idempotent' => true,
                'existing_audits' => $existingAudits,
                'warehouse_item_ids' => [],
                'outbound_ids' => [],
                'car_ids' => [],
                'ledger_ids' => [],
                'before' => [],
            ];
        }

        $ids = $this->collectIds($document);
        // Match the live outbound write path's lock order to minimise deadlock
        // risk when a reconciliation overlaps ordinary application traffic.
        $outbounds = $this->fetchModels(Outbound::query(), $ids['outbound_ids'], $lock);
        $warehouseItems = $this->fetchModels(WarehouseItems::query(), $ids['warehouse_item_ids'], $lock);
        if ($lock) {
            $relatedLedgers = OutboundWarehouseItems::query()
                ->where(function ($query) use ($ids): void {
                    $query->whereIn('warehouse_item_id', $ids['warehouse_item_ids'])
                        ->orWhereIn('outbound_id', $ids['outbound_ids'])
                        ->orWhereIn('id', $ids['ledger_ids']);
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $ledgers = $relatedLedgers->only($ids['ledger_ids'])->keyBy('id');
        } else {
            $ledgers = $this->fetchModels(OutboundWarehouseItems::query(), $ids['ledger_ids'], false);
        }
        $cars = $this->fetchModels(OutboundCar::query(), $ids['car_ids'], $lock);

        $context = [
            'idempotent' => false,
            'warehouse_item_ids' => $this->uniqueIds($warehouseItems->keys()->all()),
            'outbound_ids' => $this->uniqueIds($outbounds->keys()->all()),
            'car_ids' => $this->uniqueIds($cars->keys()->all()),
            'ledger_ids' => $ids['ledger_ids'],
            'warehouse_items' => $warehouseItems,
            'outbounds' => $outbounds,
            'cars' => $cars,
            'ledgers' => $ledgers,
            'before' => [],
        ];

        $errors = [];
        foreach ($document['operations'] as $operation) {
            $this->assertExpectedState($operation, $context, $errors);
            $this->assertOperationRules($operation, $context, $errors);
            $context['before'][$operation['operation_id']] = $this->expectedSnapshot($operation, $context);
        }

        foreach ($document['outbound_updates'] as $index => $update) {
            /** @var Outbound|null $outbound */
            $outbound = $outbounds->get((int) $update['id']);
            if ($outbound === null) {
                $errors[] = "outbound_updates.$index.id does not reference an existing outbound.";

                continue;
            }

            if (! $this->sameValue($update['expected_quantity_packages'], $outbound->quantity_packages)) {
                $errors[] = "outbound_updates.$index.expected_quantity_packages is stale.";
            }
        }

        if ($errors !== []) {
            throw new InventoryReconciliationException(
                'The reconciliation plan is stale or violates inventory rules.',
                $errors,
            );
        }

        $this->assertProjectedInvariants($document, $context);

        return $context;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, mixed>  $context
     * @param  list<string>  $errors
     */
    private function assertExpectedState(array $operation, array $context, array &$errors): void
    {
        $operationId = $operation['operation_id'];

        foreach ($operation['expected']['warehouse_items'] as $expected) {
            $this->compareExpectedModel(
                $expected,
                $context['warehouse_items']->get((int) $expected['id']),
                self::WAREHOUSE_EXPECTED_FIELDS,
                "$operationId.expected.warehouse_items[{$expected['id']}]",
                $errors,
            );
        }

        foreach ($operation['expected']['outbounds'] as $expected) {
            $this->compareExpectedModel(
                $expected,
                $context['outbounds']->get((int) $expected['id']),
                self::OUTBOUND_EXPECTED_FIELDS,
                "$operationId.expected.outbounds[{$expected['id']}]",
                $errors,
            );
        }

        foreach ($operation['expected']['outbound_cars'] as $expected) {
            $this->compareExpectedModel(
                $expected,
                $context['cars']->get((int) $expected['id']),
                self::CAR_EXPECTED_FIELDS,
                "$operationId.expected.outbound_cars[{$expected['id']}]",
                $errors,
            );
        }

        if ($operation['action'] !== 'create') {
            $expected = $operation['expected']['ledger'];
            $this->compareExpectedModel(
                $expected,
                $context['ledgers']->get((int) $expected['id']),
                self::LEDGER_EXPECTED_FIELDS,
                "$operationId.expected.ledger",
                $errors,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, mixed>  $context
     * @param  list<string>  $errors
     */
    private function assertOperationRules(array $operation, array $context, array &$errors): void
    {
        $operationId = $operation['operation_id'];
        $action = $operation['action'];
        $target = $operation['after']['ledger'];
        $this->assertMovementTarget($target, $operation, $context, "$operationId.after.ledger", $errors);

        if ($action === 'create') {
            return;
        }

        $expected = $operation['expected']['ledger'];
        $this->assertSourceReferencesDocumented($expected, $operation, $context, $errors);
        if ($action === 'update') {
            foreach (['warehouse_item_id', 'outbound_id', 'outbound_car_id'] as $field) {
                if ((int) $target[$field] !== (int) $expected[$field]) {
                    $errors[] = "$operationId update cannot change $field; use relink.";
                }
            }
        }

        if ($action === 'relink') {
            foreach (['quantity', 'other_quantity'] as $field) {
                if (! $this->sameValue($target[$field], $expected[$field])) {
                    $errors[] = "$operationId relink cannot change $field; use update or split.";
                }
            }
        }

        if ($action === 'split') {
            $source = $operation['after']['source_ledger'];
            $this->assertMovementTarget($source, $operation, $context, "$operationId.after.source_ledger", $errors);

            if ((int) $source['warehouse_item_id'] !== (int) $expected['warehouse_item_id']
                || (int) $source['outbound_id'] !== (int) $expected['outbound_id']
                || (int) $source['outbound_car_id'] !== (int) $expected['outbound_car_id']) {
                $errors[] = "$operationId split must keep the source ledger relationships unchanged.";
            }

            if (! $this->sameValue(
                $expected['quantity'],
                $this->number($source['quantity']) + $this->number($target['quantity']),
            )) {
                $errors[] = "$operationId split quantities do not conserve the source movement quantity.";
            }

            $expectedOther = $expected['other_quantity'];
            $sourceOther = $source['other_quantity'];
            $targetOther = $target['other_quantity'];
            if ($expectedOther === null) {
                if ($sourceOther !== null || $targetOther !== null) {
                    $errors[] = "$operationId split must keep secondary quantities null.";
                }
            } elseif ($sourceOther === null || $targetOther === null) {
                $errors[] = "$operationId split must explicitly allocate both secondary quantities.";
            } elseif (! $this->sameValue(
                $expectedOther,
                $this->number($sourceOther) + $this->number($targetOther),
            )) {
                $errors[] = "$operationId split secondary quantities do not conserve the source movement quantity.";
            }
        }
    }

    /**
     * Existing, valid source references must have signed snapshots. A missing
     * source reference is tolerated only by relink, whose purpose includes
     * repairing orphan ledger rows.
     *
     * @param  array<string, mixed>  $expectedLedger
     * @param  array<string, mixed>  $operation
     * @param  array<string, mixed>  $context
     * @param  list<string>  $errors
     */
    private function assertSourceReferencesDocumented(
        array $expectedLedger,
        array $operation,
        array $context,
        array &$errors,
    ): void {
        $operationId = $operation['operation_id'];
        $references = [
            'warehouse_item_id' => [
                'models' => $context['warehouse_items'],
                'expected' => array_map('intval', array_column($operation['expected']['warehouse_items'], 'id')),
                'label' => 'expected.warehouse_items',
            ],
            'outbound_id' => [
                'models' => $context['outbounds'],
                'expected' => array_map('intval', array_column($operation['expected']['outbounds'], 'id')),
                'label' => 'expected.outbounds',
            ],
            'outbound_car_id' => [
                'models' => $context['cars'],
                'expected' => array_map('intval', array_column($operation['expected']['outbound_cars'], 'id')),
                'label' => 'expected.outbound_cars',
            ],
        ];

        foreach ($references as $field => $reference) {
            $id = $expectedLedger[$field];
            if ($id === null) {
                if ($operation['action'] !== 'relink') {
                    $errors[] = "$operationId expected source $field is null; only relink can repair an orphan source.";
                }

                continue;
            }

            $id = (int) $id;
            if (! $reference['models']->has($id)) {
                if ($operation['action'] !== 'relink') {
                    $errors[] = "$operationId expected source $field=$id no longer exists; only relink can repair it.";
                }

                continue;
            }

            if (! in_array($id, $reference['expected'], true)) {
                $errors[] = "$operationId source $field must have a complete {$reference['label']} snapshot.";
            }
        }
    }

    /**
     * @param  array<string, mixed>  $target
     * @param  array<string, mixed>  $operation
     * @param  array<string, mixed>  $context
     * @param  list<string>  $errors
     */
    private function assertMovementTarget(
        array $target,
        array $operation,
        array $context,
        string $path,
        array &$errors,
    ): void {
        if (! is_numeric($target['quantity']) || $this->number($target['quantity']) <= 0) {
            $errors[] = "$path.quantity must be greater than zero.";
        }
        foreach (['warehouse_item_id', 'outbound_id', 'outbound_car_id'] as $field) {
            if (filter_var($target[$field], FILTER_VALIDATE_INT) === false || (int) $target[$field] < 1) {
                $errors[] = "$path.$field must be a positive integer.";
            }
        }
        if ($target['other_quantity'] !== null
            && (! is_numeric($target['other_quantity']) || $this->number($target['other_quantity']) < 0)) {
            $errors[] = "$path.other_quantity must be null or non-negative.";
        }
        foreach (self::PROPORTIONAL_FIELDS as $field) {
            if (array_key_exists($field, $target)
                && $target[$field] !== null
                && ! is_numeric($target[$field])) {
                $errors[] = "$path.$field must be null or numeric.";
            }
        }
        if (array_key_exists('location', $target)
            && $target['location'] !== null
            && ! is_string($target['location'])) {
            $errors[] = "$path.location must be null or a string.";
        }

        /** @var WarehouseItems|null $warehouseItem */
        $warehouseItem = $context['warehouse_items']->get((int) $target['warehouse_item_id']);
        /** @var Outbound|null $outbound */
        $outbound = $context['outbounds']->get((int) $target['outbound_id']);
        /** @var OutboundCar|null $car */
        $car = $context['cars']->get((int) $target['outbound_car_id']);

        $expectedWarehouseItemIds = array_map('intval', array_column(
            $operation['expected']['warehouse_items'],
            'id',
        ));
        $expectedOutboundIds = array_map('intval', array_column(
            $operation['expected']['outbounds'],
            'id',
        ));
        $expectedCarIds = array_map('intval', array_column(
            $operation['expected']['outbound_cars'],
            'id',
        ));

        if (! in_array((int) $target['warehouse_item_id'], $expectedWarehouseItemIds, true)) {
            $errors[] = "$path.warehouse_item_id must have a complete expected.warehouse_items snapshot.";
        }
        if (! in_array((int) $target['outbound_id'], $expectedOutboundIds, true)) {
            $errors[] = "$path.outbound_id must have a complete expected.outbounds snapshot.";
        }
        if (! in_array((int) $target['outbound_car_id'], $expectedCarIds, true)) {
            $errors[] = "$path.outbound_car_id must have a complete expected.outbound_cars snapshot.";
        }

        if ($warehouseItem === null) {
            $errors[] = "$path.warehouse_item_id does not exist or is missing from expected.warehouse_items.";
        }
        if ($outbound === null) {
            $errors[] = "$path.outbound_id does not exist or is missing from expected.outbounds.";
        }
        if ($car === null) {
            $errors[] = "$path.outbound_car_id does not exist or is missing from expected.outbound_cars.";
        }

        if ($car !== null && $outbound !== null && (int) $car->outbound_id !== (int) $outbound->id) {
            $errors[] = "$path vehicle does not belong to its outbound declaration.";
        }
        if ($warehouseItem !== null && $outbound !== null
            && (int) $warehouseItem->enter_request_id !== (int) $outbound->enter_request_id) {
            $errors[] = "$path warehouse item and outbound belong to different inbound declarations.";
        }
        if ($outbound !== null
            && ! in_array((int) $outbound->status_id, InventoryBalanceCalculator::RESERVING_STATUSES, true)) {
            $errors[] = "$path outbound is not in a stock-reserving workflow state.";
        }
    }

    /**
     * Simulate only the relevant ledger rows in memory. This keeps dry-run
     * genuinely read-only while enforcing final balance, uniqueness, and
     * outbound-total invariants.
     *
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $context
     */
    private function assertProjectedInvariants(array $document, array $context): void
    {
        $movements = DB::table('outbound_warehouse_items as movement')
            ->leftJoin('outbounds as movement_outbound', 'movement_outbound.id', '=', 'movement.outbound_id')
            ->where(function ($query) use ($context): void {
                $query->whereIn('movement.warehouse_item_id', $context['warehouse_item_ids'])
                    ->orWhereIn('movement.outbound_id', $context['outbound_ids'])
                    ->orWhereIn('movement.id', $context['ledger_ids']);
            })
            ->get([
                'movement.id',
                'movement.warehouse_item_id',
                'movement.outbound_id',
                'movement.outbound_car_id',
                'movement.quantity',
                'movement.other_quantity',
                'movement_outbound.status_id as _status_id',
            ])
            ->mapWithKeys(fn ($row): array => [(string) $row->id => (array) $row])
            ->all();

        foreach ($document['operations'] as $operation) {
            $sourceId = Arr::get($operation, 'expected.ledger.id');
            $target = $operation['after']['ledger'];
            $target['_status_id'] = $context['outbounds']->get((int) $target['outbound_id'])?->status_id;

            if ($operation['action'] === 'create') {
                $movements['new:'.$operation['operation_id']] = $target;
            } elseif ($operation['action'] === 'split') {
                $source = $operation['after']['source_ledger'];
                $source['_status_id'] = $context['outbounds']->get((int) $source['outbound_id'])?->status_id;
                $movements[(string) $sourceId] = $source;
                $movements['new:'.$operation['operation_id']] = $target;
            } else {
                $movements[(string) $sourceId] = $target;
            }
        }

        $errors = [];
        $touchedPairs = [];
        foreach ($document['operations'] as $operation) {
            $target = $operation['after']['ledger'];
            $touchedPairs[] = $target['warehouse_item_id'].'|'.$target['outbound_car_id'];
            if ($operation['action'] !== 'create') {
                $expected = $operation['expected']['ledger'];
                $touchedPairs[] = $expected['warehouse_item_id'].'|'.$expected['outbound_car_id'];
            }
        }

        $pairCounts = [];
        foreach ($movements as $movement) {
            if ($movement['warehouse_item_id'] === null || $movement['outbound_car_id'] === null) {
                continue;
            }
            $key = $movement['warehouse_item_id'].'|'.$movement['outbound_car_id'];
            $pairCounts[$key] = ($pairCounts[$key] ?? 0) + 1;
        }
        foreach (array_unique($touchedPairs) as $pair) {
            if (($pairCounts[$pair] ?? 0) > 1) {
                $errors[] = "Projected ledger has duplicate warehouse-item/vehicle pair $pair.";
            }
        }

        $reservedTotals = [];
        $outboundTotals = [];
        foreach ($movements as $movement) {
            if (in_array((int) ($movement['_status_id'] ?? 0), InventoryBalanceCalculator::RESERVING_STATUSES, true)) {
                $itemId = (int) $movement['warehouse_item_id'];
                $reservedTotals[$itemId]['quantity'] = ($reservedTotals[$itemId]['quantity'] ?? 0)
                    + $this->number($movement['quantity']);
                $reservedTotals[$itemId]['other_quantity'] = ($reservedTotals[$itemId]['other_quantity'] ?? 0)
                    + $this->number($movement['other_quantity']);
            }

            $outboundId = (int) $movement['outbound_id'];
            $outboundTotals[$outboundId] = ($outboundTotals[$outboundId] ?? 0)
                + $this->number($movement['quantity']);
        }

        foreach ($context['warehouse_items'] as $item) {
            $remaining = $this->number($item->quantity) - ($reservedTotals[$item->id]['quantity'] ?? 0);
            if ($remaining < -self::EPSILON) {
                $errors[] = "Projected movements over-allocate warehouse item {$item->id} by ".abs(round($remaining, 3)).'.';
            }
            if ($item->other_quantity !== null) {
                $remainingOther = $this->number($item->other_quantity)
                    - ($reservedTotals[$item->id]['other_quantity'] ?? 0);
                if ($remainingOther < -self::EPSILON) {
                    $errors[] = "Projected movements over-allocate the secondary quantity of warehouse item {$item->id}.";
                }
            }
        }

        $headerTotals = $context['outbounds']->mapWithKeys(
            fn (Outbound $outbound): array => [$outbound->id => $this->number($outbound->quantity_packages)]
        )->all();
        foreach ($document['outbound_updates'] as $update) {
            $headerTotals[(int) $update['id']] = $this->number($update['quantity_packages']);
        }

        foreach ($context['outbounds'] as $outbound) {
            $lineTotal = $outboundTotals[$outbound->id] ?? 0.0;
            if (! $this->sameValue($headerTotals[$outbound->id], $lineTotal)) {
                $errors[] = "Projected movement total for outbound {$outbound->id} does not match quantity_packages; include an explicit outbound_updates entry if the signed document changes the header.";
            }
        }

        if ($errors !== []) {
            throw new InventoryReconciliationException(
                'The reconciliation would leave invalid inventory state.',
                $errors,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, mixed>  $context
     * @return list<int>
     */
    private function applyOperation(array $operation, array $context): array
    {
        $action = $operation['action'];

        if ($action === 'create') {
            $movement = OutboundWarehouseItems::query()->create(
                $this->movementAttributes($operation['after']['ledger'], $context)
            );

            return [(int) $movement->id];
        }

        /** @var OutboundWarehouseItems $source */
        $source = $context['ledgers']->get((int) $operation['expected']['ledger']['id']);

        if ($action === 'split') {
            $source->forceFill($this->movementAttributes(
                $operation['after']['source_ledger'],
                $context,
                $source,
            ))->save();

            $created = OutboundWarehouseItems::query()->create(
                $this->movementAttributes($operation['after']['ledger'], $context, $source)
            );

            return [(int) $source->id, (int) $created->id];
        }

        $source->forceFill(
            $this->movementAttributes($operation['after']['ledger'], $context, $source)
        )->save();

        return [(int) $source->id];
    }

    /**
     * @param  array<string, mixed>  $specification
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function movementAttributes(
        array $specification,
        array $context,
        ?OutboundWarehouseItems $source = null,
    ): array {
        /** @var WarehouseItems $warehouseItem */
        $warehouseItem = $context['warehouse_items']->get((int) $specification['warehouse_item_id']);
        /** @var Outbound $outbound */
        $outbound = $context['outbounds']->get((int) $specification['outbound_id']);
        $quantity = $this->number($specification['quantity']);

        $attributes = [
            'warehouse_item_id' => (int) $specification['warehouse_item_id'],
            'outbound_id' => (int) $specification['outbound_id'],
            'outbound_car_id' => (int) $specification['outbound_car_id'],
            'quantity' => $quantity,
            'other_quantity' => $specification['other_quantity'] === null
                ? null
                : $this->number($specification['other_quantity']),
            'location' => array_key_exists('location', $specification)
                ? $specification['location']
                : $source?->location,
            'is_status' => in_array(
                (int) $outbound->status_id,
                InventoryBalanceCalculator::OFFICIAL_STATUSES,
                true,
            ),
        ];

        foreach (self::PROPORTIONAL_FIELDS as $field) {
            if (array_key_exists($field, $specification)) {
                $attributes[$field] = $specification[$field] === null
                    ? null
                    : $this->number($specification[$field]);

                continue;
            }

            $base = $warehouseItem->{$field};
            $attributes[$field] = $base === null || $this->number($warehouseItem->quantity) === 0.0
                ? null
                : round(($this->number($base) / $this->number($warehouseItem->quantity)) * $quantity, 3);
        }

        return $attributes;
    }

    /**
     * Validate the real post-write state before the transaction can commit.
     *
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $context
     * @param  array<string, list<int>>  $resultingIds
     */
    private function assertFinalInvariants(array $document, array $context, array $resultingIds): void
    {
        // Reuse the read-only simulator against the transaction's current rows.
        // Replacing operations with their now-current snapshots would be
        // redundant; direct aggregate checks below make commit safety explicit.
        $errors = [];

        $duplicates = DB::table('outbound_warehouse_items')
            ->whereIn('warehouse_item_id', $context['warehouse_item_ids'])
            ->whereIn('outbound_car_id', $context['car_ids'])
            ->selectRaw('warehouse_item_id, outbound_car_id, COUNT(*) as movement_count')
            ->groupBy('warehouse_item_id', 'outbound_car_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        foreach ($duplicates as $duplicate) {
            $errors[] = "Duplicate final ledger assignment {$duplicate->warehouse_item_id}|{$duplicate->outbound_car_id}.";
        }

        $items = WarehouseItems::query()->whereKey($context['warehouse_item_ids'])->get();
        foreach ($items as $item) {
            if ($this->number($item->remaining_quantity) < -self::EPSILON) {
                $errors[] = "Final balance for warehouse item {$item->id} is negative.";
            }
            if ($item->remaining_other_quantity !== null
                && $this->number($item->remaining_other_quantity) < -self::EPSILON) {
                $errors[] = "Final secondary balance for warehouse item {$item->id} is negative.";
            }
        }

        $totals = DB::table('outbound_warehouse_items')
            ->whereIn('outbound_id', $context['outbound_ids'])
            ->selectRaw('outbound_id, COALESCE(SUM(quantity), 0) as quantity')
            ->groupBy('outbound_id')
            ->pluck('quantity', 'outbound_id');
        $outbounds = Outbound::query()->whereKey($context['outbound_ids'])->get();
        foreach ($outbounds as $outbound) {
            if (! $this->sameValue($outbound->quantity_packages, $totals->get($outbound->id, 0))) {
                $errors[] = "Final movement total for outbound {$outbound->id} does not match quantity_packages.";
            }
        }

        if ($errors !== []) {
            throw new InventoryReconciliationException(
                'Post-write verification failed; the reconciliation transaction was rolled back.',
                $errors,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function result(
        array $document,
        string $sourceHash,
        string $sourceFile,
        array $context,
        bool $applied,
        ?string $actor,
    ): array {
        $idempotent = (bool) $context['idempotent'];

        return [
            'mode' => $applied ? 'apply' : 'dry-run',
            'valid' => true,
            'applied' => $applied && ! $idempotent,
            'idempotent' => $idempotent,
            'run_id' => $context['run_id']
                ?? (isset($context['existing_audits']) ? $context['existing_audits']->first()?->run_id : null),
            'reference' => $document['reference'],
            'approved_by' => $document['approved_by'],
            'actor' => $actor,
            'source_file' => $sourceFile,
            'source_hash' => $sourceHash,
            'operation_count' => count($document['operations']),
            'operations' => collect($document['operations'])->map(function (array $operation) use ($context): array {
                $resultingLedgerIds = $context['resulting_ledger_ids'][$operation['operation_id']] ?? null;

                return [
                    'operation_id' => $operation['operation_id'],
                    'action' => $operation['action'],
                    'warehouse_item_ids' => $this->operationWarehouseItemIds($operation),
                    'resulting_ledger_ids' => $resultingLedgerIds,
                ];
            })->all(),
            'message' => $idempotent
                ? 'This exact reconciliation was already applied; no rows were changed.'
                : ($applied
                    ? 'Reconciliation applied atomically and recorded in the audit log.'
                    : 'Dry-run passed. No database rows were changed; --apply will revalidate under locks.'),
        ];
    }

    /** @return array<string, list<int>> */
    private function collectIds(array $document): array
    {
        $warehouseItemIds = [];
        $outboundIds = [];
        $carIds = [];
        $ledgerIds = [];

        foreach ($document['operations'] as $operation) {
            foreach ($operation['expected']['warehouse_items'] as $row) {
                $warehouseItemIds[] = (int) $row['id'];
            }
            foreach ($operation['expected']['outbounds'] as $row) {
                $outboundIds[] = (int) $row['id'];
            }
            foreach ($operation['expected']['outbound_cars'] as $row) {
                $carIds[] = (int) $row['id'];
            }
            if ($operation['expected']['ledger'] !== null) {
                $source = $operation['expected']['ledger'];
                $ledgerIds[] = (int) $source['id'];
                if ($source['warehouse_item_id'] !== null) {
                    $warehouseItemIds[] = (int) $source['warehouse_item_id'];
                }
                if ($source['outbound_id'] !== null) {
                    $outboundIds[] = (int) $source['outbound_id'];
                }
                if ($source['outbound_car_id'] !== null) {
                    $carIds[] = (int) $source['outbound_car_id'];
                }
            }

            foreach (['ledger', 'source_ledger'] as $key) {
                $movement = Arr::get($operation, 'after.'.$key);
                if (! is_array($movement)) {
                    continue;
                }
                $warehouseItemIds[] = (int) $movement['warehouse_item_id'];
                $outboundIds[] = (int) $movement['outbound_id'];
                $carIds[] = (int) $movement['outbound_car_id'];
            }
        }

        foreach ($document['outbound_updates'] as $update) {
            $outboundIds[] = (int) $update['id'];
        }

        return [
            'warehouse_item_ids' => $this->uniqueIds($warehouseItemIds),
            'outbound_ids' => $this->uniqueIds($outboundIds),
            'car_ids' => $this->uniqueIds($carIds),
            'ledger_ids' => $this->uniqueIds($ledgerIds),
        ];
    }

    private function fetchModels($query, array $ids, bool $lock): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $query->whereKey($ids)->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->keyBy('id');
    }

    /** @param list<array<string, mixed>> $rows */
    private function validateExpectedRows(array $rows, array $fields, string $path, array &$errors): void
    {
        $ids = [];
        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                $errors[] = "$path.$index must be an object.";

                continue;
            }
            $this->validateRequiredFields($row, $fields, "$path.$index", $errors);
            if (array_key_exists('id', $row)) {
                if (! is_numeric($row['id']) || (int) $row['id'] < 1) {
                    $errors[] = "$path.$index.id must be a positive integer.";
                }
                $ids[] = (int) $row['id'];
            }
        }
        if (count($ids) !== count(array_unique($ids))) {
            $errors[] = "$path contains duplicate IDs.";
        }
    }

    private function validateRequiredFields(array $row, array $fields, string $path, array &$errors): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $row)) {
                $errors[] = "$path.$field must be present, including when its value is null.";
            }
        }
    }

    private function compareExpectedModel(
        array $expected,
        mixed $model,
        array $fields,
        string $path,
        array &$errors,
    ): void {
        if ($model === null) {
            $errors[] = "$path does not exist.";

            return;
        }

        foreach ($fields as $field) {
            if (! $this->sameValue($expected[$field], $model->{$field})) {
                $errors[] = "$path.$field is stale (expected "
                    .$this->printable($expected[$field]).', current '.$this->printable($model->{$field}).').';
            }
        }
    }

    /** @return array<string, mixed> */
    private function expectedSnapshot(array $operation, array $context): array
    {
        return [
            'warehouse_items' => $this->snapshotExpectedModels(
                $operation['expected']['warehouse_items'],
                $context['warehouse_items'],
                self::WAREHOUSE_EXPECTED_FIELDS,
            ),
            'outbounds' => $this->snapshotExpectedModels(
                $operation['expected']['outbounds'],
                $context['outbounds'],
                self::OUTBOUND_EXPECTED_FIELDS,
            ),
            'outbound_cars' => $this->snapshotExpectedModels(
                $operation['expected']['outbound_cars'],
                $context['cars'],
                self::CAR_EXPECTED_FIELDS,
            ),
            'ledger' => $operation['expected']['ledger'] === null
                ? null
                : $this->snapshotModel(
                    $context['ledgers']->get((int) $operation['expected']['ledger']['id']),
                    self::LEDGER_EXPECTED_FIELDS,
                ),
        ];
    }

    /** @return array<string, mixed> */
    private function operationSnapshot(array $operation, array $ledgerIds): array
    {
        return [
            'warehouse_items' => WarehouseItems::query()
                ->whereKey($this->operationWarehouseItemIds($operation))
                ->orderBy('id')
                ->get()
                ->map(fn ($item): array => $this->snapshotModel($item, self::WAREHOUSE_EXPECTED_FIELDS))
                ->all(),
            'outbounds' => Outbound::query()
                ->whereKey($this->operationOutboundIds($operation))
                ->orderBy('id')
                ->get()
                ->map(fn ($outbound): array => $this->snapshotModel($outbound, self::OUTBOUND_EXPECTED_FIELDS))
                ->all(),
            'outbound_cars' => OutboundCar::query()
                ->whereKey($this->operationCarIds($operation))
                ->orderBy('id')
                ->get()
                ->map(fn ($car): array => $this->snapshotModel($car, self::CAR_EXPECTED_FIELDS))
                ->all(),
            'ledgers' => OutboundWarehouseItems::query()
                ->whereKey($ledgerIds)
                ->orderBy('id')
                ->get()
                ->map(fn ($ledger): array => $this->snapshotModel(
                    $ledger,
                    array_merge(self::LEDGER_EXPECTED_FIELDS, self::PROPORTIONAL_FIELDS, ['location', 'is_status']),
                ))
                ->all(),
        ];
    }

    private function snapshotExpectedModels(array $expectedRows, Collection $models, array $fields): array
    {
        return collect($expectedRows)
            ->map(fn (array $row): array => $this->snapshotModel($models->get((int) $row['id']), $fields))
            ->all();
    }

    private function snapshotModel(mixed $model, array $fields): array
    {
        return collect($fields)->mapWithKeys(fn (string $field): array => [
            $field => $model->{$field},
        ])->all();
    }

    /** @return list<int> */
    private function operationWarehouseItemIds(array $operation): array
    {
        return $this->uniqueIds(array_merge(
            array_column($operation['expected']['warehouse_items'], 'id'),
            Arr::get($operation, 'expected.ledger.warehouse_item_id') === null
                ? []
                : [(int) Arr::get($operation, 'expected.ledger.warehouse_item_id')],
            [(int) $operation['after']['ledger']['warehouse_item_id']],
            Arr::has($operation, 'after.source_ledger.warehouse_item_id')
                ? [(int) Arr::get($operation, 'after.source_ledger.warehouse_item_id')]
                : [],
        ));
    }

    /** @return list<int> */
    private function operationOutboundIds(array $operation): array
    {
        return $this->uniqueIds(array_merge(
            array_column($operation['expected']['outbounds'], 'id'),
            Arr::get($operation, 'expected.ledger.outbound_id') === null
                ? []
                : [(int) Arr::get($operation, 'expected.ledger.outbound_id')],
            [(int) $operation['after']['ledger']['outbound_id']],
            Arr::has($operation, 'after.source_ledger.outbound_id')
                ? [(int) Arr::get($operation, 'after.source_ledger.outbound_id')]
                : [],
        ));
    }

    /** @return list<int> */
    private function operationCarIds(array $operation): array
    {
        return $this->uniqueIds(array_merge(
            array_column($operation['expected']['outbound_cars'], 'id'),
            Arr::get($operation, 'expected.ledger.outbound_car_id') === null
                ? []
                : [(int) Arr::get($operation, 'expected.ledger.outbound_car_id')],
            [(int) $operation['after']['ledger']['outbound_car_id']],
            Arr::has($operation, 'after.source_ledger.outbound_car_id')
                ? [(int) Arr::get($operation, 'after.source_ledger.outbound_car_id')]
                : [],
        ));
    }

    private function idempotencyKey(array $document, array $operation): string
    {
        return hash(
            'sha256',
            'inventory-reconciliation:v'.self::SCHEMA_VERSION.'|'.$document['reference'].'|'.$operation['operation_id'],
        );
    }

    /** @return list<int> */
    private function uniqueIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);

        return array_values(array_filter($ids, fn (int $id): bool => $id > 0));
    }

    private function sameValue(mixed $expected, mixed $actual): bool
    {
        if ($expected === null || $actual === null) {
            return $expected === null && $actual === null;
        }

        if (is_bool($expected) || is_bool($actual)) {
            $booleanValues = [true, false, 1, 0, '1', '0'];

            return in_array($expected, $booleanValues, true)
                && in_array($actual, $booleanValues, true)
                && (bool) $expected === (bool) $actual;
        }

        if (is_numeric($expected) && is_numeric($actual)) {
            return abs($this->number($expected) - $this->number($actual)) < self::EPSILON;
        }

        return (string) $expected === (string) $actual;
    }

    private function number(mixed $value): float
    {
        return $value === null ? 0.0 : round((float) $value, 3);
    }

    private function printable(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
