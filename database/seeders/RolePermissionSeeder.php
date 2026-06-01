<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public const ROLE_SUPER_ADMIN          = 'Super Admin';
    public const ROLE_QUALITY_COORDINATOR  = 'Quality College Coordinator';
    public const ROLE_LOCAL_COMMITTEE      = 'Local Committee Member';
    public const ROLE_HD_COMMITTEE         = 'HD Committee Member';
    public const ROLE_DEPARTMENT_HEAD      = 'Department Head';

    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        $permissions = [
            'users.manage',
            'roles.manage',
            'colleges.manage',
            'departments.manage',
            'staff.manage',
            'staff.manage_department',
            'staff.import',
            'staff_status.manage',
            'staff_options.manage',
            'coordinators.manage',
            'org_roles.manage',
            'periods.manage',
            'forms.manage',
            'questions.manage',
            'committees.manage',
            'committees.create_local',
            'committees.create_hd',
            'evaluations.submit',
            'evaluations.view_own',
            'evaluations.view_all',
            'evaluations.manage',
            'reports.view',
            'reports.export',
            'dashboard.view',
            'activity_log.view',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => self::ROLE_SUPER_ADMIN, 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $qualityCoord = Role::firstOrCreate(['name' => self::ROLE_QUALITY_COORDINATOR, 'guard_name' => 'web']);
        $qualityCoord->syncPermissions([
            'dashboard.view',
            'committees.manage',
            'committees.create_local',
            'committees.create_hd',
            'evaluations.submit',
            'evaluations.view_own',
            'evaluations.view_all',
            'reports.view',
            'reports.export',
        ]);

        $local = Role::firstOrCreate(['name' => self::ROLE_LOCAL_COMMITTEE, 'guard_name' => 'web']);
        $local->syncPermissions([
            'dashboard.view',
            'evaluations.submit',
            'evaluations.view_own',
        ]);

        $hd = Role::firstOrCreate(['name' => self::ROLE_HD_COMMITTEE, 'guard_name' => 'web']);
        $hd->syncPermissions([
            'dashboard.view',
            'evaluations.submit',
            'evaluations.view_own',
        ]);

        $departmentHead = Role::firstOrCreate(['name' => self::ROLE_DEPARTMENT_HEAD, 'guard_name' => 'web']);
        $departmentHead->syncPermissions([
            'staff.manage_department',
            'evaluations.view_own',
        ]);
    }
}
