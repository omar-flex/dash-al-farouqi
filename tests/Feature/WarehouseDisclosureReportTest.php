<?php

namespace Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WarehouseDisclosureReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('tax_number')->nullable();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('barcode')->nullable();
        });
        Schema::create('enter_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->date('date')->nullable();
            $table->string('bound_number')->nullable();
            $table->string('manifest_bound_number')->nullable();
            $table->string('manifest_type_number')->nullable();
            $table->integer('manifest_year')->nullable();
            $table->string('customs_entry_center')->nullable();
        });
        Schema::create('warehouse_items', function (Blueprint $table) {
            $table->id();
            $table->double('quantity')->nullable();
            $table->double('custom_value')->nullable();
            $table->double('gross_weight')->nullable();
            $table->string('batch_number')->nullable();
            $table->unsignedBigInteger('location_line_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('enter_request_id')->nullable();
        });
        Schema::create('outbounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->date('date')->nullable();
        });
        Schema::create('outbound_warehouse_items', function (Blueprint $table) {
            $table->id();
            $table->double('quantity')->nullable();
            $table->double('custom_value')->nullable();
            $table->double('gross_weight')->nullable();
            $table->unsignedBigInteger('warehouse_item_id')->nullable();
            $table->unsignedBigInteger('outbound_id')->nullable();
        });
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
        });
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
        });
        Schema::create('location_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
        });

        $this->actingAs(new WarehouseReportUser(['id' => 1, 'name' => 'Report User']));
        $this->seedReportData();
    }

    public function test_it_aggregates_the_historical_balance_in_one_query_with_item_specific_dates(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('warehouses.report.products', [
            'from_date' => '2025-01-01',
            'to_date' => '2025-06-30',
        ]));

        $response->assertOk();
        $groups = $response->viewData('customerGroups');
        $firstItem = $groups->first()->items->firstWhere('id', 1);
        $secondItem = $groups->first()->items->firstWhere('id', 2);

        $this->assertSame(-10.0, (float) $firstItem->remaining_quantity);
        $this->assertSame(-100.0, (float) $firstItem->remaining_custom_value);
        $this->assertSame(-50.0, (float) $firstItem->remaining_gross_weight);
        $this->assertSame('2025-06-01', $firstItem->last_outbound_date);
        $this->assertSame(40.0, (float) $secondItem->remaining_quantity);
        $this->assertSame('2025-05-02', $secondItem->last_outbound_date);
        $this->assertCount(1, DB::getQueryLog());
        $response->assertSee('صرف زائد');
    }

    public function test_it_keeps_customer_warehouse_product_and_inbound_filters(): void
    {
        $response = $this->get(route('warehouses.report.products', [
            'from_date' => '2025-01-01',
            'to_date' => '2025-06-30',
            'customer_id' => 1,
            'warehouse_id' => 1,
            'product_id' => 1,
            'enter_request_id' => 1,
        ]));

        $response->assertOk();
        $groups = $response->viewData('customerGroups');

        $this->assertCount(1, $groups);
        $this->assertSame([1], $groups->first()->items->pluck('id')->all());
        $this->assertSame('W-1', $response->viewData('warehouse')->code);
    }

    private function seedReportData(): void
    {
        DB::table('customers')->insert([
            ['id' => 1, 'name' => 'Customer One', 'tax_number' => 'T-1'],
            ['id' => 2, 'name' => 'Customer Two', 'tax_number' => 'T-2'],
        ]);
        DB::table('products')->insert([
            ['id' => 1, 'name' => 'Product One', 'barcode' => 'P-1'],
            ['id' => 2, 'name' => 'Product Two', 'barcode' => 'P-2'],
        ]);
        DB::table('enter_requests')->insert([
            ['id' => 1, 'customer_id' => 1, 'status_id' => 4, 'date' => '2025-01-10', 'bound_number' => 'IN-1'],
            ['id' => 2, 'customer_id' => 1, 'status_id' => 5, 'date' => '2025-02-10', 'bound_number' => 'IN-2'],
            ['id' => 3, 'customer_id' => 2, 'status_id' => 7, 'date' => '2025-03-10', 'bound_number' => 'IN-3'],
        ]);
        DB::table('warehouses')->insert([
            ['id' => 1, 'code' => 'W-1'],
            ['id' => 2, 'code' => 'W-2'],
        ]);
        DB::table('warehouse_locations')->insert([
            ['id' => 1, 'warehouse_id' => 1],
            ['id' => 2, 'warehouse_id' => 2],
        ]);
        DB::table('location_lines')->insert([
            ['id' => 1, 'location_id' => 1],
            ['id' => 2, 'location_id' => 2],
        ]);
        DB::table('warehouse_items')->insert([
            ['id' => 1, 'quantity' => 100, 'custom_value' => 1000, 'gross_weight' => 500, 'batch_number' => 'B-1', 'location_line_id' => 1, 'product_id' => 1, 'enter_request_id' => 1],
            ['id' => 2, 'quantity' => 50, 'custom_value' => 500, 'gross_weight' => 250, 'batch_number' => 'B-2', 'location_line_id' => 2, 'product_id' => 2, 'enter_request_id' => 1],
            ['id' => 3, 'quantity' => 25, 'custom_value' => 250, 'gross_weight' => 125, 'batch_number' => 'B-3', 'location_line_id' => 1, 'product_id' => 1, 'enter_request_id' => 2],
            ['id' => 4, 'quantity' => 25, 'custom_value' => 250, 'gross_weight' => 125, 'batch_number' => 'B-4', 'location_line_id' => 1, 'product_id' => 1, 'enter_request_id' => 3],
            ['id' => 5, 'quantity' => 25, 'custom_value' => 250, 'gross_weight' => 125, 'batch_number' => 'B-5', 'location_line_id' => 2, 'product_id' => 1, 'enter_request_id' => 1],
        ]);
        DB::table('outbounds')->insert([
            ['id' => 1, 'status_id' => 3, 'date' => '2025-04-01'],
            ['id' => 2, 'status_id' => 4, 'date' => '2025-05-01'],
            ['id' => 3, 'status_id' => 6, 'date' => '2025-06-01'],
            ['id' => 4, 'status_id' => 7, 'date' => '2025-07-01'],
            ['id' => 5, 'status_id' => 5, 'date' => '2025-05-02'],
        ]);
        DB::table('outbound_warehouse_items')->insert([
            ['warehouse_item_id' => 1, 'outbound_id' => 1, 'quantity' => 10, 'custom_value' => 100, 'gross_weight' => 50],
            ['warehouse_item_id' => 1, 'outbound_id' => 2, 'quantity' => 20, 'custom_value' => 200, 'gross_weight' => 100],
            ['warehouse_item_id' => 1, 'outbound_id' => 3, 'quantity' => 90, 'custom_value' => 900, 'gross_weight' => 450],
            ['warehouse_item_id' => 1, 'outbound_id' => 4, 'quantity' => 5, 'custom_value' => 50, 'gross_weight' => 25],
            ['warehouse_item_id' => 2, 'outbound_id' => 5, 'quantity' => 10, 'custom_value' => 100, 'gross_weight' => 50],
        ]);
    }
}

class WarehouseReportUser extends GenericUser
{
    public function can($abilities, $arguments = []): bool
    {
        return true;
    }
}
