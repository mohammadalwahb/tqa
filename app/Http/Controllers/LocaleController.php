<?php

namespace App\Http\Controllers;

use App\Support\LocaleHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, LocaleHelper::SUPPORTED, true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        return redirect()->back();
    }
}
