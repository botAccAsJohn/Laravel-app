<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{

    public function authorize(): bool
    {
        $user = $this->user() ?? $this->user('admin');
        $order = $this->route('order');

        return $user && $user->can('update', $order);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,confirmed,processing,shipped,delivered,cancelled,refunded'],
        ];
    }
}
