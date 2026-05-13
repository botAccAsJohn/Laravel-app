<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminAlertRequest;
use App\Notifications\AdminManualAlert;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminAlertController extends Controller
{
    public function index(): View
    {
        return view('admin.alerts.create');
    }

    public function store(StoreAdminAlertRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Notification::route('mail', $validated['email'])
            ->notify(new AdminManualAlert($validated['subject'], $validated['message']));

        return redirect()->back()->with('success', 'Manual alert sent successfully to ' . $validated['email']);
    }
}
