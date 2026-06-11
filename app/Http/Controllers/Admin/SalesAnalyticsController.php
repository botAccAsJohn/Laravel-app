<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Gate};
use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;

class SalesAnalyticsController extends Controller
{
    private const CATEGORY_REVENUE_THRESHOLD = 0;


    public function export()
    {
        Gate::authorize('view_analytics');
        $orders = Order::with('user')
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->whereNull('orders.deleted_at')
            ->orderByDesc('placed_at')
            ->get();

        return Excel::download(
            new SalesExport($orders),
            'sales_report_' . now()->format('Y-m-d') . '.csv'
        );
    }


    public function index(Request $request)
    {
        Gate::authorize('view_analytics');

        $filterYear = $request->input('year');
        $filterMonth = $request->input('month');
        $filterDate = $request->input('date');
        $filterFrom = $request->input('from');
        $filterTo = $request->input('to');

        $driver = DB::connection()->getDriverName();
        $dateExpression = match ($driver) {
            'pgsql' => "TO_CHAR(placed_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', placed_at)",
            default => "DATE_FORMAT(placed_at, '%Y-%m')",
        };

        $monthlySalesRaw = DB::table('orders')
            ->selectRaw("
                {$dateExpression}                  AS month_key,
                SUM(final_amount)                  AS revenue,
                AVG(final_amount)                  AS average,
                COUNT(*)                           AS count
            ")
            ->whereNotIn('status', ['pending', 'cancelled'])
            ->whereNull('deleted_at')
            // ── when() date filters — each fires only when the param is present ──
            // whereYear()  → WHERE YEAR(placed_at)  = ?
            ->when($filterYear, fn($q) => $q->whereYear('placed_at', $filterYear))
            // whereMonth() → WHERE MONTH(placed_at) = ?
            ->when($filterMonth, fn($q) => $q->whereMonth('placed_at', $filterMonth))
            // whereDate()  → WHERE DATE(placed_at)  = '2026-05-01'
            ->when($filterDate, fn($q) => $q->whereDate('placed_at', $filterDate))
            // whereBetween → WHERE placed_at BETWEEN '…00:00:00' AND '…23:59:59'
            ->when($filterFrom && $filterTo, fn($q) => $q->whereBetween('placed_at', [
                $filterFrom . ' 00:00:00',
                $filterTo . ' 23:59:59',
            ]))
            ->groupByRaw($dateExpression)
            ->orderByRaw("{$dateExpression} ASC")
            ->get();

        // Reshape to match the view's keyed-collection expectation:
        //   ['2026-01' => ['revenue' => …, 'average' => …, 'count' => …], …]
        $monthlySales = $monthlySalesRaw->mapWithKeys(fn($row) => [
            $row->month_key => [
                'revenue' => (float) $row->revenue,
                'average' => (float) $row->average,
                'count' => (int) $row->count,
            ],
        ]);


        $topProducts = DB::table('order_items AS oi')
            ->select('p.name')
            ->selectRaw('SUM(oi.quantity)    AS quantity')
            ->selectRaw('SUM(oi.total_price) AS revenue')
            // INNER JOIN: orders must exist and not be soft-deleted
            ->join('orders AS o', function ($join) {
                $join->on('oi.order_id', '=', 'o.id')
                    ->whereNotIn('o.status', ['pending', 'cancelled'])
                    ->whereNull('o.deleted_at');
            })
            // INNER JOIN: product must exist and not be soft-deleted
            ->join('products AS p', function ($join) {
                $join->on('oi.product_id', '=', 'p.id')
                    ->whereNull('p.deleted_at');
            })
            ->groupBy('oi.product_id', 'p.name')
            ->orderByRaw('SUM(oi.quantity) DESC')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'name' => $row->name,
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
            ]);

        // ── 3. Top 10 Customers ───────────────────────────────────────────────
        $topCustomers = DB::table('orders AS o')
            ->select('u.name', 'u.email')
            ->selectRaw('SUM(o.final_amount) AS total_spent')
            ->selectRaw('COUNT(o.id)         AS order_count')
            // INNER JOIN: only registered users (guests excluded here)
            ->join('users AS u', 'o.user_id', '=', 'u.id')
            ->whereNotIn('o.status', ['pending', 'cancelled'])
            ->whereNull('o.deleted_at')
            ->groupBy('o.user_id', 'u.name', 'u.email')
            ->orderByRaw('SUM(o.final_amount) DESC')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'name' => $row->name ?? 'Unknown',
                'email' => $row->email ?? 'N/A',
                'total_spent' => (float) $row->total_spent,
                'order_count' => (int) $row->order_count,
            ]);

        // ── 4. Sales by Category ──────────────────────────────────────────────

        $salesByCategoryRaw = DB::table('order_items AS oi')
            ->selectRaw("COALESCE(c.name, 'Uncategorized') AS category_name")
            ->selectRaw('SUM(oi.quantity * oi.unit_price) AS revenue')
            ->selectRaw('SUM(oi.quantity)                 AS quantity')
            ->selectRaw('COUNT(DISTINCT oi.order_id)      AS order_count')
            ->join('orders AS o', function ($join) {
                $join->on('oi.order_id', '=', 'o.id')
                    ->whereNotIn('o.status', ['pending', 'cancelled'])
                    ->whereNull('o.deleted_at');
            })
            ->join('products AS p', function ($join) {
                $join->on('oi.product_id', '=', 'p.id')
                    ->whereNull('p.deleted_at');
            })
            ->leftJoin('categories AS c', 'p.category_id', '=', 'c.id')
            ->groupByRaw("c.id, COALESCE(c.name, 'Uncategorized')")
            // ── when() date filters — mirrors the monthly sales filters ──────
            // Replaces four standalone if-blocks. Each when() receives the filter
            // value as its first argument and only calls the closure when truthy.
            ->when($filterYear, fn($q) => $q->whereYear('o.placed_at', $filterYear))
            ->when($filterMonth, fn($q) => $q->whereMonth('o.placed_at', $filterMonth))
            ->when($filterDate, fn($q) => $q->whereDate('o.placed_at', $filterDate))
            ->when($filterFrom && $filterTo, fn($q) => $q->whereBetween('o.placed_at', [
                $filterFrom . ' 00:00:00',
                $filterTo . ' 23:59:59',
            ]))
            // ── havingRaw(): post-GROUP-BY revenue threshold filter ─────────────
            // HAVING is applied after grouping; WHERE cannot filter on aggregates.
            ->havingRaw(
                'SUM(oi.quantity * oi.unit_price) > ?',
                [self::CATEGORY_REVENUE_THRESHOLD]
            )
            ->orderByRaw('SUM(oi.quantity * oi.unit_price) DESC')
            ->get();

        // Reshape to match the view's keyed-collection expectation:
        //   ['Electronics' => ['revenue' => …, 'quantity' => …], …]
        $salesByCategory = $salesByCategoryRaw->mapWithKeys(fn($row) => [
            $row->category_name => [
                'revenue' => (float) $row->revenue,
                'quantity' => (int) $row->quantity,
                'order_count' => (int) $row->order_count,
            ],
        ]);

        return view('admin.analytics.index', compact(
            'monthlySales',
            'topProducts',
            'topCustomers',
            'salesByCategory'
        ));
    }
}
