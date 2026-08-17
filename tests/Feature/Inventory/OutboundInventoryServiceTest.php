<?php

namespace Tests\Feature\Inventory;

use App\Http\Controllers\OperationManagement\OutboundsController;
use App\Http\Requests\OperationManagement\CarsRequest;
use App\Http\Requests\OperationManagement\OutboundValidationsRequest;
use App\Models\Outbound;
use App\Models\OutboundCar;
use App\Models\OutboundStatus;
use App\Models\OutboundWarehouseItems;
use App\Models\WarehouseItems;
use App\Services\Inventory\OutboundInventoryService;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class OutboundInventoryServiceTest extends TestCase
{
    private OutboundInventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createInventorySchema();
        $this->actingAs(new InventoryControllerUser(['id' => 1, 'name' => 'Inventory Editor']));
        $this->service = $this->app->make(OutboundInventoryService::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('outbound_files');
        Schema::dropIfExists('outbound_warehouse_items');
        Schema::dropIfExists('outbound_cars');
        Schema::dropIfExists('warehouse_items');
        Schema::dropIfExists('outbounds');

        parent::tearDown();
    }

    public function test_draft_does_not_reserve_stock_and_repeated_submit_does_not_double_deduct(): void
    {
        [$outbound, $car] = $this->outboundWithCar(quantityPackages: 40);
        $warehouseItem = $this->warehouseItem(quantity: 100, otherQuantity: 10);

        $this->service->sync($outbound, [[
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $car->id,
            'quantity' => 40,
            'other_quantity' => 4,
        ]], 'draft');

        $movement = OutboundWarehouseItems::query()->firstOrFail();
        $this->assertSame(100.0, (float) $warehouseItem->fresh()->remaining_quantity);
        $this->assertSame(10.0, (float) $warehouseItem->fresh()->remaining_other_quantity);
        $this->assertSame(OutboundStatus::WH_RELEASE_PRODUCT, (int) $outbound->fresh()->status_id);

        $this->service->sync($outbound, [[
            'id' => $movement->id,
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $car->id,
            'quantity' => 40,
            'other_quantity' => 4,
        ]], 'submit');

        $this->service->sync($outbound->fresh(), [[
            'id' => $movement->id,
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $car->id,
            'quantity' => 40,
            'other_quantity' => 4,
        ]], 'submit');

        $this->assertSame(60.0, (float) $warehouseItem->fresh()->remaining_quantity);
        $this->assertSame(6.0, (float) $warehouseItem->fresh()->remaining_other_quantity);
        $this->assertSame(1, OutboundWarehouseItems::query()->count());
    }

    public function test_moving_a_movement_recalculates_both_old_and_new_warehouse_items(): void
    {
        [$outbound, $car] = $this->outboundWithCar(quantityPackages: 30);
        $oldWarehouseItem = $this->warehouseItem(quantity: 100);
        $newWarehouseItem = $this->warehouseItem(quantity: 50);

        $this->service->sync($outbound, [[
            'warehouse_item_id' => $oldWarehouseItem->id,
            'outbound_car_id' => $car->id,
            'quantity' => 30,
        ]], 'submit');

        $movement = OutboundWarehouseItems::query()->firstOrFail();
        $outbound->refresh()->update(['status_id' => OutboundStatus::NEED_REVISION]);

        $this->service->sync($outbound->fresh(), [[
            'id' => $movement->id,
            'warehouse_item_id' => $newWarehouseItem->id,
            'outbound_car_id' => $car->id,
            'quantity' => 30,
        ]], 'draft');

        $this->assertSame(100.0, (float) $oldWarehouseItem->fresh()->remaining_quantity);
        $this->assertSame(20.0, (float) $newWarehouseItem->fresh()->remaining_quantity);
        $this->assertSame($newWarehouseItem->id, OutboundWarehouseItems::query()->firstOrFail()->warehouse_item_id);
    }

    public function test_explicit_movement_id_takes_precedence_when_relinking_to_an_existing_pair(): void
    {
        [$outbound, $firstCar] = $this->outboundWithCar(quantityPackages: 10);
        $secondCar = OutboundCar::query()->create([
            'outbound_id' => $outbound->id,
            'number' => 'CAR-SECOND',
        ]);
        $firstWarehouseItem = $this->warehouseItem(quantity: 50);
        $secondWarehouseItem = $this->warehouseItem(quantity: 50);

        $this->service->sync($outbound, [
            [
                'warehouse_item_id' => $firstWarehouseItem->id,
                'outbound_car_id' => $firstCar->id,
                'quantity' => 5,
            ],
            [
                'warehouse_item_id' => $secondWarehouseItem->id,
                'outbound_car_id' => $secondCar->id,
                'quantity' => 5,
            ],
        ], 'draft');

        $explicitMovement = OutboundWarehouseItems::query()
            ->where('warehouse_item_id', $firstWarehouseItem->id)
            ->firstOrFail();

        $this->service->sync($outbound, [[
            'id' => $explicitMovement->id,
            'warehouse_item_id' => $secondWarehouseItem->id,
            'outbound_car_id' => $secondCar->id,
            'quantity' => 5,
        ]], 'draft');

        $this->assertSame(1, OutboundWarehouseItems::query()->count());
        $this->assertDatabaseHas('outbound_warehouse_items', [
            'id' => $explicitMovement->id,
            'warehouse_item_id' => $secondWarehouseItem->id,
            'outbound_car_id' => $secondCar->id,
        ]);
    }

    public function test_competing_drafts_can_be_saved_but_second_submit_rejects_over_allocation(): void
    {
        [$firstOutbound, $firstCar] = $this->outboundWithCar(quantityPackages: 80);
        [$secondOutbound, $secondCar] = $this->outboundWithCar(quantityPackages: 30);
        $warehouseItem = $this->warehouseItem(quantity: 100);

        $this->service->sync($firstOutbound, [[
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $firstCar->id,
            'quantity' => 80,
        ]], 'draft');

        $this->service->sync($secondOutbound, [[
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $secondCar->id,
            'quantity' => 30,
        ]], 'draft');

        $this->assertSame(100.0, (float) $warehouseItem->fresh()->remaining_quantity);
        $this->assertSame(2, OutboundWarehouseItems::query()->count());

        $this->service->sync($firstOutbound->fresh(), [[
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $firstCar->id,
            'quantity' => 80,
        ]], 'submit');

        try {
            $this->service->sync($secondOutbound->fresh(), [[
                'warehouse_item_id' => $warehouseItem->id,
                'outbound_car_id' => $secondCar->id,
                'quantity' => 30,
            ]], 'submit');
            $this->fail('Expected over-allocation to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items.0.quantity', $exception->errors());
        }

        $this->assertSame(2, OutboundWarehouseItems::query()->count());
        $this->assertSame(20.0, (float) $warehouseItem->fresh()->remaining_quantity);
        $this->assertSame(OutboundStatus::WH_RELEASE_PRODUCT, (int) $secondOutbound->fresh()->status_id);
    }

    public function test_submit_requires_package_total_and_rolls_back_on_mismatch(): void
    {
        [$outbound, $car] = $this->outboundWithCar(quantityPackages: 40);
        $warehouseItem = $this->warehouseItem(quantity: 100);

        try {
            $this->service->sync($outbound, [[
                'warehouse_item_id' => $warehouseItem->id,
                'outbound_car_id' => $car->id,
                'quantity' => 39,
            ]], 'submit');
            $this->fail('Expected the package total mismatch to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame(0, OutboundWarehouseItems::query()->count());
        $this->assertSame(100.0, (float) $warehouseItem->fresh()->remaining_quantity);
        $this->assertSame(OutboundStatus::WH_RELEASE_PRODUCT, (int) $outbound->fresh()->status_id);
    }

    public function test_need_revision_edits_must_keep_the_package_total_and_round_to_three_decimals(): void
    {
        [$outbound, $car] = $this->outboundWithCar(quantityPackages: 10);
        $warehouseItem = $this->warehouseItem(quantity: 100);

        $this->service->sync($outbound, [[
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $car->id,
            'quantity' => 10,
        ]], 'submit');
        $outbound->refresh()->update(['status_id' => OutboundStatus::NEED_REVISION]);

        try {
            $this->service->sync($outbound->fresh(), [[
                'warehouse_item_id' => $warehouseItem->id,
                'outbound_car_id' => $car->id,
                'quantity' => 9.9994,
            ]], 'draft');
            $this->fail('Expected a revision package-total mismatch to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->service->sync($outbound->fresh(), [[
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $car->id,
            'quantity' => 9.9996,
        ]], 'draft');

        $this->assertSame(10.0, (float) OutboundWarehouseItems::query()->firstOrFail()->quantity);
        $this->assertSame(90.0, (float) $warehouseItem->fresh()->remaining_quantity);
    }

    public function test_direct_call_rejects_a_quantity_that_rounds_to_zero(): void
    {
        [$outbound, $car] = $this->outboundWithCar(quantityPackages: 1);
        $warehouseItem = $this->warehouseItem(quantity: 100);

        try {
            $this->service->sync($outbound, [[
                'warehouse_item_id' => $warehouseItem->id,
                'outbound_car_id' => $car->id,
                'quantity' => 0.0004,
            ]], 'draft');
            $this->fail('Expected a rounded-zero quantity to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items.0.quantity', $exception->errors());
        }

        $this->assertSame(0, OutboundWarehouseItems::query()->count());
    }

    public function test_selectable_stock_uses_ledger_availability_instead_of_cached_remaining_fields(): void
    {
        [$outbound, $car] = $this->outboundWithCar(quantityPackages: 100);
        $warehouseItem = $this->warehouseItem(quantity: 100);
        $warehouseItem->update(['remaining_quantity' => 0, 'is_status' => false]);

        $selectable = $this->service->selectableWarehouseItems($outbound);

        $this->assertTrue($selectable->contains('id', $warehouseItem->id));
        $this->assertSame(
            100.0,
            (float) $selectable->firstWhere('id', $warehouseItem->id)->calculated_available_quantity,
        );

        $this->service->sync($outbound, [[
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $car->id,
            'quantity' => 100,
        ]], 'draft');

        $selectedCurrentItem = $this->service
            ->selectableWarehouseItems($outbound)
            ->firstWhere('id', $warehouseItem->id);

        $this->assertNotNull($selectedCurrentItem);
        $this->assertSame(100.0, (float) $selectedCurrentItem->calculated_available_quantity);
    }

    public function test_delete_restores_balance_and_approved_outbounds_are_immutable(): void
    {
        [$outbound, $car] = $this->outboundWithCar(quantityPackages: 20);
        $warehouseItem = $this->warehouseItem(quantity: 50);

        $this->service->sync($outbound, [[
            'warehouse_item_id' => $warehouseItem->id,
            'outbound_car_id' => $car->id,
            'quantity' => 20,
        ]], 'submit');

        $outbound->refresh()->update(['status_id' => OutboundStatus::APPROVED]);

        try {
            $this->service->deleteOutbound($outbound->fresh());
            $this->fail('Expected approved outbound deletion to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('outbound', $exception->errors());
        }

        $outbound->refresh()->update(['status_id' => OutboundStatus::NEED_REVISION]);
        $this->service->deleteOutbound($outbound->fresh());

        $this->assertSame(50.0, (float) $warehouseItem->fresh()->remaining_quantity);
        $this->assertSame(0, OutboundWarehouseItems::query()->count());
        $this->assertNull(Outbound::query()->find($outbound->id));
    }

    public function test_cars_endpoint_rejects_a_car_owned_by_another_outbound(): void
    {
        [$outbound] = $this->outboundWithCar(quantityPackages: 10);
        [$otherOutbound, $otherCar] = $this->outboundWithCar(quantityPackages: 10);
        $outbound->update(['status_id' => OutboundStatus::CAR_CHECK]);
        $otherOutbound->update(['status_id' => OutboundStatus::CAR_CHECK]);

        $request = Mockery::mock(CarsRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'numbers' => ['FOREIGN-CAR'],
            'seal_numbers' => [null],
            'car_ids' => [$otherCar->id],
        ]);

        try {
            $this->app->make(OutboundsController::class)->cars($outbound->id, $request);
            $this->fail('Expected foreign car ownership to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('car_ids.0', $exception->errors());
        }

        $this->assertSame('CAR-'.$otherOutbound->id, $otherCar->fresh()->number);
        $this->assertSame(OutboundStatus::CAR_CHECK, (int) $outbound->fresh()->status_id);
    }

    public function test_cars_endpoint_cannot_regress_a_later_workflow_status(): void
    {
        [$outbound] = $this->outboundWithCar(quantityPackages: 10);
        $outbound->update(['status_id' => OutboundStatus::VALIDATION]);

        $request = Mockery::mock(CarsRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'numbers' => ['CAR-CHANGED'],
            'seal_numbers' => [null],
            'car_ids' => [],
        ]);

        try {
            $this->app->make(OutboundsController::class)->cars($outbound->id, $request);
            $this->fail('Expected later workflow status car edits to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('outbound', $exception->errors());
        }

        $this->assertSame(OutboundStatus::VALIDATION, (int) $outbound->fresh()->status_id);
    }

    public function test_validations_endpoint_rejects_a_line_owned_by_another_outbound(): void
    {
        [$outbound, $car] = $this->outboundWithCar(quantityPackages: 10);
        [$otherOutbound, $otherCar] = $this->outboundWithCar(quantityPackages: 10);
        $outbound->update(['status_id' => OutboundStatus::VALIDATION]);
        $otherOutbound->update(['status_id' => OutboundStatus::VALIDATION]);
        $warehouseItem = $this->warehouseItem(quantity: 100);

        $ownMovement = OutboundWarehouseItems::query()->create([
            'outbound_id' => $outbound->id,
            'outbound_car_id' => $car->id,
            'warehouse_item_id' => $warehouseItem->id,
            'quantity' => 10,
            'custom_value' => 10,
        ]);
        $foreignMovement = OutboundWarehouseItems::query()->create([
            'outbound_id' => $otherOutbound->id,
            'outbound_car_id' => $otherCar->id,
            'warehouse_item_id' => $warehouseItem->id,
            'quantity' => 10,
            'custom_value' => 20,
        ]);

        $request = Mockery::mock(OutboundValidationsRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'items_id' => [$foreignMovement->id],
            'custom_values' => [99],
            'gross_weights' => [99],
            'net_weights' => [99],
        ]);

        try {
            $this->app->make(OutboundsController::class)->validations($outbound->id, $request);
            $this->fail('Expected foreign validation line ownership to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items_id.0', $exception->errors());
        }

        $this->assertSame(10.0, (float) $ownMovement->fresh()->custom_value);
        $this->assertSame(20.0, (float) $foreignMovement->fresh()->custom_value);
    }

    private function outboundWithCar(float $quantityPackages): array
    {
        $outbound = Outbound::query()->create([
            'status_id' => OutboundStatus::WH_RELEASE_PRODUCT,
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
            'custom_value' => 1000,
            'gross_weight' => 500,
            'net_weight' => 450,
            'cpm' => 20,
            'cpm_capacity' => 10,
        ]);
    }

    private function createInventorySchema(): void
    {
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
            $table->unsignedBigInteger('location_line_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('level')->nullable();
            $table->string('pallet')->nullable();
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

        Schema::create('outbound_files', function (Blueprint $table): void {
            $table->id();
            $table->string('path')->nullable();
            $table->unsignedBigInteger('outbound_id')->nullable();
            $table->timestamps();
        });
    }
}

class InventoryControllerUser extends GenericUser
{
    public function can($abilities, $arguments = []): bool
    {
        return true;
    }
}
