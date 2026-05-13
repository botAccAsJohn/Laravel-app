<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,confirmed,processing,shipped,delivered,cancelled,refunded'],
        ];
    }
}
