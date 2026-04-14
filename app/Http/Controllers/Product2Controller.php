<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Category;
use App\Services\ProductService;
use App\Services\RecentlyViewServices;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;

class Product2Controller extends Controller
{
    public function __construct(
        private ProductService $service,
        private RecentlyViewServices $recentService
    ) {}

    public function index(Request $request): View
    {
        $allProducts = Product::getAllProductsFromCache();
        $categories = Category::getAllCategoriesFromCache();

        $result = $this->service->filterProducts($allProducts, $request);
        $productsList = $result['products'];
        $totalFound = $productsList->count();

        return view('products.index', [
            'products' => $productsList->paginate(12),
            'total_products' => $totalFound,
            'all_products_count' => Product::countFromCache(),
            'page_title' => 'All Products',
            'categories' => $categories,
            'filters' => $result['filters'],
            'priceRange' => $result['priceRange'],
        ]);

    }

    public function create(): View
    {
        $categories = Category::getAllCategoriesFromCache();
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
        $categories = Category::getAllCategoriesFromCache();
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
