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
            'customs-supervisor',
            'warehouse-manager',
            'senior-management',
        ];


        foreach ($roles as $role ) {
            Role::create(['name' => $role]);
        }

        User::find(1)->assignRole('administrator');
    }
}
