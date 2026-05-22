<?php

namespace App\Services\Auth;

use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

class GoogleAuthService
{
    /**
     * Verify whether the supplied email belongs to one of the allowed domains.
     */
    public function emailIsAllowed(string $email): bool
    {
        $email = mb_strtolower(trim($email));

        if (! str_contains($email, '@')) {
            return false;
        }

        $domain = mb_strtolower(Str::after($email, '@'));

        return in_array($domain, $this->allowedDomains(), true);
    }

    /**
     * @return array<int, string>
     */
    public function allowedDomains(): array
    {
        return array_map('mb_strtolower', config('tqa.allowed_email_domains', []));
    }

    /**
     * Find an existing user/staff for the Google identity, or create a
     * minimal user record. Linking and login-time guards are enforced here.
     */
    public function loginOrThrow(SocialiteUser $socialUser): User
    {
        $email = mb_strtolower((string) $socialUser->getEmail());

        if (! $this->emailIsAllowed($email)) {
            throw new RuntimeException('Your email domain is not allowed to sign in.');
        }

        return DB::transaction(function () use ($socialUser, $email) {
            $staff = StaffMember::query()->where('email', $email)->first();

            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $user = new User();
                $user->email = $email;
            }

            $user->name             = $user->name ?: ($socialUser->getName() ?: $email);
            $user->google_id        = $socialUser->getId();
            $user->avatar_url       = $socialUser->getAvatar();
            $user->email_verified_at = $user->email_verified_at ?: now();
            $user->is_active        = $user->is_active ?? true;
            $user->last_login_at    = now();

            if ($staff && ! $user->staff_member_id) {
                $user->staff_member_id = $staff->id;
            }

            $user->save();

            if ($staff && ! $staff->user_id) {
                $staff->user_id = $user->id;
                $staff->save();
            }

            if (! $user->is_active) {
                throw new RuntimeException('Your account is disabled. Please contact the administrator.');
            }

            return $user;
        });
    }
}
