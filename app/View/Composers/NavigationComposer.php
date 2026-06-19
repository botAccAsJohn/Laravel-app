<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Services\CartService;

class NavigationComposer
{
    public function compose(View $view): void
    {
        // ── 1. Guard & User (explicit, no helper dependency) ────────
        $isImpersonatingSession = session()->has('impersonator_id');
        $activeGuard = ($isImpersonatingSession || !Auth::guard('admin')->check()) ? 'web' : 'admin';
        $activeUser  = Auth::guard($activeGuard)->user();
        $isGuest     = !$activeUser;

        // ── 2. Impersonation (safe: requires both session + auth) ───
        $isImpersonating = session()->has('impersonator_id') && $activeUser;
        $impersonatedUser = $isImpersonating ? $activeUser : null;
        // if impersonating, the real admin is in session 'impersonator_id'
        $impersonatingAdmin = null;
        if ($isImpersonating) {
            $impersonatingAdmin = \App\Models\Admin::find(session('impersonator_id'));
        }

        // ── 3. Admin Menu Access ────────────────────────────────────
        $canManageAdmin = $activeUser && $activeUser->canAny([
            'view_admin_dashboard', 'manage_users', 'view_analytics',
            'manage_reports', 'send_alerts', 'view_logs', 'manage_products',
        ]);

        // ── 4. Cart Count (Redis-safe) ──────────────────────────────
        $cartCount = rescue(function () {
            $cartService = app(CartService::class);
            $cartKey = $cartService->resolveCartKey();
            $rawCart = $cartService->get($cartKey) ?? [];
            return count(array_filter(
                $rawCart,
                fn($k) => !str_starts_with($k, '_'),
                ARRAY_FILTER_USE_KEY
            ));
        }, 0);

        // ── 5. Customer/Guest Nav Items ─────────────────────────────
        $navItems = collect([
            [
                'label'         => __('common.products'),
                'route'         => 'products.index',
                'activePattern' => 'products.*',
                'visible'       => true,
            ],
            [
                'label'         => __('common.cart'),
                'route'         => 'cart.index',
                'activePattern' => 'cart.*',
                'visible'       => $isGuest || $activeGuard === 'web',
                'badge'         => ['count' => $cartCount > 99 ? '99+' : $cartCount, 'show' => $cartCount > 0],
            ],
            [
                'label'         => __('common.recently_viewed'),
                'route'         => 'recently.index',
                'activePattern' => 'recently.*',
                'visible'       => $isGuest || $activeGuard === 'web',
            ],
            [
                'label'         => __('common.orders'),
                'route'         => 'orders.index',
                'activePattern' => 'orders.*',
                'visible'       => $activeGuard === 'web' && $activeUser && $activeUser->hasVerifiedEmail(),
            ],
        ])->filter(fn ($item) => $item['visible'])->values();

        // ── 6. Admin Nav Items (pre-filtered by permission) ─────────
        $adminItems = $canManageAdmin
            ? collect([
                ['permission' => 'view_admin_dashboard', 'label' => __('common.admin_panel'),       'route' => 'admin.dashboard',        'activePattern' => 'admin.dashboard'],
                ['permission' => 'manage_users',         'label' => 'Users',                        'route' => 'admin.users.index',      'activePattern' => 'admin.users.*'],
                ['permission' => 'manage_products',      'label' => 'Export Products',              'route' => 'admin.products.export',  'activePattern' => 'admin.products.export'],
                ['permission' => 'view_analytics',       'label' => __('admin.sales_analytics'),    'route' => 'analytics.index',        'activePattern' => 'analytics.*'],
                ['permission' => 'manage_reports',       'label' => __('admin.reports_manager'),     'route' => 'admin.reports.index',    'activePattern' => 'admin.reports.*'],
                ['permission' => 'send_alerts',          'label' => 'Alerts',                       'route' => 'admin.alerts.index',     'activePattern' => 'admin.alerts.*'],
                ['permission' => 'view_logs',            'label' => __('common.logs'),              'route' => 'admin.logs.index',       'activePattern' => 'admin.logs.*'],
              ])->filter(fn ($item) => $activeUser->can($item['permission']))->values()
            : collect();

        // ── 7. Supported Locales ────────────────────────────────────
        $supportedLocales = ['en', 'hi', 'ar'];

        $view->with(compact(
            'activeGuard', 'activeUser', 'isGuest', 'isImpersonating', 'impersonatedUser', 'impersonatingAdmin',
            'canManageAdmin', 'navItems', 'adminItems', 'supportedLocales'
        ));
    }
}
