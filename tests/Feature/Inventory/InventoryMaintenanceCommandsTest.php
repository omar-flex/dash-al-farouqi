<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryReconciliationAudit;
use App\Models\Outbound;
use App\Models\OutboundCar;
use App\Models\OutboundStatus;
use App\Models\OutboundWarehouseItems;
use App\Models\WarehouseItems;
use App\Services\Inventory\InventoryAuditor;
use App\Services\Inventory\InventoryReconciler;
use App\Services\Inventory\InventoryReconciliationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryMaintenanceCommandsTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        DB::table('outbound_statuses')->insert([
            ['id' => OutboundStatus::WH_RELEASE_PRODUCT, 'name' => 'Warehouse release'],
            ['id' => OutboundStatus::VALIDATION, 'name' => 'Validation'],
            ['id' => OutboundStatus::AUTHORIZATION, 'name' => 'Authorization'],
            ['id' => OutboundStatus::NEED_REVISION, 'name' => 'Needs revision'],
            ['id' => OutboundStatus::APPROVED, 'name' => 'Approved'],
        ]);
        DB::table('enter_requests')->insert(['id' => 10]);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        foreach ([
            'inventory_reconciliation_audits',
            'outbound_warehouse_items',
            'outbound_cars',
            'warehouse_items',
            'outbounds',
            'outbound_statuses',
            'enter_requests',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_audit_reports_mismatches_in_json_and_csv_without_writing(): void
    {
        [$outbound, $car] = $this->outboundWithCar(OutboundStatus::VALIDATION, 4);
        $item = $this->warehouseItem(10, 5);
        $movement = OutboundWarehouseItems::query()->create([
            'warehouse_item_id' => $item->id,
            'outbound_id' => $outbound->id,
            'outbound_car_id' => $car->id,
            'quantity' => 4,
            'other_quantity' => 2,
        ]);

        $before = [
            'warehouse_item' => $item->fresh()->getAttributes(),
            'movement' => $movement->fresh()->getAttributes(),
        ];

        $report = $this->app->make(InventoryAuditor::class)->run();

        $this->assertSame(1, $report['summary']['by_code']['balance_mismatch']);
        $this->assertSame(1, $report['summary']['by_code']['other_balance_mismatch']);
        $this->assertFalse($report['summary']['constraints_ready']);
        $this->assertSame(2, $report['summary']['constraint_blocker_count']);

        $exitCode = Artisan::call('inventory:audit', ['--format' => 'csv']);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('balance_mismatch', Artisan::output());

        $this->assertSame($before['warehouse_item'], $item->fresh()->getAttributes());
        $this->assertSame($before['movement'], $movement->fresh()->getAttributes());
    }

    public function test_audit_treats_overallocation_and_missing_links_as_constraint_blockers(): void
    {
        [$outbound, $car] = $this->outboundWithCar(OutboundStatus::APPROVED, 12);
        $item = $this->warehouseItem(10);
        OutboundWarehouseItems::query()->create([
            'warehouse_item_id' => $item->id,
            'outbound_id' => $outbound->id,
            'outbound_car_id' => $car->id,
            'quantity' => 12,
        ]);
        OutboundWarehouseItems::query()->create([
            'warehouse_item_id' => null,
            'outbound_id' => null,
            'outbound_car_id' => null,
            'quantity' => 1,
            'other_quantity' => -1,
        ]);

        $report = $this->app->make(InventoryAuditor::class)->run();

        $this->assertSame(1, $report['summary']['by_code']['over_allocation']);
        $this->assertSame(1, $report['summary']['by_code']['ledger_missing_warehouse_item']);
        $this->assertSame(1, $report['summary']['by_code']['ledger_missing_outbound']);
        $this->assertSame(1, $report['summary']['by_code']['ledger_missing_car']);
        $this->assertSame(1, $report['summary']['by_code']['negative_ledger_other_quantity']);
        $this->assertFalse($report['summary']['constraints_ready']);
        $this->assertGreaterThanOrEqual(5, $report['summary']['constraint_blocker_count']);
    }

    public function test_scoped_audit_uses_every_line_when_comparing_an_outbound_total(): void
    {
        [$outbound, $car] = $this->outboundWithCar(OutboundStatus::APPROVED, 5);
        $firstItem = $this->warehouseItem(10);
        $secondItem = $this->warehouseItem(10);
        $firstItem->update(['remaining_quantity' => 8]);
        $secondItem->update(['remaining_quantity' => 7]);
        OutboundWarehouseItems::query()->create([
            'warehouse_item_id' => $firstItem->id,
            'outbound_id' => $outbound->id,
            'outbound_car_id' => $car->id,
            'quantity' => 2,
        ]);
        OutboundWarehouseItems::query()->create([
            'warehouse_item_id' => $secondItem->id,
            'outbound_id' => $outbound->id,
            'outbound_car_id' => $car->id,
            'quantity' => 3,
        ]);

        $report = $this->app->make(InventoryAuditor::class)->run([$firstItem->id]);

        $this->assertArrayNotHasKey('outbound_total_mismatch', $report['summary']['by_code']);
        $this->assertSame(0, $report['summary']['issue_count']);
    }

    public function test_reconcile_defaults_to_read_only_then_applies_atomically_and_is_idempotent(): void
    {
        [$outbound, $car] = $this->outboundWithCar(OutboundStatus::APPROVED, 4);
        $item = $this->warehouseItem(10, 5);
        $path = $this->writeDocument($this->createDocument($outbound, $car, $item));

        $exitCode = Artisan::call('inventory:reconcile', ['file' => $path]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"mode": "dry-run"', Artisan::output());
        $this->assertSame(0, OutboundWarehouseItems::query()->count());
        $this->assertSame(10.0, (float) $item->fresh()->remaining_quantity);
        $this->assertSame(0, InventoryReconciliationAudit::query()->count());

        $exitCode = Artisan::call('inventory:reconcile', [
            'file' => $path,
            '--apply' => true,
            '--actor' => 'phpunit',
        ]);

        $this->assertSame(0, $exitCode);
        $movement = OutboundWarehouseItems::query()->sole();
        $this->assertSame(4.0, (float) $movement->quantity);
        $this->assertSame(6.0, (float) $item->fresh()->remaining_quantity);
        $this->assertSame(3.0, (float) $item->fresh()->remaining_other_quantity);
        $audit = InventoryReconciliationAudit::query()->sole();
        $this->assertSame('phpunit', $audit->actor);
        $this->assertSame([$movement->id], $audit->resulting_ledger_ids);
        $this->assertSame(10.0, (float) $audit->before['warehouse_items'][0]['remaining_quantity']);
        $this->assertSame(6.0, (float) $audit->after['warehouse_items'][0]['remaining_quantity']);

        $result = $this->app->make(InventoryReconciler::class)->apply($path, 'phpunit-again');

        $this->assertTrue($result['idempotent']);
        $this->assertFalse($result['applied']);
        $this->assertSame(1, OutboundWarehouseItems::query()->count());
        $this->assertSame(1, InventoryReconciliationAudit::query()->count());
    }

    public function test_reconcile_rejects_stale_expected_values_without_partial_changes(): void
    {
        [$outbound, $car] = $this->outboundWithCar(OutboundStatus::APPROVED, 4);
        $item = $this->warehouseItem(10);
        $document = $this->createDocument($outbound, $car, $item);
        $document['operations'][0]['expected']['warehouse_items'][0]['remaining_quantity'] = 9;
        $path = $this->writeDocument($document);

        try {
            $this->app->make(InventoryReconciler::class)->apply($path, 'phpunit');
            $this->fail('Expected the stale reconciliation plan to be rejected.');
        } catch (InventoryReconciliationException $exception) {
            $this->assertStringContainsString('stale', implode(' ', $exception->errors()));
        }

        $this->assertSame(0, OutboundWarehouseItems::query()->count());
        $this->assertSame(10.0, (float) $item->fresh()->remaining_quantity);
        $this->assertSame(0, InventoryReconciliationAudit::query()->count());
    }

    public function test_reconcile_rejects_a_target_without_a_signed_expected_snapshot(): void
    {
        [$outbound, $car] = $this->outboundWithCar(OutboundStatus::APPROVED, 4);
        $documentedItem = $this->warehouseItem(10);
        $undocumentedTarget = $this->warehouseItem(10);
        $document = $this->createDocument($outbound, $car, $documentedItem);
        $document['reference'] = 'unsigned-target-test';
        $document['operations'][0]['after']['ledger']['warehouse_item_id'] = $undocumentedTarget->id;
        $path = $this->writeDocument($document);

        try {
            $this->app->make(InventoryReconciler::class)->preview($path, 'phpunit');
            $this->fail('Expected an unsigned target snapshot to be rejected.');
        } catch (InventoryReconciliationException $exception) {
            $this->assertStringContainsString(
                'complete expected.warehouse_items snapshot',
                implode(' ', $exception->errors()),
            );
        }

        $this->assertSame(0, OutboundWarehouseItems::query()->count());
        $this->assertSame(10.0, (float) $undocumentedTarget->fresh()->remaining_quantity);
    }

    public function test_reconcile_supports_documented_update_relink_and_split_operations(): void
    {
        [$outbound, $firstCar] = $this->outboundWithCar(OutboundStatus::APPROVED, 6);
        $secondCar = OutboundCar::query()->create([
            'outbound_id' => $outbound->id,
            'number' => 'SECOND-CAR',
        ]);
        $firstItem = $this->warehouseItem(10);
        $secondItem = $this->warehouseItem(10);
        $firstItem->update(['remaining_quantity' => 4]);
        $movement = OutboundWarehouseItems::query()->create([
            'warehouse_item_id' => $firstItem->id,
            'outbound_id' => $outbound->id,
            'outbound_car_id' => $firstCar->id,
            'quantity' => 6,
            'other_quantity' => null,
        ]);

        $update = $this->baseDocument(
            reference: 'signed-update-001',
            operationId: 'update-movement-1',
            action: 'update',
            expectedWarehouseItems: [$this->warehouseSnapshot($firstItem->fresh())],
            expectedOutbounds: [$this->outboundSnapshot($outbound->fresh())],
            expectedCars: [$this->carSnapshot($firstCar)],
            expectedLedger: $this->ledgerSnapshot($movement),
            after: [
                'ledger' => [
                    'warehouse_item_id' => $firstItem->id,
                    'outbound_id' => $outbound->id,
                    'outbound_car_id' => $firstCar->id,
                    'quantity' => 5,
                    'other_quantity' => null,
                ],
            ],
            outboundUpdates: [[
                'id' => $outbound->id,
                'expected_quantity_packages' => 6,
                'quantity_packages' => 5,
            ]],
        );
        $this->app->make(InventoryReconciler::class)->apply($this->writeDocument($update), 'phpunit');

        $this->assertSame(5.0, (float) $movement->fresh()->quantity);
        $this->assertSame(5.0, (float) $firstItem->fresh()->remaining_quantity);
        $this->assertSame(5.0, (float) $outbound->fresh()->quantity_packages);

        $relink = $this->baseDocument(
            reference: 'signed-relink-001',
            operationId: 'relink-movement-1',
            action: 'relink',
            expectedWarehouseItems: [$this->warehouseSnapshot($firstItem->fresh())],
            expectedOutbounds: [$this->outboundSnapshot($outbound->fresh())],
            expectedCars: [$this->carSnapshot($firstCar), $this->carSnapshot($secondCar)],
            expectedLedger: $this->ledgerSnapshot($movement->fresh()),
            after: [
                'ledger' => [
                    'warehouse_item_id' => $firstItem->id,
                    'outbound_id' => $outbound->id,
                    'outbound_car_id' => $secondCar->id,
                    'quantity' => 5,
                    'other_quantity' => null,
                ],
            ],
        );
        $this->app->make(InventoryReconciler::class)->apply($this->writeDocument($relink), 'phpunit');

        $this->assertSame($secondCar->id, $movement->fresh()->outbound_car_id);
        $this->assertSame(5.0, (float) $firstItem->fresh()->remaining_quantity);

        $split = $this->baseDocument(
            reference: 'signed-split-001',
            operationId: 'split-movement-1',
            action: 'split',
            expectedWarehouseItems: [
                $this->warehouseSnapshot($firstItem->fresh()),
                $this->warehouseSnapshot($secondItem->fresh()),
            ],
            expectedOutbounds: [$this->outboundSnapshot($outbound->fresh())],
            expectedCars: [$this->carSnapshot($firstCar), $this->carSnapshot($secondCar)],
            expectedLedger: $this->ledgerSnapshot($movement->fresh()),
            after: [
                'source_ledger' => [
                    'warehouse_item_id' => $firstItem->id,
                    'outbound_id' => $outbound->id,
                    'outbound_car_id' => $secondCar->id,
                    'quantity' => 2,
                    'other_quantity' => null,
                ],
                'ledger' => [
                    'warehouse_item_id' => $secondItem->id,
                    'outbound_id' => $outbound->id,
                    'outbound_car_id' => $firstCar->id,
                    'quantity' => 3,
                    'other_quantity' => null,
                ],
            ],
        );
        $this->app->make(InventoryReconciler::class)->apply($this->writeDocument($split), 'phpunit');

        $this->assertSame(2, OutboundWarehouseItems::query()->count());
        $this->assertSame(2.0, (float) $movement->fresh()->quantity);
        $this->assertSame(8.0, (float) $firstItem->fresh()->remaining_quantity);
        $this->assertSame(7.0, (float) $secondItem->fresh()->remaining_quantity);
        $this->assertSame(3, InventoryReconciliationAudit::query()->count());
    }

    public function test_relink_can_repair_an_orphan_ledger_row_without_direct_balance_edits(): void
    {
        [$outbound, $car] = $this->outboundWithCar(OutboundStatus::APPROVED, 4);
        $item = $this->warehouseItem(10);
        $orphan = OutboundWarehouseItems::query()->create([
            'warehouse_item_id' => null,
            'outbound_id' => null,
            'outbound_car_id' => null,
            'quantity' => 4,
            'other_quantity' => null,
        ]);
        $document = $this->baseDocument(
            reference: 'signed-orphan-relink-001',
            operationId: 'relink-orphan-1',
            action: 'relink',
            expectedWarehouseItems: [$this->warehouseSnapshot($item)],
            expectedOutbounds: [$this->outboundSnapshot($outbound)],
            expectedCars: [$this->carSnapshot($car)],
            expectedLedger: $this->ledgerSnapshot($orphan),
            after: [
                'ledger' => [
                    'warehouse_item_id' => $item->id,
                    'outbound_id' => $outbound->id,
                    'outbound_car_id' => $car->id,
                    'quantity' => 4,
                    'other_quantity' => null,
                ],
            ],
        );

        $result = $this->app->make(InventoryReconciler::class)->apply(
            $this->writeDocument($document),
            'phpunit',
        );

        $this->assertTrue($result['applied']);
        $this->assertSame($item->id, $orphan->fresh()->warehouse_item_id);
        $this->assertSame($outbound->id, $orphan->fresh()->outbound_id);
        $this->assertSame($car->id, $orphan->fresh()->outbound_car_id);
        $this->assertSame(6.0, (float) $item->fresh()->remaining_quantity);
        $this->assertSame(1, InventoryReconciliationAudit::query()->count());
    }

    /** @return array{Outbound, OutboundCar} */
    private function outboundWithCar(int $statusId, float $quantityPackages): array
    {
        $outbound = Outbound::query()->create([
            'status_id' => $statusId,
            'enter_request_id' => 10,
            'quantity_packages' => $quantityPackages,
        ]);
        $car = OutboundCar::query()->create([
            'outbound_id' => $outbound->id,
            'number' => 'CAR-'.$outbound->id,
        ]);

        return [$outbound, $car];
    }

    private function warehouseItem(float $quantity, ?float $otherQuantity = null): WarehouseItems
    {
        return WarehouseItems::query()->create([
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'other_quantity' => $otherQuantity,
            'remaining_other_quantity' => $otherQuantity,
            'is_status' => true,
            'enter_request_id' => 10,
            'custom_value' => 100,
            'gross_weight' => 50,
            'net_weight' => 45,
            'cpm' => 20,
            'cpm_capacity' => 10,
        ]);
    }

    /** @return array<string, mixed> */
    private function createDocument(Outbound $outbound, OutboundCar $car, WarehouseItems $item): array
    {
        return [
            'schema_version' => 1,
            'reference' => 'signed-dispatch-2026-001',
            'approved_by' => 'Inventory Committee',
            'operations' => [[
                'operation_id' => 'create-missing-ledger-1',
                'action' => 'create',
                'reason' => 'Signed dispatch proves that four packages physically left the warehouse.',
                'expected' => [
                    'warehouse_items' => [[
                        'id' => $item->id,
                        'quantity' => (float) $item->quantity,
                        'remaining_quantity' => (float) $item->remaining_quantity,
                        'other_quantity' => $item->other_quantity === null ? null : (float) $item->other_quantity,
                        'remaining_other_quantity' => $item->remaining_other_quantity === null
                            ? null
                            : (float) $item->remaining_other_quantity,
                    ]],
                    'outbounds' => [[
                        'id' => $outbound->id,
                        'quantity_packages' => (float) $outbound->quantity_packages,
                        'status_id' => (int) $outbound->status_id,
                        'enter_request_id' => (int) $outbound->enter_request_id,
                    ]],
                    'outbound_cars' => [[
                        'id' => $car->id,
                        'outbound_id' => (int) $car->outbound_id,
                    ]],
                    'ledger' => null,
                ],
                'after' => [
                    'ledger' => [
                        'warehouse_item_id' => $item->id,
                        'outbound_id' => $outbound->id,
                        'outbound_car_id' => $car->id,
                        'quantity' => 4,
                        'other_quantity' => $item->other_quantity === null ? null : 2,
                        'location' => 'WH-A-01',
                    ],
                ],
            ]],
            'outbound_updates' => [],
        ];
    }

    private function baseDocument(
        string $reference,
        string $operationId,
        string $action,
        array $expectedWarehouseItems,
        array $expectedOutbounds,
        array $expectedCars,
        ?array $expectedLedger,
        array $after,
        array $outboundUpdates = [],
    ): array {
        return [
            'schema_version' => 1,
            'reference' => $reference,
            'approved_by' => 'Inventory Committee',
            'operations' => [[
                'operation_id' => $operationId,
                'action' => $action,
                'reason' => 'Signed inventory documents confirm this exact ledger correction.',
                'expected' => [
                    'warehouse_items' => $expectedWarehouseItems,
                    'outbounds' => $expectedOutbounds,
                    'outbound_cars' => $expectedCars,
                    'ledger' => $expectedLedger,
                ],
                'after' => $after,
            ]],
            'outbound_updates' => $outboundUpdates,
        ];
    }

    private function warehouseSnapshot(WarehouseItems $item): array
    {
        return [
            'id' => $item->id,
            'quantity' => (float) $item->quantity,
            'remaining_quantity' => $item->remaining_quantity === null
                ? null
                : (float) $item->remaining_quantity,
            'other_quantity' => $item->other_quantity === null ? null : (float) $item->other_quantity,
            'remaining_other_quantity' => $item->remaining_other_quantity === null
                ? null
                : (float) $item->remaining_other_quantity,
        ];
    }

    private function outboundSnapshot(Outbound $outbound): array
    {
        return [
            'id' => $outbound->id,
            'quantity_packages' => $outbound->quantity_packages === null
                ? null
                : (float) $outbound->quantity_packages,
            'status_id' => $outbound->status_id === null ? null : (int) $outbound->status_id,
            'enter_request_id' => $outbound->enter_request_id === null
                ? null
                : (int) $outbound->enter_request_id,
        ];
    }

    private function carSnapshot(OutboundCar $car): array
    {
        return [
            'id' => $car->id,
            'outbound_id' => $car->outbound_id === null ? null : (int) $car->outbound_id,
        ];
    }

    private function ledgerSnapshot(OutboundWarehouseItems $movement): array
    {
        $movement = $movement->fresh();

        return [
            'id' => $movement->id,
            'warehouse_item_id' => $movement->warehouse_item_id === null
                ? null
                : (int) $movement->warehouse_item_id,
            'outbound_id' => $movement->outbound_id === null ? null : (int) $movement->outbound_id,
            'outbound_car_id' => $movement->outbound_car_id === null
                ? null
                : (int) $movement->outbound_car_id,
            'quantity' => $movement->quantity === null ? null : (float) $movement->quantity,
            'other_quantity' => $movement->other_quantity === null
                ? null
                : (float) $movement->other_quantity,
            'location' => $movement->location,
            'custom_value' => $movement->custom_value === null ? null : (float) $movement->custom_value,
            'gross_weight' => $movement->gross_weight === null ? null : (float) $movement->gross_weight,
            'net_weight' => $movement->net_weight === null ? null : (float) $movement->net_weight,
            'cpm' => $movement->cpm === null ? null : (float) $movement->cpm,
            'cpm_capacity' => $movement->cpm_capacity === null ? null : (float) $movement->cpm_capacity,
            'is_status' => $movement->is_status === null ? null : (bool) $movement->is_status,
        ];
    }

    private function writeDocument(array $document): string
    {
        $path = tempnam(sys_get_temp_dir(), 'inventory-reconcile-');
        if ($path === false) {
            $this->fail('Could not allocate a temporary reconciliation file.');
        }
        file_put_contents($path, json_encode($document, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->temporaryFiles[] = $path;

        return $path;
    }

    private function createSchema(): void
    {
        Schema::create('enter_requests', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('outbound_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('outbounds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('enter_request_id')->nullable();
            $table->decimal('quantity_packages', 18, 3)->nullable();
            $table->timestamps();
        });
        Schema::create('warehouse_items', function (Blueprint $table): void {
            $table->id();
            $table->decimal('quantity', 18, 3);
            $table->decimal('remaining_quantity', 18, 3)->nullable();
            $table->decimal('other_quantity', 18, 3)->nullable();
            $table->decimal('remaining_other_quantity', 18, 3)->nullable();
            $table->boolean('is_status')->default(true);
            $table->unsignedBigInteger('enter_request_id')->nullable();
            $table->decimal('custom_value', 18, 3)->nullable();
            $table->decimal('gross_weight', 18, 3)->nullable();
            $table->decimal('net_weight', 18, 3)->nullable();
            $table->decimal('cpm', 18, 3)->nullable();
            $table->decimal('cpm_capacity', 18, 3)->nullable();
            $table->timestamps();
        });
        Schema::create('outbound_cars', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('outbound_id')->nullable();
            $table->string('number')->nullable();
            $table->timestamps();
        });
        Schema::create('outbound_warehouse_items', function (Blueprint $table): void {
            $table->id();
            $table->decimal('quantity', 18, 3)->nullable();
            $table->decimal('other_quantity', 18, 3)->nullable();
            $table->string('location')->nullable();
            $table->decimal('custom_value', 18, 3)->nullable();
            $table->decimal('gross_weight', 18, 3)->nullable();
            $table->decimal('net_weight', 18, 3)->nullable();
            $table->decimal('cpm', 18, 3)->nullable();
            $table->decimal('cpm_capacity', 18, 3)->nullable();
            $table->boolean('is_status')->default(false);
            $table->unsignedBigInteger('warehouse_item_id')->nullable();
            $table->unsignedBigInteger('outbound_id')->nullable();
            $table->unsignedBigInteger('outbound_car_id')->nullable();
            $table->timestamps();
        });
        Schema::create('inventory_reconciliation_audits', function (Blueprint $table): void {
            $table->id();
            $table->uuid('run_id')->index();
            $table->string('idempotency_key', 64)->unique();
            $table->string('source_hash', 64);
            $table->string('source_file');
            $table->string('reference');
            $table->string('operation_id');
            $table->string('action', 20);
            $table->string('actor')->nullable();
            $table->unsignedBigInteger('source_ledger_id')->nullable();
            $table->json('resulting_ledger_ids')->nullable();
            $table->json('affected_warehouse_item_ids');
            $table->text('reason');
            $table->json('before');
            $table->json('after');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
}
