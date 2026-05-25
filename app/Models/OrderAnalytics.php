<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderAnalytics extends Model
{
    use HasFactory;

    protected $connection = 'analytics';
    protected $table = 'order_analytics';

    protected $fillable = [
        // Time dimension
        'report_date',
        'period_type',

        // Order volume
        'total_orders',
        'pending_orders',
        'confirmed_orders',
        'processing_orders',
        'shipped_orders',
        'delivered_orders',
        'cancelled_orders',
        'refunded_orders',

        // Revenue
        'gross_revenue',
        'total_discount',
        'net_revenue',
        'average_order_value',

        // Items
        'total_items_sold',
        'unique_products_sold',

        // Payment methods
        'card_payments',
        'upi_payments',
        'wallet_payments',
        'cod_payments',
        'emi_payments',
        'netbanking_payments',

        // Customers
        'registered_customer_orders',
        'guest_orders',
        'new_customers',
        'returning_customers',

        // Coupons
        'orders_with_coupon',
        'coupon_discount_total',

        // JSON snapshots
        'top_products',
        'top_categories',
    ];

    protected $casts = [
        'report_date'           => 'date',
        'gross_revenue'         => 'decimal:2',
        'total_discount'        => 'decimal:2',
        'net_revenue'           => 'decimal:2',
        'average_order_value'   => 'decimal:2',
        'coupon_discount_total' => 'decimal:2',
        'top_products'          => 'array',
        'top_categories'        => 'array',
    ];

    // ─── Query Scopes ───────────────────────────────────────────

    /**
     * Filter by period type.
     */
    public function scopeDaily(Builder $query): Builder
    {
        return $query->where('period_type', 'daily');
    }

    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('period_type', 'weekly');
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('period_type', 'monthly');
    }

    /**
     * Filter by date range.
     */
    public function scopeDateRange(Builder $query, Carbon|string $from, Carbon|string $to): Builder
    {
        return $query->whereBetween('report_date', [
            Carbon::parse($from)->toDateString(),
            Carbon::parse($to)->toDateString(),
        ]);
    }

    // ─── Aggregation Builder ────────────────────────────────────

    /**
     * Build (or update) a daily analytics snapshot for a given date.
     *
     * Usage:  OrderAnalytics::buildForDate('2026-05-13');
     */
    public static function buildForDate(Carbon|string $date): self
    {
        $date = Carbon::parse($date);
        $dateStr = $date->toDateString();

        // ── Core order metrics ──────────────────────────────────
        $orderMetrics = DB::table('orders')
            ->whereDate('placed_at', $dateStr)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN status = 'pending'    THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw("SUM(CASE WHEN status = 'confirmed'  THEN 1 ELSE 0 END) as confirmed_orders")
            ->selectRaw("SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders")
            ->selectRaw("SUM(CASE WHEN status = 'shipped'    THEN 1 ELSE 0 END) as shipped_orders")
            ->selectRaw("SUM(CASE WHEN status = 'delivered'  THEN 1 ELSE 0 END) as delivered_orders")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled'  THEN 1 ELSE 0 END) as cancelled_orders")
            ->selectRaw("SUM(CASE WHEN status = 'refunded'   THEN 1 ELSE 0 END) as refunded_orders")
            ->selectRaw('COALESCE(SUM(total_amount),    0) as gross_revenue')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as total_discount')
            ->selectRaw('COALESCE(SUM(final_amount),    0) as net_revenue')
            // Payment method breakdown
            ->selectRaw("SUM(CASE WHEN payment_method = 'card'       THEN 1 ELSE 0 END) as card_payments")
            ->selectRaw("SUM(CASE WHEN payment_method = 'upi'        THEN 1 ELSE 0 END) as upi_payments")
            ->selectRaw("SUM(CASE WHEN payment_method = 'wallet'     THEN 1 ELSE 0 END) as wallet_payments")
            ->selectRaw("SUM(CASE WHEN payment_method = 'cod'        THEN 1 ELSE 0 END) as cod_payments")
            ->selectRaw("SUM(CASE WHEN payment_method = 'emi'        THEN 1 ELSE 0 END) as emi_payments")
            ->selectRaw("SUM(CASE WHEN payment_method = 'netbanking' THEN 1 ELSE 0 END) as netbanking_payments")
            // Customer type
            ->selectRaw('SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as registered_customer_orders')
            ->selectRaw('SUM(CASE WHEN user_id IS NULL     THEN 1 ELSE 0 END) as guest_orders')
            // Coupon usage
            ->selectRaw('SUM(CASE WHEN coupon_code IS NOT NULL THEN 1 ELSE 0 END) as orders_with_coupon')
            ->selectRaw('COALESCE(SUM(CASE WHEN coupon_code IS NOT NULL THEN discount_amount ELSE 0 END), 0) as coupon_discount_total')
            ->first();

        $totalOrders = $orderMetrics->total_orders ?? 0;

        // ── Item-level metrics ──────────────────────────────────
        $itemMetrics = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereDate('orders.placed_at', $dateStr)
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_items_sold')
            ->selectRaw('COUNT(DISTINCT order_items.product_id) as unique_products_sold')
            ->first();

        // ── New vs returning customers ──────────────────────────
        $newCustomers = DB::table('orders')
            ->whereDate('placed_at', $dateStr)
            ->whereNotNull('user_id')
            ->whereRaw('user_id NOT IN (SELECT DISTINCT user_id FROM orders WHERE placed_at < ? AND user_id IS NOT NULL)', [$dateStr])
            ->distinct('user_id')
            ->count('user_id');

        $returningCustomers = DB::table('orders')
            ->whereDate('placed_at', $dateStr)
            ->whereNotNull('user_id')
            ->whereRaw('user_id IN (SELECT DISTINCT user_id FROM orders WHERE placed_at < ? AND user_id IS NOT NULL)', [$dateStr])
            ->distinct('user_id')
            ->count('user_id');

        // ── Top products ────────────────────────────────────────
        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereDate('orders.placed_at', $dateStr)
            ->groupBy('order_items.product_id', 'products.name')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->limit(10)
            ->select([
                'order_items.product_id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as qty'),
                DB::raw('SUM(order_items.total_price) as revenue'),
            ])
            ->get()
            ->toArray();

        // ── Top categories ──────────────────────────────────────
        $topCategories = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereDate('orders.placed_at', $dateStr)
            ->groupBy('products.category_id', 'categories.name')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->limit(10)
            ->select([
                'products.category_id',
                'categories.name',
                DB::raw('SUM(order_items.quantity) as qty'),
                DB::raw('SUM(order_items.total_price) as revenue'),
            ])
            ->get()
            ->toArray();

        // ── Upsert the snapshot ─────────────────────────────────
        return self::updateOrCreate(
            [
                'report_date' => $dateStr,
                'period_type' => 'daily',
            ],
            [
                'total_orders'               => $totalOrders,
                'pending_orders'             => $orderMetrics->pending_orders ?? 0,
                'confirmed_orders'           => $orderMetrics->confirmed_orders ?? 0,
                'processing_orders'          => $orderMetrics->processing_orders ?? 0,
                'shipped_orders'             => $orderMetrics->shipped_orders ?? 0,
                'delivered_orders'           => $orderMetrics->delivered_orders ?? 0,
                'cancelled_orders'           => $orderMetrics->cancelled_orders ?? 0,
                'refunded_orders'            => $orderMetrics->refunded_orders ?? 0,
                'gross_revenue'              => $orderMetrics->gross_revenue ?? 0,
                'total_discount'             => $orderMetrics->total_discount ?? 0,
                'net_revenue'                => $orderMetrics->net_revenue ?? 0,
                'average_order_value'        => $totalOrders > 0
                    ? round(($orderMetrics->net_revenue ?? 0) / $totalOrders, 2)
                    : 0,
                'total_items_sold'           => $itemMetrics->total_items_sold ?? 0,
                'unique_products_sold'       => $itemMetrics->unique_products_sold ?? 0,
                'card_payments'              => $orderMetrics->card_payments ?? 0,
                'upi_payments'               => $orderMetrics->upi_payments ?? 0,
                'wallet_payments'            => $orderMetrics->wallet_payments ?? 0,
                'cod_payments'               => $orderMetrics->cod_payments ?? 0,
                'emi_payments'               => $orderMetrics->emi_payments ?? 0,
                'netbanking_payments'        => $orderMetrics->netbanking_payments ?? 0,
                'registered_customer_orders' => $orderMetrics->registered_customer_orders ?? 0,
                'guest_orders'               => $orderMetrics->guest_orders ?? 0,
                'new_customers'              => $newCustomers,
                'returning_customers'        => $returningCustomers,
                'orders_with_coupon'         => $orderMetrics->orders_with_coupon ?? 0,
                'coupon_discount_total'      => $orderMetrics->coupon_discount_total ?? 0,
                'top_products'               => $topProducts,
                'top_categories'             => $topCategories,
            ],
        );
    }
}
