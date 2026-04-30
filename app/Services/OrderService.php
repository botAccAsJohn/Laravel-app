<?php

namespace App\Services;

use App\Models\{Order, OrderItem, User, Coupon};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\{DB, Log, Auth, Storage};

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

    public function cartSummary(int $userId, ?string $couponCode = null): array
    {
        // Fetch cart data once and reuse for all calculations
        $cart = $this->cartService->get($userId);
        $cartModels = $this->cartService->getCartModels($userId);

        // Use calcTotal() with pre-fetched data — no extra Redis/cache reads
        $finalAmount = $this->cartService->calcTotal($cart, $cartModels);
        $totalAmount = 0.0;

        foreach ($cart as $productId => $item) {
            $model = $cartModels[$productId] ?? null;
            $originalPrice = (float) ($item['price'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($model && $model->price) {
                $originalPrice = (float) $model->price;
            }

            $totalAmount += $originalPrice * $quantity;
        }

        $couponDiscount = 0.0;
        $coupon = null;

        if ($couponCode) {
            $coupon = $this->validateCoupon($couponCode);
            if ($coupon) {
                $couponDiscount = $coupon->calculateDiscount($finalAmount);
                $finalAmount = max(0, $finalAmount - $couponDiscount);
            }
        }

        return [
            'cart' => $cart,
            'cartModels' => $cartModels,
            'total_amount' => $totalAmount,
            'discount_amount' => max(0, $totalAmount - $finalAmount),
            'final_amount' => $finalAmount,
            'coupon' => $coupon,
            'coupon_discount' => $couponDiscount,
        ];
    }

    /**
     * Create an order from the current cart.
     * Pass $summary if you have already called cartSummary() to avoid a second fetch.
     */
    public function createFromCart(User $user, array $data, ?array $summary = null): Order
    {
        $couponCode = $data['coupon_code'] ?? null;
        $summary = $summary ?? $this->cartSummary($user->id, $couponCode);

        return retry(3, function () use ($user, $data, $summary) {
            return DB::transaction(function () use ($user, $data, $summary) {
                $order = tap(Order::create([
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'payment_method' => $data['payment_method'],
                    'address' => $data['address'],
                    'phone' => $data['phone'] ?? null,
                    'coupon_code' => $summary['coupon']?->code ?? null,
                    'total_amount' => $summary['total_amount'],
                    'discount_amount' => $summary['discount_amount'],
                    'final_amount' => $summary['final_amount'],
                    'placed_at' => now(),
                ]), function ($order) {
                    Log::channel('orders')->info("Order Initialized: #{$order->id}");
                });

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
                        $model->quantity = $model->quantity - $quantity;
                        $model->save();

                        broadcast(new \App\Events\ProductStockChanged($model->id, $model->quantity));
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

                if ($summary['coupon']) {
                    $summary['coupon']->increment('used_count');
                }

                Log::channel('orders')->info("Order #{$order->id} finalized for User #{$user->id}");

                event(new \App\Events\Orders\OrderPlaced($order));

                return $order;
            });
        }, 100);
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

        $subtotal = (float) $order->total_amount;
        $discount = (float) $order->discount_amount;
        $taxRate = (float) ($order->tax_rate ?? 0);
        $salesTax = ($subtotal - $discount) * $taxRate;
        $shippingCharges = (float) ($order->shipping_charges ?? 0);
        $total = (float) $order->final_amount;

        return [
            'invoice_number' => 'INV-' . $order->id,
            'date' => $order->placed_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'coupon_code' => $order->coupon_code,

            'bill_to' => [
                'name' => $order->customer_name ?? $order->user?->name ?? '',
                'address_1' => $order->billing_address_1 ?? $order->address ?? '',
                'address_2' => $order->billing_address_2 ?? '',
                'city_state_zip' => trim(($order->billing_city ?? '') . ' ' . ($order->billing_state ?? '') . ' ' . ($order->billing_zip ?? '')),
                'phone' => $order->billing_phone ?? $order->phone ?? '',
            ],

            'ship_to' => [
                'name' => $order->shipping_name ?? $order->customer_name ?? $order->user?->name ?? '',
                'address_1' => $order->shipping_address_1 ?? $order->address ?? '',
                'address_2' => $order->shipping_address_2 ?? '',
                'city_state_zip' => trim(($order->shipping_city ?? '') . ' ' . ($order->shipping_state ?? '') . ' ' . ($order->shipping_zip ?? '')),
                'phone' => $order->shipping_phone ?? $order->phone ?? '',
            ],

            'items' => $items,
            'tax_rate' => $taxRate,
            'shipping_charges' => $shippingCharges,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'sales_tax' => $salesTax,
            'total' => $total,
        ];
    }

    public function generateInvoiceAndReturnPath(Order $order)
    {
        return rescue(function () use ($order) {
            $invoiceData = $this->generateInvoiceData($order);
            $pdf = Pdf::loadView('layouts.invoice', ['invoice' => $invoiceData]);
            Storage::disk('public')->put('invoices/invoice-' . $order->id . '.pdf', $pdf->output());
            return 'invoices/invoice-' . $order->id . '.pdf';
        }, function ($e) {
            Log::error('Invoice generation failed: ' . $e->getMessage());
            return null;
        });
    }

    public function downloadInvoice(Order $order)
    {
        $invoiceData = $this->generateInvoiceData($order);
        $pdf = Pdf::loadView('layouts.invoice', ['invoice' => $invoiceData]);
        $pdf->save(storage_path('app/invoices/invoice-' . $order->id . '.pdf'));
        return $pdf->download('invoice.pdf');
    }

    public function validateCoupon(string $code): ?Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon && $coupon->isValid()) {
            return $coupon;
        }

        return null;
    }
}
