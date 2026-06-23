<?php

namespace App\Services\Evaluations;

use App\Models\Evaluation;
use App\Models\EvaluationQuestion;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class EvaluationQuestionVisibilityService
{
    private ?int $superAdminRoleId = null;

    public function isSuperAdminSharedEvaluation(Evaluation $evaluation): bool
    {
        return app(SuperAdminEvaluationAssignmentService::class)
            ->isSharedSuperAdminEvaluation($evaluation);
    }

    public function usesSuperAdminQuestionScope(Evaluation $evaluation, ?string $from = null): bool
    {
        if ($from === 'super-admin') {
            return true;
        }

        $user = auth()->user();

        return $user?->isSuperAdmin() && $this->isSuperAdminSharedEvaluation($evaluation);
    }

    /**
     * @param  Collection<int, EvaluationQuestion>  $questions
     * @return Collection<int, EvaluationQuestion>
     */
    public function filterForSuperAdmin(Collection $questions): Collection
    {
        $roleId = $this->superAdminRoleId();

        if (! $roleId) {
            return collect();
        }

        return $questions
            ->filter(fn (EvaluationQuestion $question) => $question->visibleToRoles
                ->pluck('id')
                ->contains($roleId))
            ->values();
    }

    private function superAdminRoleId(): ?int
    {
        if ($this->superAdminRoleId !== null) {
            return $this->superAdminRoleId;
        }

        $this->superAdminRoleId = Role::query()
            ->where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN)
            ->where('guard_name', 'web')
            ->value('id');

        return $this->superAdminRoleId ? (int) $this->superAdminRoleId : null;
    }
}
