<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Session, Auth};

class LocaleController extends Controller
{
    /**
     * Switch the application locale at runtime.
     * Stores preference in session and user profile (if logged in).
     */
    public function switch(Request $request)
    {
        $lang = $request->input('lang');

        // Supported languages (English, Hindi, and Arabic for RTL support)
        $supported = ['en', 'hi', 'ar'];

        if (in_array($lang, $supported)) {
            Session::put('locale', $lang);

            // Persist preference to the authenticated user
            if (Auth::check()) {
                $user = Auth::user();
                $user->preferred_locale = $lang;
                $user->save();
            }
        }

        return back();
    }
}
