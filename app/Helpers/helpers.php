<?php

use App\Services\AnalyticsService;
use Illuminate\Support\Number;
use Illuminate\Support\Stringable;
use App\Models\Admin;
use App\Models\User;

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

// if (!function_exists('analytics')) {
//     function analytics(): AnalyticsService
//     {
//         return app(AnalyticsService::class);
//     }
// }


if (!function_exists('human_file_size')) {
    function human_file_size($bytes)
    {
        return Number::fileSize($bytes);
    }
}

if (!function_exists('meta_description')){
    function meta_description(
        string $description,
        ?string $productName = null,
        bool $debug = false,
    ): string {
        return str($description)
            ->stripTags()
            ->pipe(fn (Stringable $s) => $s->replaceMatches('/\s+/', ' '))
            ->whenStartsWith($productName ?? '', fn (Stringable $s) => $s->after($productName)->ltrim(' :-'))
            ->whenEndsWith('...', fn (Stringable $s) => $s->beforeLast('...')->append('…'))
            ->whenContains(
                ['sale', 'discount', 'offer'],
                fn (Stringable $s) => $s->prepend('🏷️ ')
            )
            ->when($debug, fn (Stringable $s) => $s->dump())
            ->limit(160)
            ->trim()
            ->value();
    }
}

class CurrencyHelper
{
    public static function currencyAbbreviated($amount, $currency = null, $precision = 1)
    {

        if (is_null($currency)) {
            $currency = config('admin.currency_code')[app()->getLocale()]['code'];
        }

        $absAmount = abs($amount);

        if ($absAmount >= 1000000) {
            $abbreviated = round($amount / 1000000, $precision) . 'M';
        } elseif ($absAmount >= 1000) {
            $abbreviated = round($amount / 1000, $precision) . 'K';
        } else {
            return Number::currency($amount, in: $currency);
        }

        $symbol = self::getCurrencySymbol($currency);
        return $symbol.$abbreviated;
    }

    private static function getCurrencySymbol($currency)
    {
        return match ($currency) {
            'USD' => '$',
            'EUR' => '€',
            'INR' => '₹',
            'GBP' => '£',
            'SAR' => 'ر.س',
            default => $currency,
        };
    }
}
