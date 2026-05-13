@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">{{ __('products.edit_product') }}</h1>
        <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('products.back_to_list') }}</a>
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

    <form action="{{ route('products.update', $product->slug) }}" method="POST" enctype="multipart/form-data"
          class="bg-white shadow-md rounded-lg p-6 space-y-4">
        @csrf
        @method('PUT')

        {{-- Name --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.name') }} <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
        </div>

        {{-- Slug --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.slug') }}</label>
            <input type="text" name="slug" value="{{ old('slug', $product->slug) }}"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.description') }}</label>
            <textarea name="description" rows="4"
                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $product->description) }}</textarea>
        </div>

        {{-- Price --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.price') }} ({{ config('app.currency') }}) <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" min="0.01"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
        </div>

        {{-- Discount Price & Stock --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-bold mb-1">{{ __('products.discount_price') }} ({{ config('app.currency') }})
                    <span class="text-gray-400 font-normal text-sm">— {{ __('products.optional') }}</span>
                </label>
                <input type="number" step="0.01" name="discount_price"
                       value="{{ old('discount_price', $product->discount_price) }}" min="0.01"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            {{-- Quantity (Stock) --}}
            <div>
                <label class="block text-gray-700 font-bold mb-1">{{ __('products.stock') }} <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" value="{{ old('quantity', $product->quantity) }}" min="0" step="1"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
            </div>
        </div>

        {{-- Category --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.category') }}</label>
            <select name="category_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">— None —</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tags --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.tags') }}
                <span class="text-gray-400 font-normal text-sm">— {{ __('products.tags_help') }}</span>
            </label>
            <input type="text" name="tags"
                   value="{{ old('tags', $product->tags ? implode(', ', $product->tags) : '') }}"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="sale, new-arrival, featured">
        </div>

        {{-- Current Image --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.current_image') }}</label>
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                 class="h-32 object-cover rounded border border-gray-200">
        </div>

        {{-- Replace Image --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">
                {{ $product->image_path ? __('products.replace_image') : __('products.image') }}
            </label>
            <input type="file" name="image" accept="image/jpg,image/jpeg,image/png"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        {{-- Is Active --}}
        <div class="flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="mr-2">
            <label for="is_active" class="text-gray-700 font-bold">{{ __('products.active') }}</label>
        </div>

        <button type="submit"
                class="w-full bg-yellow-500 text-white font-bold py-2 px-4 rounded hover:bg-yellow-600 transition">
            {{ __('products.update_product') }}
        </button>
    </form>
</div>
@endsection