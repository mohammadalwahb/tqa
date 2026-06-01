<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name'       => 'staff.manage_department',
            'guard_name' => 'web',
        ]);

        $role = Role::firstOrCreate([
            'name'       => RolePermissionSeeder::ROLE_DEPARTMENT_HEAD,
            'guard_name' => 'web',
        ]);

        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $role = Role::where('name', RolePermissionSeeder::ROLE_DEPARTMENT_HEAD)->first();

        if ($role) {
            $role->revokePermissionTo('staff.manage_department');
        }

        Permission::where('name', 'staff.manage_department')->delete();
    }
};
