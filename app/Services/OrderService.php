<?php

namespace App\Services;

use App\Jobs\Orders\{ChargePayment, ReserveStock, GenerateInvoicePdf, SendOrderConfirmation};
use App\Models\{Order, OrderItem, User, Coupon};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\{Bus, DB, Log, Auth, Storage, Cache, URL};
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class OrderService
{
    public function __construct(
        private CartService $cartService,
    ) {
    }

    public function getOrdersForUser(\Illuminate\Contracts\Auth\Authenticatable $user, ?string $cursor = null, bool $isAdmin = false)
    {
        $cacheKey = $isAdmin
            ? 'orders:admin:all:cursor:' . ($cursor ?: 'first')
            : "orders:user:{$user->getAuthIdentifier()}:cursor:" . ($cursor ?: 'first');

        return Cache::tags('ordersPage')->remember($cacheKey, now()->addMinutes(30), function () use ($user, $cursor, $isAdmin) {

            $query = Order::with('user')
                ->when(
                    !$isAdmin,
                    fn(Builder $q) =>
                    $q->where('user_id', $user->getAuthIdentifier())
                );

            $totalOrders = $query->clone()->count();

            $query->orderBy('placed_at', 'desc')->orderBy('id', 'desc');

            $paginated = $query->cursorPaginate(10, ['*'], 'cursor', $cursor ?: null);

            return [
                'orders' => $paginated,
                'total_orders' => $totalOrders,
            ];
        });
    }

    public function cartSummary(int $userId, ?string $couponCode = null): array
    {
        $cartKey = 'cart:user:' . $userId;

        $cart = $this->cartService->get($cartKey);
        $cartModels = $this->cartService->getCartModels($cartKey);

        $finalAmount = $this->cartService->calcTotal($cart, $cartModels);
        $totalAmount = 0.0;
        $couponCode = $couponCode ?? $this->cartService->getAppliedCoupon($cartKey);

        foreach ($cart as $productId => $item) {
            if (is_string($productId) && str_starts_with($productId, '_')) {
                continue;
            }
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

    public function createFromCart(User $user, array $data, ?array $summary = null): Order
    {
        $couponCode = $data['coupon_code'] ?? null;
        $summary = $summary ?? $this->cartSummary($user->id, $couponCode);
        return $this->processOrderCreation($user, $data, $summary);
    }


    private function processOrderCreation(User $user, array $data, array $summary): Order
    {
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
                ]), function ($order) use ($user) {
                    Log::channel('orders')->info("Order Initialized: #{$order->order_number} for User #{$user->id}");
                });

                foreach ($summary['cart'] as $productId => $item) {
                    if (is_string($productId) && str_starts_with($productId, '_'))
                        continue;

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
                    }

                    // $discountedPrice = (float) ($item['price'] ?? 0);
                    $discountedPrice = $model && $model->discount_price 
                        ? (float) $model->discount_price 
                        : (float) ($item['price'] ?? 0);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'unit_price' => $originalPrice,
                        'discount_amount' => max(0, ($originalPrice - $discountedPrice) * $quantity),
                        'total_price' => $discountedPrice * $quantity,
                    ]);
                }

                if ($user) {
                    $this->cartService->clear('cart:user:' . $user->id);
                }

                if ($summary['coupon']) {
                    $summary['coupon']->increment('used_count');
                }

                Log::channel('orders')->info("Order #{$order->order_number} finalized");

                event(new \App\Events\Orders\OrderPlaced($order));

                $chain = [
                    (new ChargePayment($order))->onQueue('default'),
                    (new ReserveStock($order))->onQueue('default'),
                    (new GenerateInvoicePdf($order))->onQueue('pdfs'),
                    (new SendOrderConfirmation($order))->onQueue('emails'),
                ];

                // unless(): remove ReserveStock from the chain for digital orders
                if ($order->is_digital ?? false) {
                    $chain = array_filter($chain, fn($job) => !($job instanceof ReserveStock));
                    $chain = array_values($chain);
                }

                Bus::chain($chain)
                    ->catch(function (Throwable $e) use ($order) {
                        Log::channel('orders')->critical(
                            "Post-checkout chain FAILED for Order #{$order->order_number}: {$e->getMessage()}"
                        );
                        $order->update(['status' => 'failed']);
                    })
                    ->dispatch();

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

                Log::channel('orders')->info("Order #{$order->order_number} status updated", [
                    'old_status' => $oldStatus,
                    'new_status' => $data['status'],
                    'updated_by' => current_user()?->id,
                    'actor_guard' => current_guard()
                ]);

                // Broadcast + event dispatch is handled by OrderObserver::updated()
            }

            return $order->refresh();
        });
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            Log::channel('orders')->warning("Order #{$order->order_number} was cancelled", [
                'user_id' => current_user()?->id,
                'actor_guard' => current_guard(),
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
            'deleted_by' => current_user()?->id,
            'actor_guard' => current_guard()
        ]);
    }

    public function generateInvoiceData(Order $order): array
    {
        $order->loadMissing('items.product');

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
            'invoice_number' => 'INV-' . ($order->order_number ?? $order->id),
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
            $filename = 'invoices/invoice-' . ($order->order_number ?? $order->id) . '.pdf';
            Storage::disk('local')->put($filename, $pdf->output());
            return $filename;
        }, function ($e) {
            Log::error('Invoice generation failed: ' . $e->getMessage());
            return null;
        });
    }

    public function generateSignedUrl(Order $order)
    {
        $signedUrl = URL::temporarySignedRoute(
            'invoices.download',
            now()->addMinutes(10),
            ['order' => $order]
        );

        return $signedUrl;
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
