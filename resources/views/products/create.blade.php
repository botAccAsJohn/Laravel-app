@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">{{ __('products.add_product') }}</h1>
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

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data"
          class="bg-white shadow-md rounded-lg p-6 space-y-4">
        @csrf

        {{-- Name --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.name') }} <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
        </div>

        {{-- Slug --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.slug') }}</label>
            <input type="text" name="slug" value="{{ old('slug') }}"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="{{ __('products.slug_placeholder') }}">
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.description') }}</label>
            <textarea name="description" rows="4"
                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
        </div>

        {{-- Price --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.price') }} ({{ config('app.currency') }}) <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" name="price" value="{{ old('price') }}" min="0.01"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
        </div>

        {{-- Discount Price --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-bold mb-1">{{ __('products.discount_price') }} ({{ config('app.currency') }})
                    <span class="text-gray-400 font-normal text-sm">— {{ __('products.optional') }}</span>
                </label>
                <input type="number" step="0.01" name="discount_price" value="{{ old('discount_price') }}" min="0.01"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            {{-- Quantity (Stock) --}}
            <div>
                <label class="block text-gray-700 font-bold mb-1">{{ __('products.stock') }} <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" value="{{ old('quantity', 0) }}" min="0" step="1"
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
            </div>
        </div>

        {{-- Category --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.category') }}</label>
            <select name="category_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">— None —</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
            <input type="text" name="tags" value="{{ old('tags') }}"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="sale, new-arrival, featured">
        </div>

        {{-- Image --}}
        <div>
            <label class="block text-gray-700 font-bold mb-1">{{ __('products.image') }}</label>
            <input type="file" name="image" accept="image/jpg,image/jpeg,image/png"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        {{-- Is Active --}}
        <div class="flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', 1) ? 'checked' : '' }} class="mr-2">
            <label for="is_active" class="text-gray-700 font-bold">{{ __('products.active') }}</label>
        </div>

        <button type="submit"
                class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700 transition">
            {{ __('products.save_product') }}
        </button>
    </form>
</div>
@endsection