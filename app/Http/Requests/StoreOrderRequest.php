<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'in:card,upi,wallet,cod,emi,netbanking'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
