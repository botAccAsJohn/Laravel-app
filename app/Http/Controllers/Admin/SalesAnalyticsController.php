<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;

class SalesAnalyticsController extends Controller
{
    /**
     * Export sales data to CSV.
     * Uses Collection methods (filter, sortByDesc, values) for data preparation.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export()
    {
        // Fetch all and filter using Collections
        $orders = Order::with('user')->get()
            ->filter(fn($order) => $order->status !== 'cancelled')
            ->sortByDesc('placed_at')
            ->values(); // Reset keys

        return Excel::download(new SalesExport($orders), 'sales_report_' . now()->format('Y-m-d') . '.csv');
    }

    /**
     * Display the sales analytics dashboard.
     *
     * This method maximizes the use of Laravel Collections for all data processing:
     * - Monthly sales (revenue and average)
     * - Top 10 products by quantity sold
     * - Top 10 customers by total spent
     * - Sales by category
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 1. Initial Data Load
        $baseOrders = Order::with(['user'])->get();
        $baseItems = OrderItem::with(['product.category'])->get();

        // 2. High-Level Filtering (Using Collections)
        // Only include non-pending and non-cancelled orders for analytics
        $orders = $baseOrders->reject(function ($order) {
            return in_array($order->status, ['pending', 'cancelled']);
        });

        // Sync items with valid orders
        $validOrderIds = $orders->pluck('id');
        $orderItems = $baseItems->filter(function ($item) use ($validOrderIds) {
            return $validOrderIds->contains($item->order_id);
        });

        // 3. Monthly Sales (group orders by month, calculate revenue/average)
        // Uses: groupBy(), map(), sum(), avg(), sortKeys()
        $monthlySales = $orders->groupBy(function ($order) {
            return $order->placed_at ? $order->placed_at->format('Y-m') : 'Unknown';
        })->map(function ($group) {
            return [
                'revenue' => (float) $group->sum('final_amount'),
                'average' => (float) $group->avg('final_amount'),
                'count'   => $group->count(),
            ];
        })->sortKeys();

        // 4. Top 10 Products (by quantity sold)
        // Uses: groupBy(), map(), sortByDesc(), sum(), take()
        $topProducts = $orderItems->groupBy('product_id')->map(function ($items) {
            $product = $items->first()->product;
            return [
                'name'     => $product->name ?? 'Unknown',
                'quantity' => $items->sum('quantity'),
                'revenue'  => (float) $items->sum('total_price'),
            ];
        })->sortByDesc('quantity')->take(10);

        // 5. Top 10 Customers (by total spent)
        // Uses: groupBy(), map(), sortByDesc(), sum(), take()
        $topCustomers = $orders->groupBy('user_id')->map(function ($userOrders) {
            $user = $userOrders->first()->user;
            return [
                'name'  => $user->name ?? 'Guest',
                'email' => $user->email ?? 'N/A',
                'total_spent' => (float) $userOrders->sum('final_amount'),
                'order_count' => $userOrders->count(),
            ];
        })->sortByDesc('total_spent')->take(10);

        // 6. Sales by category
        // Uses: groupBy(), map(), sum()
        $salesByCategory = $orderItems->groupBy(function ($item) {
            return $item->product && $item->product->category ? $item->product->category->name : 'Uncategorized';
        })->map(function ($items) {
            return [
                'revenue' => (float) $items->sum('total_price'),
                'quantity' => $items->sum('quantity'),
            ];
        });

        return view('admin.analytics.index', compact(
            'monthlySales',
            'topProducts',
            'topCustomers',
            'salesByCategory'
        ));
    }
}
