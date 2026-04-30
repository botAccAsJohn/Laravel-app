<?php

use Illuminate\Support\Number;

if (! function_exists('format_price')) {
    function format_price(int|float $amount): string
    {
        $currency = config('app.currency', 'USD');
        return Number::currency($amount, in: $currency);
    }
}

if (! function_exists('order_status_badge')) {
    function order_status_badge(string $status): string
    {
        return match ($status) {
            'pending'   => 'badge bg-warning text-dark',
            'paid'      => 'badge bg-info text-dark',
            'shipped'   => 'badge bg-primary',
            'delivered' => 'badge bg-success',
            default     => 'badge bg-secondary',
        };
    }
}

if (! function_exists('human_file_size')) {
    function human_file_size(int $bytes): string
    {
        return match (true) {
            $bytes >= 1_073_741_824 => number_format($bytes / 1_073_741_824, 2) . ' GB',
            $bytes >= 1_048_576     => number_format($bytes / 1_048_576, 2) . ' MB',
            $bytes >= 1_024         => number_format($bytes / 1_024, 2) . ' KB',
            default                 => $bytes . ' B',
        };
    }
}
