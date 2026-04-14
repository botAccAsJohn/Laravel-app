<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $service,
    ) {}

    public function index(): View
    {
        $orders = $this->service->getOrdersForUser(Auth::user());

        return view('orders.index', [
            'orders' => $orders,
            'total_orders' => $orders->count(),
        ]);
    }

    /**
     * Display customer order insights and statistics.
     * 
     * Uses Collection methods: count(), sum(), avg(), reject(), flatMap(), groupBy(), sortByDesc(), take()
     */
    public function analytics(): View
    {
        $user = Auth::user();
        // Fetch all orders for the user with items
        $orders = Order::with('items.product')
            ->where('user_id', $user->id)
            ->get();

        // 1. General Metrics (Collection: reject, sum, avg)
        $processedOrders = $orders->reject(fn($o) => in_array($o->status, ['cancelled', 'pending']));
        
        $totalOrders = $orders->count();
        $totalSpent = (float) $processedOrders->sum('final_amount');
        $averageOrderValue = (float) $processedOrders->avg('final_amount');

        // 2. Favorite Products (Collection: flatMap, groupBy, map, sortByDesc, take)
        $favoriteProducts = $orders->flatMap(fn($order) => $order->items)
            ->groupBy('product_id')
            ->map(function ($items) {
                $item = $items->first();
                return [
                    'name' => $item->product->name ?? 'Unknown',
                    'count' => (int) $items->sum('quantity'),
                    'image' => $item->product->image_path ?? null,
                ];
            })
            ->sortByDesc('count')
            ->take(3);

        // 3. Status Breakdown (Collection: groupBy, map)
        $statusBreakdown = $orders->groupBy('status')
            ->map(fn($group) => $group->count());

        return view('orders.analytics', compact(
            'totalOrders',
            'totalSpent',
            'averageOrderValue',
            'favoriteProducts',
            'statusBreakdown'
        ));
    }

    public function create(): View|RedirectResponse
    {
        $summary = $this->service->cartSummary(Auth::id());

        if (empty($summary['cart'])) {
            return redirect()->route('cart.index')->with('warning', 'Your cart is empty. Add products before checkout.');
        }

        return view('orders.create', $summary);
    }

    // public function store(Request $request): RedirectResponse
    // {
    //     $validated = $request->validate([
    //         'address' => ['required', 'string', 'max:255'],
    //         'phone' => ['nullable', 'string', 'max:20'],
    //         'payment_method' => ['required', 'in:card,upi,wallet,cod,emi,netbanking'],
    //     ]);

    //     // Compute summary once
    //     $summary = $this->service->cartSummary(Auth::id());
    //     if (empty($summary['cart'])) {
    //         return redirect()->route('cart.index')->with('warning', 'Your cart is empty. Add products before checkout.');
    //     }

    //     try {
    //         $order = $this->service->createFromCart(Auth::user(), $validated, $summary);
    //         broadcast(new OrderPlaced($order))->toOthers();
    //         return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
    //     } catch (\App\Exceptions\ProductOutOfStockException $e) {
    //         return redirect()->route('cart.index')->with('error', $e->getMessage());
    //     }
    // }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address'        => ['required', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'in:card,upi,wallet,cod,emi,netbanking'],
        ]);

        // Compute summary once — reuse for the empty-cart check AND for order creation
        $summary = $this->service->cartSummary(Auth::id());
        if (empty($summary['cart'])) {
            return redirect()->route('cart.index')->with('warning', 'Your cart is empty. Add products before checkout.');
        }

        $order = $this->service->createFromCart(Auth::user(), $validated, $summary);

        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function show(Order $order): View
    {
        $this->authorizeAccess($order);
        $order->load(['user', 'items.product']);
        return view('orders.show', compact('order'));
    }

    public function edit(Order $order): View
    {
        abort_unless(Auth::user()->role === 'admin', 403, 'Only admins can edit orders.');

        $order->load(['items.product']);

        return view('orders.edit', compact('order'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        abort_unless(Auth::user()->role === 'admin', 403, 'Only admins can update orders.');

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,processing,shipped,delivered,cancelled,refunded'],
        ]);

        $this->service->update($order, $validated);
        broadcast(new OrderStatusUpdated($order))->toOthers();

        return redirect()->route('orders.index')->with('success', 'Order status updated.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorizeAccess($order);

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return redirect()->back()->with('error', 'Order cannot be cancelled in its current state.');
        }

        $this->service->cancel($order);

        return redirect()->back()->with('success', 'Order has been cancelled.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        abort_unless(Auth::user()->role === 'admin', 403, 'Only admins can delete orders.');

        $this->service->delete($order);

        return redirect()->route('orders.index')->with('success', 'Order deleted.');
    }

    private function authorizeAccess(Order $order): void
    {
        $user = Auth::user();

        abort_unless($user->role === 'admin' || $order->user_id === $user->id, 403);
    }
}
