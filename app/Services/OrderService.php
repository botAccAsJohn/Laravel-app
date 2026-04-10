<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function getOrdersForUser(User $user)
    {
        return $user->role === 'admin'
            ? Order::with('user')->latest('placed_at')->get()
            : Order::with('user')->where('user_id', $user->id)->latest('placed_at')->get();
    }

    public function cartSummary(int $userId): array
    {
        // Fetch cart data once and reuse for all calculations
        $cart       = $this->cartService->get($userId);
        $cartModels = $this->cartService->getCartModels($userId);

        // Use calcTotal() with pre-fetched data — no extra Redis/cache reads
        $finalAmount  = $this->cartService->calcTotal($cart, $cartModels);
        $totalAmount  = 0.0;

        foreach ($cart as $productId => $item) {
            $model         = $cartModels[$productId] ?? null;
            $originalPrice = (float) ($item['price'] ?? 0);
            $quantity      = (int) ($item['quantity'] ?? 0);

            if ($model && $model->price) {
                $originalPrice = (float) $model->price;
            }

            $totalAmount += $originalPrice * $quantity;
        }

        return [
            'cart'            => $cart,
            'cartModels'      => $cartModels,
            'total_amount'    => $totalAmount,
            'discount_amount' => max(0, $totalAmount - $finalAmount),
            'final_amount'    => $finalAmount,
        ];
    }

    /**
     * Create an order from the current cart.
     * Pass $summary if you have already called cartSummary() to avoid a second fetch.
     */
    public function createFromCart(User $user, array $data, ?array $summary = null): Order
    {
        $summary = $summary ?? $this->cartSummary($user->id);

        return DB::transaction(function () use ($user, $data, $summary) {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
                'address' => $data['address'],
                'phone' => $data['phone'] ?? null,
                'total_amount' => $summary['total_amount'],
                'discount_amount' => $summary['discount_amount'],
                'final_amount' => $summary['final_amount'],
                'placed_at' => now(),
            ]);

            foreach ($summary['cart'] as $productId => $item) {
                $model = $summary['cartModels'][$productId] ?? null;
                $originalPrice = (float) ($item['price'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($model && $model->price) {
                    if ($quantity > $model->quantity) {
                        throw new \App\Exceptions\ProductOutOfStockException(
                            productName: $model->name,
                            productId: $model->id,
                            requestedQty: $quantity,
                            availableQty: $model->quantity
                        );
                    }

                    $originalPrice = (float) $model->price;

                    // Deduct stock and broadcast real-time logic
                    $model->quantity = $model->quantity - $quantity;
                    $model->save();

                    broadcast(new \App\Events\ProductStockChanged($model->id, $model->quantity));

                    // Clear the cache for this product since quantity changed
                    \Illuminate\Support\Facades\Cache::forget(\App\Models\Product::CACHE_KEY_SINGLE . $model->slug);
                }

                $discountedPrice = (float) ($item['price'] ?? 0);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $originalPrice,
                    'discount_amount' => max(0, ($originalPrice - $discountedPrice) * $quantity),
                    'total_price' => $discountedPrice * $quantity,
                ]);
            }
            $this->cartService->clear($user->id);

            Log::channel('orders')->info("Order #{$order->id} created for User #{$user->id}", [
                'total' => $order->total_amount,
                'items_count' => count($summary['cart']),
                'address' => $order->address
            ]);

            return $order;
        });
    }

    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            if (isset($data['status'])) {
                $oldStatus = $order->status;
                $order->update(['status' => $data['status']]);

                Log::channel('orders')->info("Order #{$order->id} status updated", [
                    'old_status' => $oldStatus,
                    'new_status' => $data['status'],
                    'updated_by' => Auth::id()
                ]);
            }

            return $order->refresh();
        });
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            Log::channel('orders')->warning("Order #{$order->id} was cancelled", [
                'user_id' => Auth::id(),
                'order_user_id' => $order->user_id
            ]);

            return $order->refresh();
        });
    }

    public function delete(Order $order): void
    {
        $orderId = $order->id;
        $order->delete();

        Log::channel('orders')->alert("Order #{$orderId} was deleted from database", [
            'deleted_by' => Auth::id()
        ]);
    }

    public function generateInvoiceData(Order $order): array
    {
        $order->load('items.product');

        $items = $order->items->map(function ($item) {
            return [
                'description' => $item->product?->name ?? 'Item',
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) ($item->total_price ?? ($item->quantity * $item->unit_price)),
            ];
        })->values()->all();

        $subtotal = collect($items)->sum('total_price');
        $taxRate = (float) ($order->tax_rate ?? 0);
        $salesTax = $subtotal * $taxRate;
        $shippingCharges = (float) ($order->shipping_charges ?? 0);
        $total = $subtotal + $salesTax + $shippingCharges;

        return [
            'invoice_number' => 'INV-' . $order->id,
            'date' => now()->format('Y-m-d'),

            'bill_to' => [
                'name' => $order->customer_name ?? $order->user?->name ?? '',
                'address_1' => $order->billing_address_1 ?? '',
                'address_2' => $order->billing_address_2 ?? '',
                'city_state_zip' => trim(($order->billing_city ?? '') . ' ' . ($order->billing_state ?? '') . ' ' . ($order->billing_zip ?? '')),
                'phone' => $order->billing_phone ?? '',
            ],

            'ship_to' => [
                'name' => $order->shipping_name ?? $order->customer_name ?? $order->user?->name ?? '',
                'address_1' => $order->shipping_address_1 ?? '',
                'address_2' => $order->shipping_address_2 ?? '',
                'city_state_zip' => trim(($order->shipping_city ?? '') . ' ' . ($order->shipping_state ?? '') . ' ' . ($order->shipping_zip ?? '')),
                'phone' => $order->shipping_phone ?? '',
            ],

            'items' => $items,
            'tax_rate' => $taxRate,
            'shipping_charges' => $shippingCharges,
            'subtotal' => $subtotal,
            'sales_tax' => $salesTax,
            'total' => $total,
        ];
    }

    public function downloadInvoice(Order $order)
    {
        $invoiceData = $this->generateInvoiceData($order);

        $pdf = Pdf::loadView('invoices.invoice', ['invoice' => $invoiceData]);

        return $pdf->download('invoice.pdf');
    }
}
