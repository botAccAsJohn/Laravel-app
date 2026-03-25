<?php

namespace App\Services;

class AdminHelper
{
    public function panelName(): string
    {
        return config('admin.name', 'Admin Panel');
    }

    public function greeting(): string
    {
        return 'Welcome, ' . auth()->user()->name;
    }
}
