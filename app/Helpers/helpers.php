<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Number;

if (!function_exists('format_price')) {
    function format_price(int|float $amount): string
    {
        return Number::currency($amount);
    }
}

if (!function_exists('order_status_badge')) {
    function order_status_badge(string $status): string
    {
        return match ($status) {
            'pending' => 'badge bg-warning text-dark',
            'paid' => 'badge bg-info text-dark',
            'shipped' => 'badge bg-primary',
            'delivered' => 'badge bg-success',
            default => 'badge bg-secondary',
        };
    }
}

if (!function_exists('human_file_size')) {
    function human_file_size(int $bytes): string
    {
        return match (true) {
            $bytes >= 1_073_741_824 => number_format($bytes / 1_073_741_824, 2) . ' GB',
            $bytes >= 1_048_576 => number_format($bytes / 1_048_576, 2) . ' MB',
            $bytes >= 1_024 => number_format($bytes / 1_024, 2) . ' KB',
            default => $bytes . ' B',
        };
    }
}

if (!function_exists('is_impersonating')) {
    function is_impersonating(): bool
    {
        return session()->has('impersonator_id');
    }
}


if (!function_exists('impersonated_user')) {
    function impersonated_user(): ?User
    {
        if (!is_impersonating()) {
            return null;
        }

        /** @var User|null */
        return auth()->guard('web')->user();
    }
}


if (!function_exists('impersonating_admin')) {
    function impersonating_admin(): ?Admin
    {
        $adminId = session('impersonator_id');

        if (!$adminId) {
            return null;
        }

        return Admin::find($adminId);
    }
}

