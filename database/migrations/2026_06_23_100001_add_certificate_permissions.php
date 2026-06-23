<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $manage = Permission::firstOrCreate(['name' => 'certificates.manage', 'guard_name' => 'web']);
        $viewOwn = Permission::firstOrCreate(['name' => 'certificates.view_own', 'guard_name' => 'web']);

        $superAdmin = Role::where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN)->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([$manage, $viewOwn]);
        }

        $staffMember = Role::firstOrCreate([
            'name' => RolePermissionSeeder::ROLE_STAFF_MEMBER,
            'guard_name' => 'web',
        ]);
        $staffMember->syncPermissions(['certificates.view_own']);
    }

    public function down(): void
    {
        Permission::whereIn('name', ['certificates.manage', 'certificates.view_own'])->delete();
        Role::where('name', RolePermissionSeeder::ROLE_STAFF_MEMBER)->delete();
    }
};
