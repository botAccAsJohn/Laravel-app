<?php
// app/Http/Requests/UpdateOrderRequest.php
// Exercise 50.3 — Authorization via OrderPolicy instead of raw guard check.

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Only admins may update order status.
     *
     * $this->route('order') resolves the route-model bound Order instance.
     * OrderPolicy::update() returns true only for admin-guard users.
     * The policy's before() also short-circuits this when admin is detected.
     */
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $this->user()?->can('update', $order)
            ?? \Illuminate\Support\Facades\Auth::guard('admin')->check();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,confirmed,processing,shipped,delivered,cancelled,refunded'],
        ];
    }
}
