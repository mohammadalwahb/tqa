<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Services\Evaluations\SuperAdminEvaluationAssignmentService;
use Illuminate\Console\Command;

class SyncSuperAdminEvaluationsCommand extends Command
{
    protected $signature = 'evaluations:sync-super-admin
                            {--committee= : Limit to a single committee ID}';

    protected $description = 'Create draft Super Admin evaluations for staff when forms include Super Admin questions';

    public function handle(SuperAdminEvaluationAssignmentService $service): int
    {
        if ($committeeId = $this->option('committee')) {
            $committee = Committee::query()->where('is_active', true)->find($committeeId);
            $created   = $committee ? $service->syncForCommittee($committee) : 0;
        } else {
            $created = $service->syncAllActiveCommittees();
        }

        $this->info("Created {$created} Super Admin evaluation assignment(s).");

        return self::SUCCESS;
    }
}
