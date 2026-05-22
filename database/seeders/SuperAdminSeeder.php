<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $emails = (array) config('tqa.super_admin_emails', []);
        $allowedDomains = (array) config('tqa.allowed_email_domains', []);

        foreach ($emails as $email) {
            $email = mb_strtolower(trim($email));
            if ($email === '') {
                continue;
            }

            $domain = Str::after($email, '@');
            if (! in_array($domain, $allowedDomains, true)) {
                $this->command?->warn("Skipping {$email}: domain @{$domain} is not in ALLOWED_EMAIL_DOMAINS.");
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'      => Str::title(str_replace(['.', '_'], ' ', Str::before($email, '@'))),
                    'password'  => Hash::make(Str::random(40)),
                    'is_active' => true,
                ]
            );

            if (! $user->hasRole(RolePermissionSeeder::ROLE_SUPER_ADMIN)) {
                $user->assignRole(RolePermissionSeeder::ROLE_SUPER_ADMIN);
                $this->command?->info("Granted Super Admin role to {$email}.");
            }
        }
    }
}
