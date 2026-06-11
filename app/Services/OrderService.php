<?php

namespace App\Services;

use App\Jobs\Orders\{ChargePayment, ReserveStock, GenerateInvoicePdf, SendOrderConfirmation};
use App\Models\{Order, OrderItem, User, Coupon};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\{Bus, DB, Log, Auth, Storage, Cache};
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class OrderService
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function getOrdersForUser(\Illuminate\Contracts\Auth\Authenticatable $user, ?string $cursor = null, bool $isAdmin = false)
    {
        $cacheKey = $isAdmin
            ? 'orders:admin:all:cursor:' . ($cursor ?: 'first')
            : "orders:user:{$user->getAuthIdentifier()}:cursor:" . ($cursor ?: 'first');

        return Cache::tags('ordersPage')->remember($cacheKey, now()->addMinutes(30), function () use ($user, $cursor, $isAdmin) {

            $query = Order::with('user')
                ->when(! $isAdmin, fn (Builder $q) =>
                    $q->where('user_id', $user->getAuthIdentifier())
                );

            $totalOrders = $query->clone()->count();

            $query->orderBy('placed_at', 'desc')->orderBy('id', 'desc');

            $paginated = $query->cursorPaginate(10, ['*'], 'cursor', $cursor ?: null);

            return [
                'orders'       => $paginated,
                'total_orders' => $totalOrders,
            ];
        });
    }

    /**
     * Return a paginated list of customers for the admin "customer history" page.
     *
     * Subquery techniques used
     * ───────────────────────
     *  addSelect() + subquery
     *    Injects a correlated scalar subquery that fetches the *most recent*
     *    order's total for each user in the same SQL round-trip.
     *    SQL emitted:
     *      SELECT *, (
     *        SELECT total_amount FROM orders
     *        WHERE  user_id = users.id
     *        AND    deleted_at IS NULL
     *        ORDER  BY placed_at DESC
     *        LIMIT  1
     *      ) AS last_order_amount
     *      FROM users …
     *
     *  whereExists() + subquery
     *    Filters to users who have placed at least one paid order.
     *    SQL emitted:
     *      WHERE EXISTS (
     *        SELECT 1 FROM orders
     *        WHERE  user_id = users.id
     *        AND    status IN ('confirmed','processing','shipped','delivered')
     *        AND    deleted_at IS NULL
     *      )
     *
     *  whereIn() with a subquery (zero extra round-trips)
     *    Used to scope to users who bought at least one product currently on sale.
     *    SQL emitted:
     *      WHERE id IN (
     *        SELECT DISTINCT user_id FROM orders
     *        WHERE  id IN (
     *          SELECT order_id FROM order_items
     *          WHERE  product_id IN (
     *            SELECT id FROM products
     *            WHERE  discount_price > 0 AND deleted_at IS NULL
     *          )
     *        )
     *        AND deleted_at IS NULL
     *      )
     *
     *  when() — conditional filter application
     *    Replaces every bare `if ($filter) { $query->where(…) }` block.
     *    when($value, $callback) appends the constraint only when $value is truthy;
     *    the second (optional) $default callback runs when $value is falsy.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCustomerList($request, int $perPage = 20)
    {
        // ── Scalar subquery for last order amount ──────────────────────────────
        // Order::select('total_amount') scopes to this user via whereColumn(),
        // latest() adds ORDER BY placed_at DESC, limit(1) keeps it scalar.
        // addSelect() appends this computed column without replacing the base select.
        $lastOrderSubquery = Order::select('total_amount')
            ->whereColumn('user_id', 'users.id')   // correlated: ties to outer query row
            ->whereNull('deleted_at')
            ->latest('placed_at')
            ->limit(1);

        // ── "On sale" product IDs subquery (no second DB round-trip) ────────────
        // Built as a query object — whereIn() accepts a Builder directly and
        // Laravel compiles it into a subquery: WHERE id IN (SELECT id FROM …)
        $onSaleProductIds = DB::table('products')
            ->select('id')
            ->where('discount_price', '>', 0)
            ->whereNull('deleted_at');

        // Order IDs that contain at least one on-sale product
        $onSaleOrderIds = DB::table('order_items')
            ->select('order_id')
            ->whereIn('product_id', $onSaleProductIds);  // ← subquery, not array

        // User IDs who placed those orders
        $onSaleUserIds = DB::table('orders')
            ->select('user_id')
            ->distinct()
            ->whereIn('id', $onSaleOrderIds)             // ← subquery, not array
            ->whereNull('deleted_at');

        return User::select(['id', 'name', 'email', 'role', 'created_at'])
            // ── addSelect() + subquery: inject last_order_amount as a computed column ─
            ->addSelect(['last_order_amount' => $lastOrderSubquery])

            // ── whereExists(): only users with at least one paid order ───────────
            // EXISTS is more efficient than IN for large sets: the DB can short-circuit
            // as soon as one matching row is found — it never counts all matches.
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('user_id', 'users.id')
                    ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->whereNull('deleted_at');
            })

            // ── Exclude soft-deleted users ────────────────────────────────────────
            ->whereNull('deleted_at')

            // ── when(): search by name/email — applies only if ?search= is present ──
            // Replaces: if ($request->filled('search')) { $query->where(…) }
            ->when($request->filled('search'), fn (Builder $q) =>
                $q->where(function (Builder $inner) use ($request) {
                    $term = '%' . $request->input('search') . '%';
                    $inner->where('name', 'LIKE', $term)
                          ->orWhere('email', 'LIKE', $term);
                })
            )

            // ── when(): scope to role — applies only if ?role= is present ────────
            ->when($request->filled('role'), fn (Builder $q) =>
                $q->where('role', $request->input('role'))
            )

            // ── when(): whereIn with subquery — scope to on-sale buyers ──────────
            // ?on_sale_buyers=1 narrows to users who ordered a discounted product.
            // whereIn() receives the Builder object directly — no ->get() needed.
            ->when($request->boolean('on_sale_buyers'), fn (Builder $q) =>
                $q->whereIn('id', $onSaleUserIds)       // ← single SQL, no round-trip
            )

            // ── when(): sort — default to newest registered ─────────────────────
            ->when(
                $request->input('sort') === 'last_order_desc',
                fn (Builder $q) => $q->orderByDesc('last_order_amount'),
                fn (Builder $q) => $q->orderByDesc('created_at')   // default
            )

            ->paginate($perPage);
    }

    public function cartSummary(int $userId, ?string $couponCode = null): array
    {
        // Orders are always placed by authenticated users — build their key directly
        $cartKey = 'cart:user:' . $userId;

        // Fetch cart data once and reuse for all calculations
        $cart = $this->cartService->get($cartKey);
        $cartModels = $this->cartService->getCartModels($cartKey);

        // Use calcTotal() with pre-fetched data — no extra Redis/cache reads
        $finalAmount = $this->cartService->calcTotal($cart, $cartModels);
        $totalAmount = 0.0;
        $couponCode = $couponCode ?? $this->cartService->getAppliedCoupon($cartKey);

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
                    $this->cartService->clear('cart:user:' . $user->id);
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
