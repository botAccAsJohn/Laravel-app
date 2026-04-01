<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\RecentlyViewServices;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;


class Product2Controller extends Controller
{
    public function __construct(
        private ProductService $service,
        private RecentlyViewServices $recentService
    ) {
    }

    public function index(): View
    {
        return view('products.index', [
            'products' => $this->service->all(),
            'total_products' => $this->service->count(),
            'page_title' => 'All Products',
        ]);
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    public function show(Product $product): View
    {
        if (Auth::check()) {
            $this->recentService->record(Auth::id(), $product->id);
        }
        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $this->service->create(
            $request->validated(),
            $request->file('image'),
        );
        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->service->update(
            $product,
            $request->validated(),
            $request->file('image'),
        );

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->service->delete($product);

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function exportProducts()
    {
        return Excel::download(new ProductsExport, 'products.csv');
    }

    public function logs(\Illuminate\Http\Request $request)
    {
        $logType = $request->query('type', 'products');
        $validTypes = ['db', 'products', 'orders'];
        
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
