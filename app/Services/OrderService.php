<?php

namespace App\Services;

use App\Jobs\Orders\{ChargePayment, ReserveStock, GenerateInvoicePdf, SendOrderConfirmation};
use App\Models\{Order, OrderItem, User, Coupon};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\{Bus, DB, Log, Auth, Storage, Cache};
use Throwable;

class OrderService
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function getOrdersForUser(User $user, ?string $cursor = null)
    {
        $cacheKey = $user->role === 'admin' 
            ? "orders:admin:all:cursor:" . ($cursor ?: 'first')
            : "orders:user:{$user->id}:cursor:" . ($cursor ?: 'first');

        return Cache::tags('ordersPage')->remember($cacheKey, now()->addMinutes(30), function () use ($user, $cursor) {
            $query = Order::with('user');

            if ($user->role !== 'admin') {
                $query->where('user_id', $user->id);
            }

            $totalOrders = $query->clone()->count();

            // Deterministic sorting with unique fallback
            $query->orderBy('placed_at', 'desc')->orderBy('id', 'desc');

            $perPage = 10;

            $paginated = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor ?: null);

            return [
                'orders' => $paginated,
                'total_orders' => $totalOrders,
            ];
        });
    }

    public function cartSummary(int $userId, ?string $couponCode = null): array
    {
        // Fetch cart data once and reuse for all calculations
        $cart = $this->cartService->get($userId);
        $cartModels = $this->cartService->getCartModels($userId);

        // Use calcTotal() with pre-fetched data — no extra Redis/cache reads
        $finalAmount = $this->cartService->calcTotal($cart, $cartModels);
        $totalAmount = 0.0;
        $couponCode = $couponCode ?? $this->cartService->getAppliedCoupon($userId);

        foreach ($cart as $productId => $item) {
            // Skip Redis metadata keys (starting with _)
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

    /**
     * Create an order from the current cart.
     * Pass $summary if you have already called cartSummary() to avoid a second fetch.
     */
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
                    if (is_string($productId) && str_starts_with($productId, '_')) continue;

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
                        // Stock deduction is handled by UpdateInventoryListener
                        // (triggered by the OrderPlaced event below)
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

                if ($user) {
                    $this->cartService->clear($user->id);
                }

                if ($summary['coupon']) {
                    $summary['coupon']->increment('used_count');
                }

                Log::channel('orders')->info("Order #{$order->order_number} finalized");

                // Fire legacy event (broadcasts real-time update to admin dashboard)
                event(new \App\Events\Orders\OrderPlaced($order));

                // ─────────────────────────────────────────────────────────────────
                // Exercise 46.4 — Job Chain
                // Sequence: ChargePayment → ReserveStock → GenerateInvoicePdf → SendOrderConfirmation
                //
                // Key properties:
                //  • Each job runs ONLY if the previous one succeeded.
                //  • catch() fires if ANY step throws — subsequent steps are skipped.
                //  • unless($order->is_digital) skips stock reservation for virtual products.
                // ─────────────────────────────────────────────────────────────────
                $chain = [
                    new ChargePayment($order),
                    new ReserveStock($order),
                    new GenerateInvoicePdf($order),
                    new SendOrderConfirmation($order),
                ];

                // unless(): remove ReserveStock from the chain for digital orders
                if ($order->is_digital ?? false) {
                    $chain = array_filter($chain, fn($job) => !($job instanceof ReserveStock));
                    $chain = array_values($chain);
                }

                Bus::chain($chain)
                    ->onQueue('default')
                    ->catch(function (Throwable $e) use ($order) {
                        // Runs if any step in the chain fails after all retries
                        Log::channel('orders')->critical(
                            "Post-checkout chain FAILED for Order #{$order->order_number}: {$e->getMessage()}"
                        );
                        // Mark order for manual intervention
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
                    'updated_by' => \Illuminate\Support\Facades\Auth::id()
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
            Storage::disk('public')->put($filename, $pdf->output());
            return $filename;
        }, function ($e) {
            Log::error('Invoice generation failed: ' . $e->getMessage());
            return null;
        });
    }

    public function downloadInvoice(Order $order)
    {
        $invoiceData = $this->generateInvoiceData($order);
        $pdf = Pdf::loadView('layouts.invoice', ['invoice' => $invoiceData]);
        $pdf->save(storage_path('app/invoices/invoice-' . ($order->order_number ?? $order->id) . '.pdf'));
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
