<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\User;
use App\Services\Evaluations\SuperAdminEvaluationAssignmentService;

class EvaluationPolicy
{
    public function __construct(
        private readonly SuperAdminEvaluationAssignmentService $superAdminEvaluations,
    ) {
    }
    public function viewAny(User $user): bool
    {
        return $user->can('evaluations.submit') || $user->can('evaluations.view_all');
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        if ($user->can('evaluations.view_all')) {
            return true;
        }

        if ($user->isSuperAdmin() && $this->superAdminEvaluations->isSharedSuperAdminEvaluation($evaluation)) {
            return true;
        }

        return (int) $evaluation->evaluator_user_id === (int) $user->id;
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        if ($user->can('evaluations.manage')) {
            return true;
        }

        $isSharedSuperAdmin = $user->isSuperAdmin()
            && $this->superAdminEvaluations->isSharedSuperAdminEvaluation($evaluation);

        if (! $isSharedSuperAdmin && (int) $evaluation->evaluator_user_id !== (int) $user->id) {
            return false;
        }

        if ($evaluation->isSubmitted()) {
            return false;
        }

        $period = $evaluation->period;

        if ($isSharedSuperAdmin && $user->isSuperAdmin()) {
            return (bool) $period;
        }

        return $period && $period->isOpen();
    }
}
