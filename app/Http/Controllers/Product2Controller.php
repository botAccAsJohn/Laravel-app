<?php

namespace App\Http\Controllers;

use App\Http\Requests\{StoreProductRequest, UpdateProductRequest};
use App\Models\{Product, Category};
use App\Services\{ProductService};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\{Auth};
use Illuminate\Http\{Request};
use Illuminate\View\{View};
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;

class Product2Controller extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ProductService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $query = $request->get('q', '');
        $is_search = !empty($query);

        if ($is_search) {
            $results = $this->service->searchProducts($request, $query);
            $products = $results['products'];
            $total_products = $results['total'];
            $filters = $results['filters'] ?? [];
            $priceRange = $results['priceRange'] ?? ['min' => 0, 'max' => 0];
        } else {
            $allProducts = $this->service->dosomething($request);
            $products = $allProducts['products'];
            $total_products = null;
            $filters = $allProducts['filters'];
            $priceRange = $allProducts['priceRange'];
        }

        $categories = Category::getAllCategoriesFromCache();
        $all_products_count = Product::countFromCache();

        return view('products.indexOld', [
            'products'          => $products,
            'all_products_count'=> $all_products_count,
            'total_products'    => $total_products,
            'is_search'         => $is_search,
            'search_query'      => $query,
            'page_title'        => $is_search ? 'Search Results' : 'All Products',
            'categories'        => $categories,
            'filters'           => $filters,
            'priceRange'        => $priceRange,
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
        $product->load([
            'category',
            'reviews' => function ($query) {
                $query->latest()->take(5);
            },
            'reviews.user'
        ]);

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
        $validTypes = ['db', 'products', 'orders', 'slowquery'];

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
