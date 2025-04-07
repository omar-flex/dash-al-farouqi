<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $permissions = [
            'warehouses',
            'locations',
            'enter_requests',
            'customers',
            'products',
            'outbounds',
            'companies'
        ];

        $cans = ['list_', 'add_', 'edit_', 'delete_'];

        foreach ($permissions as $permission) {
            foreach ($cans as $can) {
                $permission_new = Permission::updateOrcreate(['name' => $can . $permission]);
                Role::findByName('administrator')->givePermissionTo($permission_new);
            }
        }
    }
}
