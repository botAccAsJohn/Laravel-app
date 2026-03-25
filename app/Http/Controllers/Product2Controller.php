<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ProductService;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class Product2Controller extends Controller
{

    public function __construct(private ProductService $service) {}

    public function index()
    {
        return view('products.index', ['products' => $this->service->all(), 'total_products' => $this->service->count(), 'page_title' => 'All Products']);
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            // Check if file is valid, store in storage/app/public/products, and save path
            $path = $request->file('image')->store('products', 'public');
            $validated['image_path'] = $path;
        }

        $this->service->create($validated);
        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    // Route model binding — Laravel resolves Product automatically
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();
        if ($request->hasFile('image')) {
            // Check if file is valid, store in storage/app/public/products, and save path
            $path = $request->file('image')->store('products', 'public');
            $validated['image_path'] = $path;
            // Optional: You could also add logic here to delete the old image using Storage::disk('public')->delete($product->image_path);
            if ($product->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_path);
            }
        }

        $this->service->update($product, $validated);
        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->service->delete($product);
        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }
}
