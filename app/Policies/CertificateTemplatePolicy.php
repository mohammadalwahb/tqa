<?php

namespace App\Policies;

use App\Models\CertificateTemplate;
use App\Models\User;

class CertificateTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('certificates.manage');
    }

    public function view(User $user, CertificateTemplate $template): bool
    {
        return $user->can('certificates.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('certificates.manage');
    }

    public function update(User $user, CertificateTemplate $template): bool
    {
        return $user->can('certificates.manage');
    }

    public function delete(User $user, CertificateTemplate $template): bool
    {
        return $user->can('certificates.manage');
    }
}
