<?php

namespace Database\Seeders;

use App\Models\UnitMeasure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitMeasuresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitMeasures = [
            'ea', 'Unit', 'PCS', 'Box', 'CTN', 'PKG', 'PLT', 'Kg', 'g', 'TON', 'CBM', 'SQM', 'L', 'ML', 'm',
            'cm', 'ft', 'in', 'Set', 'Roll', 'Pair', 'Bundle', 'CRT', 'Motor'
        ];

        foreach ($unitMeasures as $unitMeasure) {
            UnitMeasure::updateOrcreate(['name' => $unitMeasure]);
        }

    }
}
