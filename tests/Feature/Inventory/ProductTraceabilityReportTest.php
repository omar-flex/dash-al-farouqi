<?php

namespace Tests\Feature\Inventory;

use App\Models\EnterRequestStatus;
use App\Models\OutboundStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductTraceabilityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_uses_document_dates_and_the_inventory_ledger_for_balances(): void
    {
        $this->signInForReport();
        $filters = $this->seedMovements();

        $response = $this->get(route('warehouses.traceability', $filters))
            ->assertOk()
            ->assertSee('2026/211/4/NEW')
            ->assertSee('OUT-FINAL')
            ->assertSee('W-OLD-H-A')
            ->assertDontSee('OUT-DRAFT')
            ->assertDontSee('OUT-FUTURE')
            ->assertDontSee('IN-DRAFT')
            ->assertDontSee('IN-OTHER-WAREHOUSE');

        $summary = $response->viewData('summary');
        $this->assertSame(16.875, (float) $summary->opening_qty);
        $this->assertSame(8.0, (float) $summary->inbound_qty);
        $this->assertSame(6.0, (float) $summary->outbound_qty);
        $this->assertSame(18.875, (float) $summary->closing_qty);
        $this->assertSame(8.0, (float) $summary->opening_other_qty);
        $this->assertNull($summary->inbound_other_qty);
        $this->assertSame(10.0, (float) $summary->outbound_other_qty);
        $this->assertSame(7.0, (float) $summary->closing_other_qty);

        $rows = $response->viewData('rows')->getCollection();
        $this->assertSame(['OUT-FINAL', 'OUT-LEGACY', null], $rows->pluck('outbound_reference')->all());
        $this->assertNull($rows->last()->other_quantity);
        $this->assertSame(9.0, (float) $rows[1]->other_quantity);
    }

    public function test_all_history_and_csv_keep_blank_other_quantity_and_protect_spreadsheet_cells(): void
    {
        $this->signInForReport();
        $filters = $this->seedMovements();
        unset($filters['from_date']);

        $response = $this->get(route('warehouses.traceability', $filters))->assertOk();
        $summary = $response->viewData('summary');
        $this->assertSame(0.0, (float) $summary->opening_qty);
        $this->assertSame(28.125, (float) $summary->inbound_qty);
        $this->assertSame(5, $response->viewData('rows')->total());

        $export = $this->get(route('warehouses.traceability', $filters + ['format' => 'csv', 'page' => 2]))
            ->assertOk()
            ->assertDownload('product-traceability-'.$filters['product_id'].'-2026-03-31.csv');

        $csv = $export->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = array_map(fn (string $line) => str_getcsv($line, escape: ''), explode("\n", rtrim($csv)));
        $this->assertCount(6, $lines);
        $newInbound = collect($lines)->first(fn (array $line) => $line[5] === '2026/211/4/NEW' && $line[6] === '');
        $this->assertSame("'=LOT-01", $newInbound[11]);
        $this->assertSame('', $newInbound[13]);
    }

    public function test_search_supports_name_and_exact_barcode_and_validates_the_date_range(): void
    {
        $this->signInForReport();
        $filters = $this->seedMovements();
        DB::table('products')->insert(['name' => 'Another product', 'barcode' => 'X-ABC-1']);

        $this->getJson(route('warehouses.traceability.products', ['q' => 'ABC-1']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $filters['product_id'])
            ->assertJsonCount(2, 'results');
        $this->getJson(route('warehouses.traceability.products', ['q' => 'لوحات']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $filters['product_id']);
        $this->getJson(route('warehouses.traceability.products', ['q' => '%']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
        $this->getJson(route('warehouses.traceability', [
            'product_id' => $filters['product_id'],
            'from_date' => '2026-04-01',
            'to_date' => '2026-03-31',
        ]))->assertUnprocessable()->assertJsonValidationErrors('from_date');
    }

    public function test_report_search_and_export_require_the_existing_report_permission(): void
    {
        $this->get(route('warehouses.traceability'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('warehouses.traceability'))->assertForbidden();
        $this->getJson(route('warehouses.traceability.products'))->assertForbidden();
        $this->get(route('warehouses.traceability', ['format' => 'csv']))->assertForbidden();
    }

    private function signInForReport(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('warehouses_report'));
        $this->actingAs($user);
    }

    private function seedMovements(): array
    {
        $unit = DB::table('unit_measures')->insertGetId(['name' => 'Pallet']);
        $product = DB::table('products')->insertGetId(['name' => 'لوحات تحكم', 'barcode' => 'ABC-1', 'unit_measure_id' => $unit]);
        $warehouse = DB::table('warehouses')->insertGetId(['code' => 'W-01']);
        $otherWarehouse = DB::table('warehouses')->insertGetId(['code' => 'W-02']);
        $hall = DB::table('warehouse_locations')->insertGetId(['code' => 'H-01', 'position' => 1, 'warehouse_id' => $warehouse]);
        $location = DB::table('location_lines')->insertGetId(['code' => 'A-01', 'location_id' => $hall]);
        $customer = DB::table('customers')->insertGetId(['name' => 'عميل تجريبي']);

        foreach ([EnterRequestStatus::DRAFT, EnterRequestStatus::VALIDATION, EnterRequestStatus::APPROVED] as $status) {
            DB::table('enter_request_statuses')->insert(['id' => $status, 'name' => 'Inbound '.$status]);
        }
        foreach ([OutboundStatus::DRAFT, OutboundStatus::AUTHORIZATION, OutboundStatus::APPROVED] as $status) {
            DB::table('outbound_statuses')->insert(['id' => $status, 'name' => 'Outbound '.$status]);
        }

        $old = $this->stock($product, $customer, $warehouse, $location, '2026-02-15', '2026/211/4/OLD', 20.125, 10);
        $new = $this->stock($product, $customer, $warehouse, $location, '2026-03-01', '2026/211/4/NEW', 8);
        $this->stock($product, $customer, $otherWarehouse, null, '2026-03-01', 'IN-OTHER-WAREHOUSE', 30);
        $this->stock($product, $customer, $warehouse, $location, '2026-03-01', 'IN-DRAFT', 90, status: EnterRequestStatus::DRAFT);

        $this->outbound($old, '2026-02-20', 'OUT-OPENING', 3.25, 2);
        $this->outbound($old, '2026-03-31', 'OUT-FINAL', 5, 1, OutboundStatus::AUTHORIZATION);
        $this->outbound($new, '2026-03-15', 'OUT-LEGACY', 1, 9);
        $this->outbound($old, '2026-03-03', 'OUT-DRAFT', 9, 1, OutboundStatus::DRAFT);
        $this->outbound($old, '2026-04-01', 'OUT-FUTURE', 9, 1);

        return [
            'product_id' => $product,
            'customer_id' => $customer,
            'warehouse_id' => $warehouse,
            'batch_number' => '=LOT-01',
            'from_date' => '2026-03-01',
            'to_date' => '2026-03-31',
        ];
    }

    private function stock(int $product, int $customer, int $warehouse, ?int $location, string $date, string $reference, float $quantity, ?float $other = null, int $status = EnterRequestStatus::APPROVED): int
    {
        $inbound = DB::table('enter_requests')->insertGetId([
            'date' => $date, 'bound_number' => $reference, 'status_id' => $status,
            'customer_id' => $customer, 'warehouse_id' => $warehouse,
        ]);

        return DB::table('warehouse_items')->insertGetId([
            'product_id' => $product, 'enter_request_id' => $inbound, 'location_line_id' => $location,
            'quantity' => $quantity, 'other_quantity' => $other, 'remaining_quantity' => 999,
            'batch_number' => '=LOT-01', 'level' => '1', 'pallet' => '0',
        ]);
    }

    private function outbound(int $stock, string $date, string $reference, float $quantity, ?float $other, int $status = OutboundStatus::APPROVED): void
    {
        $inbound = DB::table('warehouse_items')->where('id', $stock)->value('enter_request_id');
        $outbound = DB::table('outbounds')->insertGetId([
            'enter_request_id' => $inbound, 'date' => $date, 'outbound_number' => $reference, 'status_id' => $status,
        ]);
        DB::table('outbound_warehouse_items')->insert([
            'outbound_id' => $outbound, 'warehouse_item_id' => $stock,
            'quantity' => $quantity, 'other_quantity' => $other, 'location' => 'W-OLD-H-A',
        ]);
    }
}
