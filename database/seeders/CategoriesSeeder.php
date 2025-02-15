<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\UnitMeasure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $categories[] = [
            'name_en' => 'Dried Materials',
            'name_ar' => 'تخزين مواد جافة',
            'type' => 'service',
        ];

        $categories[] = [
            'name_en' => 'Refrigerated Storage',
            'name_ar' => 'تخزين مواد مبردة',
            'type' => 'service',
        ];

        foreach ($categories as $category) {
            Category::updateOrcreate($category);
        }

    }
}
