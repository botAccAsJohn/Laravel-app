<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreOrderRequest extends FormRequest
{

    public function authorize(): bool
    {
        // Admin guard users (e.g. admin testing checkout directly, not impersonating) — always pass.
        if (Auth::guard('admin')->check() && !is_impersonating()) {
            return true;
        }

        // Web-authenticated customers must have the 'place_order' permission.
        return Auth::check() && Auth::user()->can('place_order');
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
