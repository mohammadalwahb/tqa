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
            'name'       => 'evaluations.manage',
            'guard_name' => 'web',
        ]);

        $superAdmin = Role::findByName(RolePermissionSeeder::ROLE_SUPER_ADMIN, 'web');
        $superAdmin?->givePermissionTo($permission);
    }

    public function down(): void
    {
        $permission = Permission::findByName('evaluations.manage', 'web');
        $permission?->delete();
    }
};
