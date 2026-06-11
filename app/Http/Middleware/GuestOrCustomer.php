<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestOrCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if(is_guest()){ // 1. Check if the user is not authenticated (a guest)
            return $next($request); // Allow guests to proceed
        }
        if(!current_user()->hasRole('customer')){ // 2. If the user IS logged in, check if they have the 'customer' role
            abort(403, 'Access denied.'); // Block admins or other authenticated non-customers
        }
        return $next($request); // 3. Allow logged-in customers to proceed
    }
}