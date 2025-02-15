<?php

namespace Database\Seeders;

use App\Models\LocationLine;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        Schema::disableForeignKeyConstraints();
        DB::table('warehouses')->truncate();
        DB::table('location_lines')->truncate();
        DB::table('warehouse_locations')->truncate();
        Schema::enableForeignKeyConstraints();

        $warehouses = ['B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7'];
        $locations = ['L1', 'L2', 'L3', 'L4', 'L5', 'L6', 'L7', 'L8', 'L9', 'R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'R7', 'R8', 'R9'];
        foreach ($warehouses as $warehouse) {
            $warehouse = Warehouse::updateOrcreate(['name' => 'Warehous ' . $warehouse, 'code' => $warehouse]);
            foreach ($locations as $location) {
                $location = WarehouseLocation::updateOrcreate([
                    'name' => 'Location ' . $location,
                    'code' => $location,
                    'position' => Str::contains($location, 'L') ? 'L' : 'R',
                    'warehouse_id' => $warehouse->id,
                ]);
                for ($x = 1; $x <= 3; $x++) {
                    LocationLine::updateOrcreate([
                        'code' => 'C' . $x,
                        'location_id' => $location->id,
                        'capacity' => 64,
                        'category_id' => 1
                    ]);
                }
            }

        }

    }
}
