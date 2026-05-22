<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollegeRequest;
use App\Models\College;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CollegeController extends Controller
{
    public function index(): View
    {
        $colleges = College::withCount(['departments', 'staffMembers'])->latest()->get();

        return view('colleges.index', compact('colleges'));
    }

    public function create(): View
    {
        return view('colleges.form', ['college' => new College()]);
    }

    public function store(CollegeRequest $request): RedirectResponse
    {
        College::create($request->validated());

        return redirect()->route('colleges.index')->with('success', 'College created successfully.');
    }

    public function edit(College $college): View
    {
        return view('colleges.form', compact('college'));
    }

    public function update(CollegeRequest $request, College $college): RedirectResponse
    {
        $college->update($request->validated());

        return redirect()->route('colleges.index')->with('success', 'College updated successfully.');
    }

    public function destroy(College $college): RedirectResponse
    {
        $college->delete();

        return redirect()->route('colleges.index')->with('success', 'College deleted successfully.');
    }
}
