<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class Product2Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // Call : GET => 127.0.0.1:8000/products
    public function index(): View
    {
        $products = ["Laptop", "Phone", "Tablet"];
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return "inside the product2 controller : create";
    }

    /**
     * Store a newly created resource in storage.
     */
    // Call : POST => 127.0.0.1:8000/products
    public function store(): RedirectResponse
    {
        return redirect('/products')->with('success', 'Product added!');
    }

    /**
     * Display the specified resource.
     */
    // Call : GET => 127.0.0.1:8000/products/1
    public function show($id): JsonResponse
    {
        $product = [
            "id" => $id,
            "name" => "Laptop",
            "price" => 50000
        ];
        return response()->json($product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        return "inside the product2 controller : edit";
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        return "inside the product2 controller : update";
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        return "inside the product2 controller : destroy";
    }

    // Call : GET => 127.0.0.1:8000/download
    public function download()
    {
        $path = public_path('robots.txt');
        return response()->download($path);
    }

    // Call : GET => 127.0.0.1:8000/custom
    public function custom()
    {
        /** @phpstan-ignore-next-line */
        return response()->success([
            "name" => "Laptop",
            "price" => 50000
        ]);
    }
}
