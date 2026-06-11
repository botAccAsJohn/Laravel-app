<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreOrderRequest extends FormRequest
{

    public function authorize(): bool
    {
        $user = $this->user() ?? $this->user('admin');

        return $user && $user->can('create', Order::class);
    }

    public function rules(): array
    {
        return [
            'address'        => ['required', 'string', 'max:255'],
            'phone'          => ['nullable', 'numeric', 'digits:10'],
            'payment_method' => ['required', 'in:card,upi,wallet,cod,emi,netbanking'],
            'coupon_code'    => ['nullable', 'string', 'max:50'],
        ];
    }
}
