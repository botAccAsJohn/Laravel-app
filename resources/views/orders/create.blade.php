@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Checkout</h1>
            <p class="text-sm text-gray-500 mt-1">Review your cart and add the remaining order details.</p>
        </div>
        <a href="{{ route('cart.index') }}" class="text-gray-600 hover:text-gray-900">Back to Cart</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $paymentMethods = ['card', 'upi', 'wallet', 'cod', 'emi', 'netbanking'];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form action="{{ route('orders.store') }}" method="POST" class="bg-white shadow-md rounded-lg p-6 space-y-5">
                @csrf

                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Delivery Details</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-bold mb-1">Address <span class="text-red-500">*</span></label>
                            <textarea name="address" rows="4" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>{{ old('address') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-bold mb-1">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Enter phone number">
                        </div>
                    </div>
                </div>

                <div class="border-t pt-5">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Payment Details</h2>

                    <div>
                        <label class="block text-gray-700 font-bold mb-1">Payment Method <span class="text-red-500">*</span></label>
                        <select name="payment_method" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">Select payment method</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" {{ old('payment_method') === $method ? 'selected' : '' }}>
                                    {{ strtoupper($method) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="border-t pt-5">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Coupon Code</h2>
                    <div class="flex gap-2">
                        <input type="text" id="coupon_input" name="coupon_code" value="{{ $applied_coupon ?? old('coupon_code') }}"
                               class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="Enter coupon code">
                        <button type="button" onclick="applyCoupon()"
                                class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition"
                                id="apply-btn">
                            Apply
                        </button>
                        <button type="button" onclick="removeCoupon()" 
                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition"
                                id="remove-btn"
                                style="display: {{ (isset($coupon) && $coupon) ? 'block' : 'none' }};">
                            Remove
                        </button>
                    </div>
                    <div id="coupon_message"></div>
                    @if(isset($coupon) && $coupon)
                        <p class="text-sm text-green-600 mt-2" id="coupon_status">
                            <i class="fas fa-check-circle"></i> Coupon <strong>{{ $coupon->code }}</strong> applied! ({{ $coupon->type === 'percentage' ? $coupon->value.'%' : format_price($coupon->value) }} off)
                        </p>
                    @endif
                </div>

                <div class="border-t pt-5">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div class="rounded-lg bg-gray-50 p-4 border">
                            <p class="text-gray-500">Subtotal</p>
                            <p class="text-lg font-semibold text-gray-800 mt-1" data-total-amount>@currency($total_amount)</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 border" data-discount-amount>
                            <p class="text-gray-500">Discount</p>
                            <p class="text-lg font-semibold text-green-600 mt-1">@currency($discount_amount - ($coupon_discount ?? 0))</p>
                        </div>
                        <div class="rounded-lg bg-green-50 p-4 border border-green-200" id="main_coupon_discount_card" style="display: {{ (isset($coupon_discount) && $coupon_discount > 0) ? 'block' : 'none' }};">
                            <p class="text-green-700 font-medium">Coupon</p>
                            <p class="text-lg font-semibold text-green-700 mt-1" id="main_coupon_discount_amount">- @currency($coupon_discount ?? 0)</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 border" data-final-amount>
                            <p class="text-gray-500">Final Total</p>
                            <p class="text-lg font-semibold text-indigo-700 mt-1">@currency($final_amount)</p>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-indigo-700 transition">
                    Place Order
                </button>
            </form>
        </div>

        <div>
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Order Summary</h2>

                <div class="space-y-4">
                    @foreach($cart as $productId => $item)
                        @php
                            if (is_string($productId) && str_starts_with($productId, '_')) continue;
                            $model = $cartModels[$item['id']] ?? null;
                            $effectivePrice = $model && $model->discount_price ? (float) $model->discount_price : (float) $item['price'];
                            $lineTotal = $effectivePrice * $item['quantity'];
                        @endphp

                        <div class="flex gap-3 border-b pb-4">
                            <div class="w-16 h-16 bg-gray-100 rounded overflow-hidden flex items-center justify-center shrink-0">
                                @if($model)
                                    <img src="{{ $model->image_url }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-xs text-gray-400">No image</span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                                <p class="text-sm text-gray-500">Qty: {{ $item['quantity'] }}</p>
                                <p class="text-sm font-semibold text-gray-700 mt-1">@currency($lineTotal)</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Items Total</span>
                        <span id="summary_total">@currency($total_amount)</span>
                    </div>
                    <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span id="summary_discount">- @currency($discount_amount)</span>
                    </div>
                    <div id="coupon_discount_display" style="display: {{ (isset($coupon_discount) && $coupon_discount > 0) ? 'flex' : 'none' }};" class="flex justify-between text-green-600">
                        <span>Coupon Discount</span>
                        <span id="summary_coupon_discount">- @currency($coupon_discount ?? 0)</span>
                    </div>
                    <div class="flex justify-between text-base font-semibold text-gray-900 border-t pt-3">
                        <span>Order Total</span>
                        <span id="summary_final">@currency($final_amount)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                       document.querySelector('input[name="_token"]')?.value;

    function applyCoupon() {
        const code = document.getElementById('coupon_input').value.trim();
        if (!code) {
            showMessage('Please enter a coupon code', 'error');
            return;
        }

        const btn = document.getElementById('apply-btn');
        btn.disabled = true;
        btn.textContent = 'Applying...';

        fetch('{{ route("coupon.validate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ coupon_code: code })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the order summary
                updateOrderSummary(data.summary, data.coupon);
                showMessage(data.message, 'success');
                // Show remove button
                document.getElementById('remove-btn').style.display = 'block';
                // Make coupon input readonly so it's still sent with the form
                document.getElementById('coupon_input').readOnly = true;
                document.getElementById('coupon_input').classList.add('bg-gray-100', 'cursor-not-allowed');
            } else {
                showMessage(data.message, 'error');
                document.getElementById('coupon_input').value = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Apply';
        });
    }

    function removeCoupon() {
        if (!confirm('Remove the applied coupon?')) {
            return;
        }

        const btn = document.getElementById('remove-btn');
        btn.disabled = true;
        btn.textContent = 'Removing...';

        fetch('{{ route("coupon.remove") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear coupon input
                document.getElementById('coupon_input').value = '';
                document.getElementById('coupon_input').readOnly = false;
                document.getElementById('coupon_input').classList.remove('bg-gray-100', 'cursor-not-allowed');
                // Update the order summary
                updateOrderSummary(data.summary, null);
                showMessage(data.message, 'success');
                // Hide remove button and status
                document.getElementById('remove-btn').style.display = 'none';
                const statusEl = document.getElementById('coupon_status');
                if (statusEl) statusEl.style.display = 'none';
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Remove';
        });
    }

    function updateOrderSummary(summary, coupon) {
        // Update the right sidebar summary
        document.getElementById('summary_total').textContent = summary.total_amount;
        document.getElementById('summary_discount').textContent = '- ' + summary.discount_amount;
        document.getElementById('summary_final').textContent = summary.final_amount;

        // Update or show coupon discount line
        const couponDiscountDisplay = document.getElementById('coupon_discount_display');
        const mainCouponCard = document.getElementById('main_coupon_discount_card');
        const mainCouponAmount = document.getElementById('main_coupon_discount_amount');

        if (coupon && summary.coupon_discount) {
            document.getElementById('summary_coupon_discount').textContent = '- ' + summary.coupon_discount;
            couponDiscountDisplay.style.display = 'flex';
            
            if (mainCouponCard && mainCouponAmount) {
                mainCouponAmount.textContent = '- ' + summary.coupon_discount;
                mainCouponCard.style.display = 'block';
            }
        } else {
            couponDiscountDisplay.style.display = 'none';
            if (mainCouponCard) mainCouponCard.style.display = 'none';
        }

        // Update the main summary grid (top section)
        const discountCard = document.querySelector('[data-discount-amount]');
        if (discountCard) {
            discountCard.querySelector('p:last-child').textContent = summary.discount_amount;
        }

        const finalCard = document.querySelector('[data-final-amount]');
        if (finalCard) {
            finalCard.querySelector('p:last-child').textContent = summary.final_amount;
        }
    }

    function showMessage(message, type = 'info') {
        const messageDiv = document.getElementById('coupon_message');
        const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' :
                       type === 'error' ? 'bg-red-100 border-red-400 text-red-700' :
                       'bg-blue-100 border-blue-400 text-blue-700';
        
        messageDiv.innerHTML = `<div class="mt-2 border px-4 py-3 rounded ${bgColor}">${message}</div>`;
        
        // Auto-remove message after 5 seconds
        setTimeout(() => {
            messageDiv.innerHTML = '';
        }, 5000);
    }
</script>
@endpush
@endsection
