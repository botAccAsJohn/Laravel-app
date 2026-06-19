<?php

namespace App\Http\Requests\Auth;

use App\Models\PasswordHistory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Exercise 52.2 — Strong Password FormRequest for password reset.
 *
 * Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
 * ─────────────────────────────────────────────────────────────────────
 * • min(12)         — at least 12 characters.
 * • mixedCase()     — at least one uppercase AND one lowercase letter.
 * • numbers()       — at least one digit.
 * • symbols()       — at least one special character (!, @, #, …).
 * • uncompromised() — checks the password against the haveibeenpwned.com
 *                     k-anonymity API WITHOUT ever sending the full password:
 *
 *   1. The password is SHA-1 hashed locally.
 *   2. Only the FIRST 5 characters of the hash are sent to the API.
 *   3. HIBP returns all hash suffixes that share that 5-char prefix.
 *   4. Laravel compares the remaining hash characters client-side.
 *   5. The full hash — let alone the plain-text — NEVER leaves the app.
 *
 *   This is the k-Anonymity model: even HIBP cannot determine which
 *   specific password is being queried.
 */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                // Exercise 52.2: Strong password rules
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(), // k-Anonymity HIBP check — see above
            ],
        ];
    }

    /**
     * After-validation hook: block reuse of the last 5 passwords.
     *
     * We load the last 5 hashes for this user's email and use
     * Hash::check() against each — the only safe way to compare bcrypt hashes.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var \App\Models\User|null $user */
                $user = \App\Models\User::where('email', $this->input('email'))->first();

                if (! $user) {
                    return; // unknown user — Password::reset() will handle it
                }

                $newPassword = $this->input('password');

                $reused = PasswordHistory::where('user_id', $user->id)
                    ->latest('created_at')
                    ->limit(5)
                    ->get()
                    ->first(fn ($history) => \Illuminate\Support\Facades\Hash::check($newPassword, $history->password));

                if ($reused) {
                    $validator->errors()->add(
                        'password',
                        'This password was used recently. Please choose a different password.'
                    );
                }
            },
        ];
    }
}
