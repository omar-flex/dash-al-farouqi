<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate([
            'name' => 'Administrator',
            'email' => 'administrator@gmail.com'],
            ['password' => Hash::make('administrator'),
                'email_verified_at' => now(),
            ]);

    }
}
