<?php

namespace App\Services\Staff;

use App\Models\College;
use App\Models\CommitteeMember;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StaffPurgeService
{
    public function purgeAllPermanently(): int
    {
        return DB::transaction(function () {
            EvaluationAnswer::query()->delete();

            Evaluation::query()->withTrashed()->forceDelete();

            CommitteeMember::query()->update(['staff_member_id' => null]);

            College::query()->update(['dean_staff_id' => null]);
            Department::query()->update([
                'head_staff_id'                => null,
                'quality_coordinator_staff_id' => null,
            ]);

            User::query()->update(['staff_member_id' => null]);

            $count = StaffMember::query()->withTrashed()->count();

            StaffMember::query()->withTrashed()->forceDelete();

            return $count;
        });
    }
}
