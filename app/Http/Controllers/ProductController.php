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
use Illuminate\Support\Facades\{Gate};

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ProductService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);
        $results = $this->service->getProducts($request);
        return view('products.indexOld', [
            'products'          => $results['products'],
            'total_products'    => $results['total'],
            'is_search'         => !empty($request->get('q')),
            'search_query'      => $request->get('q', ''),
            'page_title'        => !empty($request->get('q')) ? 'Search Results' : 'All Products',
            'categories'        => Category::getAllCategoriesFromCache(),
            'filters'           => $results['filters'],
            'priceRange'        => $results['priceRange'],
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

        $recentlyViewKey = app(\App\Services\RecentlyViewServices::class)->resolveRecentlyViewedKey();
        event(new \App\Events\Behavior\ProductViewed($product->id, $recentlyViewKey));
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

    public function logs(Request $request)
    {
        $logType = $request->query('type', 'products');
        $validTypes = ['db', 'products', 'orders', 'slowquery'];

        if (!in_array($logType, $validTypes)) {
            $logType = 'products';
        }

        $logs = [];
        try {
            $allLogs = $this->service->getLogs($logType);
            // Filter out empty lines and grab the last 50 rows
            $logs = array_slice(array_filter($allLogs, fn($line) => trim($line) !== ''), -50);
        } catch (\Exception $e) {
            $logs = ["Log file for '{$logType}' not found today."];
        }
        return view('logs.index', compact('logs', 'logType'));
    }
}
