<?php

namespace Database\Seeders;

use App\Models\EnterRequestStatus;
use App\Models\OutboundStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OutboundStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Manifest office Draft'],
            ['name' => 'Car Check'],
            ['name' => 'WH Release Products'],
            ['name' => 'Manifest Validation'],
            ['name' => 'Manifest Authorization'],
            ['name' => 'Needs Revision'],
            ['name' => 'Approved'],
        ];

        foreach ($statuses as $status) {
            OutboundStatus::updateOrCreate($status);
        }
    }
}
