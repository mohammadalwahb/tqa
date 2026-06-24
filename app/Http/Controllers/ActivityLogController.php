<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogIndexRequest;
use App\Services\ActivityLog\ActivityLogQueryService;
use App\Services\ActivityLog\ActivitySubjectPresenter;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(
        ActivityLogIndexRequest $request,
        ActivityLogQueryService $queries,
        ActivitySubjectPresenter $presenter,
    ): View {
        $activities = $queries->paginate($request->filters());
        $presenter->enrich($activities->getCollection());

        return view('activity_log.index', [
            'activities'   => $activities,
            'filters'      => $request->filters(),
            'subjectTypes' => ActivitySubjectPresenter::subjectTypeOptions(),
            'events'       => ['created', 'updated', 'deleted'],
        ]);
    }
}
