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
    // ── Revenue threshold for the HAVING clause ───────────────────────────────
    // Only categories whose aggregated revenue exceeds this value are included
    // in the salesByCategory report. Tune per business requirement.
    private const CATEGORY_REVENUE_THRESHOLD = 0;

    /**
     * Export sales data to CSV.
     *
     * Replaced the Collection filter+sort with a single ordered DB query so we
     * never load cancelled/refunded rows into PHP memory at all.
     *
     * join()  : orders → users (INNER — only authenticated customers)
     * whereNotIn(): exclude unwanted statuses at the SQL layer
     * orderBy(): sorting delegated to the DB engine
     */
    public function export()
    {
        Gate::authorize('view-analytics');
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

    /**
     * Display the admin sales analytics dashboard.
     *
     * Every metric is now computed entirely inside the database:
     *
     *  1. Monthly Sales   — GROUP BY year/month with SUM / AVG / COUNT aggregates
     *  2. Top Products    — join() order_items → products, GROUP BY product
     *  3. Top Customers   — join() orders → users,    GROUP BY user
     *  4. Sales by Cat.   — join() + leftJoin(),      GROUP BY category,
     *                       HAVING revenue > threshold, date-range filters
     *
     * No ->get() followed by Collection methods; all heavy lifting is SQL.
     */
    public function index(Request $request)
    {
        Gate::authorize('view-analytics');
        // ── Date-range filter inputs (optional) ───────────────────────────────
        // Accepts: ?year=2026  ?month=5  ?date=2026-05-01  ?from=… &to=…
        $filterYear  = $request->input('year');
        $filterMonth = $request->input('month');
        $filterDate  = $request->input('date');
        $filterFrom  = $request->input('from');
        $filterTo    = $request->input('to');

        // ── 1. Monthly Sales ──────────────────────────────────────────────────
        // Technique: GROUP BY YEAR(placed_at), MONTH(placed_at)
        //   selectRaw()  → computes revenue, average order value, and count
        //   when()       → applies each date filter only when the parameter is present
        //                  Replaces: if ($filterYear) { $query->whereYear(…); }
        //   whereNotIn() → exclude non-revenue statuses
        //   whereNull()  → respect soft deletes (DB::table bypasses the Eloquent scope)
        //   orderByRaw() → chronological sort inside a GROUP BY query
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
            ->when($filterYear,  fn ($q) => $q->whereYear('placed_at', $filterYear))
            // whereMonth() → WHERE MONTH(placed_at) = ?
            ->when($filterMonth, fn ($q) => $q->whereMonth('placed_at', $filterMonth))
            // whereDate()  → WHERE DATE(placed_at)  = '2026-05-01'
            ->when($filterDate,  fn ($q) => $q->whereDate('placed_at', $filterDate))
            // whereBetween → WHERE placed_at BETWEEN '…00:00:00' AND '…23:59:59'
            ->when($filterFrom && $filterTo, fn ($q) => $q->whereBetween('placed_at', [
                $filterFrom . ' 00:00:00',
                $filterTo   . ' 23:59:59',
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
                'count'   => (int)   $row->count,
            ],
        ]);

        // ── 2. Top 10 Products ────────────────────────────────────────────────
        // Technique:
        //   join()       orders ← order_items   (INNER: only items in real orders)
        //   join()       order_items → products  (INNER: only existing products)
        //   selectRaw()  SUM(oi.quantity) and SUM(oi.total_price) per product
        //   groupBy()    GROUP BY product_id
        //   orderByDesc  top sellers first
        //   limit()      top 10 only
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
                'name'     => $row->name,
                'quantity' => (int)   $row->quantity,
                'revenue'  => (float) $row->revenue,
            ]);

        // ── 3. Top 10 Customers ───────────────────────────────────────────────
        // Technique:
        //   join()      orders → users  (INNER: only authenticated customers)
        //   selectRaw() SUM(final_amount), COUNT(*) per user
        //   groupBy()   GROUP BY user_id
        //   orderByDesc highest spenders first
        //   limit()     top 10 only
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
                'name'        => $row->name  ?? 'Unknown',
                'email'       => $row->email ?? 'N/A',
                'total_spent' => (float) $row->total_spent,
                'order_count' => (int)   $row->order_count,
            ]);

        // ── 4. Sales by Category ──────────────────────────────────────────────
        // Technique:
        //   join()      order_items → orders    (INNER: only valid orders)
        //   join()      order_items → products  (INNER: only real products)
        //   leftJoin()  products → categories   (LEFT: keep uncategorised rows)
        //   selectRaw() SUM(quantity * unit_price) AS revenue
        //   when()      date filters applied conditionally with no if-blocks
        //   having()    HAVING revenue > threshold  ← aggregate filter
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
            ->when($filterYear,  fn ($q) => $q->whereYear('o.placed_at', $filterYear))
            ->when($filterMonth, fn ($q) => $q->whereMonth('o.placed_at', $filterMonth))
            ->when($filterDate,  fn ($q) => $q->whereDate('o.placed_at', $filterDate))
            ->when($filterFrom && $filterTo, fn ($q) => $q->whereBetween('o.placed_at', [
                $filterFrom . ' 00:00:00',
                $filterTo   . ' 23:59:59',
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
                'revenue'     => (float) $row->revenue,
                'quantity'    => (int)   $row->quantity,
                'order_count' => (int)   $row->order_count,
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
