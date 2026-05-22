<?php

namespace App\Http\Controllers;

use App\Http\Requests\EvaluationPeriodRequest;
use App\Models\EvaluationPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluationPeriodController extends Controller
{
    public function index(): View
    {
        $periods = EvaluationPeriod::orderByDesc('start_date')->get();
        return view('periods.index', compact('periods'));
    }

    public function create(): View
    {
        return view('periods.form', ['period' => new EvaluationPeriod()]);
    }

    public function store(EvaluationPeriodRequest $request): RedirectResponse
    {
        EvaluationPeriod::create($request->validated());
        return redirect()->route('periods.index')->with('success', 'Evaluation period created.');
    }

    public function edit(EvaluationPeriod $period): View
    {
        return view('periods.form', compact('period'));
    }

    public function update(EvaluationPeriodRequest $request, EvaluationPeriod $period): RedirectResponse
    {
        $period->update($request->validated());
        return redirect()->route('periods.index')->with('success', 'Period updated.');
    }

    public function destroy(EvaluationPeriod $period): RedirectResponse
    {
        $period->delete();
        return redirect()->route('periods.index')->with('success', 'Period deleted.');
    }
}
