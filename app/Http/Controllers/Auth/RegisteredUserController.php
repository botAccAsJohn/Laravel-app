<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            // Exercise 52.2: Strong password rules — same policy on registration
            // and reset so users can never set a weak password.
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(), // k-Anonymity HIBP check
            ],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign default customer role so Spatie role/permission checks work.
        $user->assignRole('customer');

        // Seed the history table with the first password hash so future
        // resets immediately have a baseline to check against.
        PasswordHistory::create([
            'user_id'  => $user->id,
            'password' => $user->password,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $sessionCart = session()->get('cart', []);
        app(\App\Services\CartService::class)->mergeSessionCart($sessionCart);

        Log::channel('security')->info('New user registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect(route('dashboard', absolute: false));
    }
}
