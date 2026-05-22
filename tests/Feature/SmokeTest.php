<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('redirects guests to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('renders the login page', function () {
    $this->get('/login')->assertOk()->assertSee('Sign in');
});

it('renders the dashboard for super admin', function () {
    $admin = User::role('Super Admin')->firstOrFail();
    $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Dashboard');
});

it('forbids non-super-admins from reaching the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('Quality College Coordinator');
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($user->fresh())->get('/dashboard')->assertStatus(403);
});

it('routes /home according to role', function () {
    $admin = User::role('Super Admin')->firstOrFail();
    $this->actingAs($admin)->get('/home')->assertRedirect('/dashboard');

    $coord = User::factory()->create();
    $coord->assignRole('Quality College Coordinator');
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($coord->fresh())->get('/home')->assertRedirect('/committees');
});

it('renders colleges, departments, staff, periods, forms and reports pages', function () {
    $admin = User::role('Super Admin')->firstOrFail();
    $routes = ['/colleges', '/departments', '/staff', '/staff-options', '/periods', '/forms', '/reports', '/users', '/coordinators', '/org-roles', '/activity-log', '/committees'];
    foreach ($routes as $r) {
        $this->actingAs($admin)->get($r)->assertOk();
    }
});
