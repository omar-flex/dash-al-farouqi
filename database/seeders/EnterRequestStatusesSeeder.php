<?php

namespace Database\Seeders;

use App\Models\EnterRequestStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnterRequestStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Manifest office Draft'],
            ['name' => 'Car Check'],
            ['name' => 'WH Enter Product'],
            ['name' => 'Manifest Validation'],
            ['name' => 'Manifest Authorization'],
        ];

        foreach ($statuses as $status) {
            EnterRequestStatus::updateOrCreate($status);
        }

    }
}
