<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        $cart = $this->cartService->get($userId);
        $cartModels = $this->cartService->getCartModels($userId);
        $finalAmount = $this->cartService->total($userId);
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

        return [
            'cart' => $cart,
            'cartModels' => $cartModels,
            'total_amount' => $totalAmount,
            'discount_amount' => max(0, $totalAmount - $finalAmount),
            'final_amount' => $finalAmount,
        ];
    }

    public function createFromCart(User $user, array $data): Order
    {
        $summary = $this->cartSummary($user->id);

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
                    $originalPrice = (float) $model->price;
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

            return $order;
        });
    }

    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            if (isset($data['status'])) {
                $order->update(['status' => $data['status']]);
            }

            return $order->refresh();
        });
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);
            return $order->refresh();
        });
    }

    public function delete(Order $order): void
    {
        $order->delete();
    }

    public function generateInvoiceData(Order $order): array
    {
        $order->load('items.product');

        $items = $order->items->map(function ($item) {
            return [
                'description'  => $item->product?->name ?? 'Item',
                'quantity'     => (int) $item->quantity,
                'unit_price'   => (float) $item->unit_price,
                'total_price'  => (float) ($item->total_price ?? ($item->quantity * $item->unit_price)),
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

        $pdf = \Pdf::loadView('invoices.invoice', ['invoice' => $invoiceData]);

        return $pdf->download('invoice.pdf');
    }
}
