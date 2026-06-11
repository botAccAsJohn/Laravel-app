<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\ExternalApiException;
use Illuminate\Support\Facades\Log;

class FakeStoreController extends Controller
{
    public function index()
    {
        try {
            $response = Http::jsonApi()
                ->timeout(15)
                ->get('/products')
                ->throw()
                ->throwIf(
                    fn ($response) => !$response->json(),
                    new ExternalApiException('The external service returned an empty response.')
                );

            $products = $response->json();

            return view('fakestore.index', compact('products'));
        } catch (RequestException $e) {
            Log::error('FakeStore API returned an error: ' . $e->getMessage());
            throw new ExternalApiException('The external service returned an error. Please try again later.');
        } catch (ConnectionException $e) {
            Log::error('FakeStore API unreachable: ' . $e->getMessage());
            throw new ExternalApiException('Could not connect to the external service. Please try again later.');
        }
    }
}
