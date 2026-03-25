@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Create Product</h1>
        <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900">Back to List</a>
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

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded-lg p-6">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Slug</label>
            <input type="text" name="slug" value="{{ old('slug') }}" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Description</label>
            <textarea name="description" rows="4" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>{{ old('description') }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Price</label>
            <input type="number" step="0.01" name="price" value="{{ old('price') }}" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">category</label>
            <input type="text" name="category" value="{{ old('category') }}" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Image</label>
            <input type="file" name="image" value="{{ old('image') }}" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-6 flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="mr-2">
            <label class="text-gray-700 font-bold">Is Active</label>
        </div>

        <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700 transition">Save Product</button>
    </form>
</div>
@endsection