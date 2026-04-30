<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class FakeStoreController extends Controller
{
    public function index()
    {
        // Use our registered macro
        $response = Http::jsonApi()->get('/products');

        if ($response->serverError()) {
            return response()->view('errors.external-api', [
                'message' => 'The external service is currently unavailable. Please try again later.'
            ], 500);
        }

        $products = $response->json();

        return view('fakestore.index', compact('products'));
    }
}
