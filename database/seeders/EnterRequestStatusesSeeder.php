<?php

namespace Database\Seeders;

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
            ['id' => 1, 'name' => 'Manifest office Draft'],
            ['id' => 2, 'name' => 'Manifest office Confirming'],
        ];

        DB::table('enter_request_statuses')->insert($statuses);


    }
}
