<?php
// app/Http/Requests/UpdateProductRequest.php
// Exercise 50.3 — Authorization centralised in FormRequest.

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Only admins may update products.
     *
     * $this->route('product') resolves the route-model bound Product instance,
     * so we pass the actual object — not just the class. This invokes
     * ProductPolicy::update(?User $user, Product $product) which checks the
     * admin guard and runs through the policy's before() bypass.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        // Admin guard check: $this->user() is null for admin-guard sessions,
        // so we fall back to the guard check directly.
        return $this->user()?->can('update', $product)
            ?? \Illuminate\Support\Facades\Auth::guard('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'discount_price' => ['nullable', 'numeric', 'min:0.01', 'lt:price'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('products', 'slug')->ignore($product)
            ],
            'is_active' => ['required', 'boolean'],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
