<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        if (Auth::guard('admin')->check() && !is_impersonating()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('products.index');
    }
}
