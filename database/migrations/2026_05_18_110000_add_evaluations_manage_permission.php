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

        $superAdmin = Role::firstOrCreate([
            'name'       => RolePermissionSeeder::ROLE_SUPER_ADMIN,
            'guard_name' => 'web',
        ]);

        if (! $superAdmin->hasPermissionTo($permission)) {
            $superAdmin->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $permission = Permission::query()
            ->where('name', 'evaluations.manage')
            ->where('guard_name', 'web')
            ->first();

        $permission?->delete();
    }
};
