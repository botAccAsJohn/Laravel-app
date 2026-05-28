<?php
// app/Http/Requests/StoreProductRequest.php
//
// Exercise 50.3 — Authorization moved into the FormRequest.
//
// authorize() is called by Laravel BEFORE rules() and BEFORE the controller
// action runs. This centralises "who can do this?" in one place.
//
// $this->user() returns the currently authenticated web-guard user (or null).
// We call ->can('create', Product::class) which resolves to ProductPolicy::create(),
// which in turn checks Auth::guard('admin') via the policy's before() bypass.

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Centralised authorization: only admins may create products.
     *
     * Why here and not in the controller?
     * FormRequest authorization runs at the HTTP layer — before the controller
     * is even instantiated. This makes it impossible to forget the check when
     * adding a new route that reuses the same FormRequest.
     */
    public function authorize(): bool
    {
        // $this->user() gives the web-guard user (null for guests/admins).
        // Passing Product::class (not an instance) resolves to the "create" method.
        return $this->user()?->can('create', Product::class)
            ?? \Illuminate\Support\Facades\Auth::guard('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'price'          => ['required', 'numeric', 'min:0.01'],
            'discount_price' => ['nullable', 'numeric', 'min:0.01', 'lt:price'],
            'description'    => ['nullable', 'string'],
            'category_id'    => ['nullable', 'integer', 'exists:categories,id'],
            'tags'           => ['nullable', 'string'],
            'image'          => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'slug'           => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'is_active'      => ['required', 'boolean'],
            'quantity'       => ['required', 'integer', 'min:0'],
        ];
    }
}
