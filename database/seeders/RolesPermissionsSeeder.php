<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'administrator',
            'Customs Supervisor',
            'Warehouse Manager',
            'Senior Management',
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role]);
        }

        User::find(1)->assignRole('administrator');
    }
}
