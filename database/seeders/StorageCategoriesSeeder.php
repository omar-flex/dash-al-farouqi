<?php

namespace Database\Seeders;

use App\Models\StorageCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StorageCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StorageCategory::updateOrCreate(['name' => 'dry']);
        StorageCategory::updateOrCreate(['name' => 'cold']);
    }
}
