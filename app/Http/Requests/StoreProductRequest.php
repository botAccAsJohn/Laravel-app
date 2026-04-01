<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
        ];
    }
}
