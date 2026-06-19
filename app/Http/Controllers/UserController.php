<?php

namespace App\Http\Controllers;

use App\Services\ExternalApiService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        protected ExternalApiService $api
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->api->getUsers();
        return response()->json($users);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->api->getUser($id);
        return response()->json($user);
    }

    public function store(): JsonResponse
    {
        $user = $this->api->createUser(request()->all());
        return response()->json($user, 201);
    }
}
