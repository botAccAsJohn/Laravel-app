<?php

namespace App\Http\Controllers;

use App\Http\Requests\{StoreProductRequest, UpdateProductRequest};
use App\Models\{Product, Category};
use App\Services\{ProductService};
use Illuminate\Support\Facades\{Auth};
use Illuminate\Http\{Request};
use Illuminate\View\{View};
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;

class Product2Controller extends Controller
{
    public function __construct(
        private ProductService $service,
    ) {}

    public function index(Request $request): View
    {
        $allProducts = $this->service->dosomething($request);
        $categories = Category::getAllCategoriesFromCache();

        return view('products.indexOld', [
            'products' => $allProducts['products'],
            'all_products_count' => Product::countFromCache(),
            'page_title' => 'All Products',
            'categories' => $categories,
            'filters' => $allProducts['filters'],
            'priceRange' => $allProducts['priceRange'],
        ]);
    }


    public function create(): View
    {
        $this->authorize('create', Product::class);
        $categories = Category::getAllCategoriesFromCache();
        return view('products.create', compact('categories'));
    }

    public function show(Product $product): View
    {
        $product->load(['reviews' => function ($query) {
            $query->latest()->take(5);
        }, 'reviews.user']);

        event(new \App\Events\Behavior\ProductViewed($product->id, Auth::id()));
        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        $categories = Category::getAllCategoriesFromCache();
        return view('products.edit', compact('product', 'categories'));
    }

    public function store(StoreProductRequest $request)
    {
        // Authorization is handled by StoreProductRequest::authorize() via ProductPolicy.
        $this->service->create(
            $request->validated(),
            $request->file('image'),
        );
        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        // Authorization is handled by UpdateProductRequest::authorize() via ProductPolicy.
        $this->service->update(
            $product,
            $request->validated(),
            $request->file('image'),
        );

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        $this->service->delete($product);
        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function exportProducts()
    {
        $this->authorize('create', Product::class); // admin action
        return Excel::Download(new ProductsExport, 'products.csv');
    }

    public function logs(\Illuminate\Http\Request $request)
    {
        $logType = $request->query('type', 'products');
        $validTypes = ['db', 'products', 'orders','SlowQuery'];

        if (!in_array($logType, $validTypes)) {
            $logType = 'products';
        }

        $logs = [];
        try {
            $logs = $this->service->getLogs($logType);
        } catch (\Exception $e) {
            $logs = ["Log file for '{$logType}' not found today."];
        }

        return view('logs.index', compact('logs', 'logType'));
    }   
}
