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
use Illuminate\Support\Facades\Concurrency;

class Product2Controller extends Controller
{
    public function __construct(
        private ProductService $service,
        private RecentlyViewServices $recentService
    ) {
    }

    public function index(Request $request): View
    {
        $allProducts = $this->service->dosomething($request);
        $categories = Category::getAllCategoriesFromCache();

        $paginator = $allProducts['products'];
        $totalFound = $paginator->total();

        return view('products.indexOld', [
            'products' => $paginator,
            'total_products' => $totalFound,
            'all_products_count' => Product::countFromCache(),
            'page_title' => 'All Products',
            'categories' => $categories,
            'filters' => $allProducts['filters'],
            'priceRange' => $allProducts['priceRange'],
        ]);
    }


    // public function index(Request $request): View|string
    // {
    //     // $products = Product::getAllProductsFromCache();
    //     // $products = new \Illuminate\Support\Collection($products);
    //     [$freatured, $new, $onsale, $all] = Concurrency::run([
    //         fn() => Product::getAllProductsFromCache()->where('is_featured', true)->take(8)->values(),
    //         fn() => Product::getAllProductsFromCache()->sortByDesc('created_at')->take(8)->values(),
    //         fn() => Product::getAllProductsFromCache()->whereNotNull('discount_price')->take(8)->values(),
    //         fn() => Product::getAllProductsFromCache(),
    //     ]);

    //     // return view('products.index', [
    //     //     'products' => $paginator,
    //     //     'total_products' => $totalFound,
    //     //     'all_products_count' => Product::countFromCache(),
    //     //     'page_title' => 'All Products',
    //     //     'categories' => $categories,
    //     //     'filters' => $allProducts['filters'],
    //     //     'priceRange' => $allProducts['priceRange'],
    //     // ]);
    //     // $isSearch = $request->anyFilled(['categories', 'min_price', 'max_price', 'in_stock', 'on_sale', 'sort', 'page']);
    //     // return "Hello !!";
    //     $isSearch = false;
    //     if (!$isSearch) {
    //         return view('products.index', [
    //             'is_search' => false,
    //             'featured' => $freatured,
    //             'new_arrivals' => $new,
    //             'on_sale' => $onsale,
    //             'best_sellers' => $all->sortByDesc('sales_count')->take(10)->values(),
    //             'categories' => Category::getAllCategoriesFromCache(),
    //             'all_products_count' => Product::countFromCache(),
    //             'filters' => [],
    //             'priceRange' => ['min' => 0, 'max' => 5000],
    //         ]);
    //     }
    //     $allProducts = $this->service->dosomething($request);
    //     $paginator = $allProducts['products'];
    //     return view('products.index', [
    //         'is_search' => true,
    //         'products' => $paginator,
    //         'total_products' => $paginator->total(),
    //         'all_products_count' => Product::countFromCache(),
    //         'categories' => Category::getAllCategoriesFromCache(),
    //         'filters' => $allProducts['filters'],
    //         'priceRange' => $allProducts['priceRange'],
    //     ]);
    // }

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
