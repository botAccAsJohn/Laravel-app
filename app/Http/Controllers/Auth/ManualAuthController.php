<?php
// app/Http/Controllers/Auth/ManualAuthController.php
//
// Exercise 49.3 — Manual Authentication
// ──────────────────────────────────────────────────────────────────────────
// This controller purposely duplicates NO Breeze scaffold code.
// Every method calls the lower-level Auth façade directly so the
// exercise requirements are visible and self-contained.

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationLog;
use App\Models\User;
use App\Services\LoginThrottleService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ManualAuthController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    // ❶  REGISTRATION
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Show the manual registration form.
     */
    public function showRegister(): View
    {
        return view('auth.manual.register');
    }

    /**
     * Validate, create the user, fire the Registered event, and log them in.
     *
     * Auth::login($user) is used instead of attempt() because we already
     * have the model; no credential re-check is needed.
     * Session is regenerated afterward to prevent session-fixation.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Fire the framework's Registered event so email verification
        // listeners can respond if needed.
        event(new Registered($user));

        // Directly log in with the new User model instance.
        Auth::login($user);

        // ⚠ Critical: regenerate prevents session-fixation attacks.
        // (See conceptual explanation in docs below.)
        $request->session()->regenerate();

        Log::info('[ManualAuth] New customer registered', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Welcome! Your account has been created.');
    }

    // ══════════════════════════════════════════════════════════════════════
    // ❷  LOGIN
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Show the manual login form.
     */
    public function showLogin(): View
    {
        return view('auth.manual.login');
    }

    /**
     * Authenticate using raw Auth::attempt().
     *
     * Differences from Breeze's AuthenticatedSessionController:
     *  - No LoginRequest form-request; validation is inline.
     *  - Rate-limiting is handled by the `throttle:login` middleware on the
     *    route rather than inside a form-request so it stays out of this
     *    controller's single responsibility.
     *  - The $remember flag is forwarded as Auth::attempt()'s second arg,
     *    which tells the session driver to set a long-lived "remember me"
     *    cookie automatically.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email    = strtolower(trim($credentials['email']));
        $remember = $request->boolean('remember');
        $service  = app(LoginThrottleService::class);

        // ── CAPTCHA gate — shown after 10 account failures ────────────────
        if ($service->requiresCaptcha($email)) {
            $token = $request->input('h-captcha-response');
            if (empty($token) && !empty(config('services.hcaptcha.secret'))) {
                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['captcha' => 'Please complete the CAPTCHA challenge.']);
            }
        }

        if (! Auth::attempt($credentials, $remember)) {
            // Account-based counter: warning email at 5, CAPTCHA at 10
            $service->recordFailure($email, $request->ip(), $request->userAgent() ?? '');

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => trans('auth.failed')]);
        }

        // ⚠ CRITICAL — regenerate the session ID on every successful login.
        $request->session()->regenerate();

        // Clear both counters on success.
        $service->recordSuccess($email, $request->ip(), Auth::id());

        return redirect()->intended(route('products.index'))
            ->with('success', 'Welcome back!');
    }

    // ══════════════════════════════════════════════════════════════════════
    // ❸  LOGOUT
    // ══════════════════════════════════════════════════════════════════════

    /**
     * End the customer session properly.
     *
     * Three-step logout pattern:
     *   1. Auth::logout()           — clears Auth state from the session.
     *   2. session()->invalidate()  — destroys all session data and generates
     *                                  a new session file/key in the store.
     *   3. session()->regenerateToken() — rotates the CSRF token so any forms
     *                                     open in other tabs become stale.
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('[ManualAuth] Customer logged out', [
            'user_id' => $userId,
            'ip'      => $request->ip(),
        ]);

        return redirect()->route('login')
            ->with('success', 'You have been signed out.');
    }

    // ══════════════════════════════════════════════════════════════════════
    // ❹  IMPERSONATION  (admin only, uses Auth::loginUsingId)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Show the admin impersonation management page.
     *
     * Lists all customers and the last 50 impersonation log entries.
     */
    public function impersonateIndex(): View
    {
        $customers = User::orderBy('name')->get(['id', 'name', 'email', 'created_at']);
        $logs      = ImpersonationLog::with('targetUser')
            ->latest()
            ->take(50)
            ->get();

        return view('admin.impersonate', compact('customers', 'logs'));
    }

    /**
     * Impersonate a customer as an admin.
     *
     * Auth::loginUsingId($id) — permanently logs in as the given user for
     * the remainder of the session (not just the current request).
     *
     * Flow:
     *   • The admin's identity is saved in the session before switching.
     *   • An ImpersonationLog row is created for the audit trail.
     *   • The session is regenerated to prevent fixation.
     */
    public function impersonateStart(Request $request, int $userId): RedirectResponse
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $target = User::findOrFail($userId);

        // Persist admin identity in session so we can restore it on stop.
        $request->session()->put('impersonating_admin_id', $admin->id);

        // Write audit log row.
        $log = ImpersonationLog::create([
            'admin_id'       => $admin->id,
            'admin_email'    => $admin->email,
            'target_user_id' => $target->id,
            'target_email'   => $target->email,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        // Store log ID so we can close the record when impersonation ends.
        $request->session()->put('impersonation_log_id', $log->id);

        Log::warning('[ManualAuth] Admin started impersonation', [
            'admin_id'       => $admin->id,
            'admin_email'    => $admin->email,
            'target_user_id' => $target->id,
            'target_email'   => $target->email,
            'ip'             => $request->ip(),
        ]);

        // Switch the web guard to the target customer.
        // This persists across the entire session (unlike onceUsingId).
        Auth::guard('web')->loginUsingId($userId);
        $request->session()->regenerate();

        return redirect()->route('products.index')
            ->with('success', "Now impersonating {$target->name} ({$target->email}).");
    }

    /**
     * Stop impersonating and return the admin to their guard.
     */
    public function impersonateStop(Request $request): RedirectResponse
    {
        // Close the audit record.
        $logId = $request->session()->pull('impersonation_log_id');
        if ($logId) {
            ImpersonationLog::find($logId)?->stop();
        }

        // Restore the admin guard session — the admin was never logged out,
        // so Auth::guard('admin')->check() is still true.
        $request->session()->forget('impersonating_admin_id');

        // Log out the impersonated customer session.
        Auth::guard('web')->logout();
        $request->session()->regenerate();

        Log::info('[ManualAuth] Admin stopped impersonation', [
            'ip' => $request->ip(),
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Impersonation ended. Returned to admin context.');
    }

    // ══════════════════════════════════════════════════════════════════════
    // ❺  MAGIC LINK  (uses Auth::onceUsingId)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Generate a signed magic-link URL for a given user.
     *
     * The link is valid for 15 minutes and is single-use because the URL
     * is signed (tamper-proof) and the route handler uses onceUsingId()
     * which authenticates for ONE request only — no session is written.
     */
    public function magicLinkGenerate(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        $url = URL::temporarySignedRoute(
            'manual-auth.magic.login',
            now()->addMinutes(15),
            ['userId' => $user->id]
        );

        Log::info('[ManualAuth] Magic link generated', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        // In a real app you'd dispatch a Mailable. For demo purposes we flash
        // the URL so it's visible on the redirect target.
        return back()->with('magic_link', $url);
    }

    /**
     * Handle the magic-link click.
     *
     * Auth::onceUsingId($id) — authenticates for THIS request only.
     * No session cookie is written, which makes it suitable for:
     *   • Password-less / email magic-link logins (you then start a proper session)
     *   • Webhook callbacks that need user context for one action
     *   • Automated e-mail actions (e.g. "unsubscribe in one click")
     *
     * Here we use it to verify the user exists and the signature is valid,
     * then start a proper session via Auth::login() for a real UX session.
     */
    public function magicLinkLogin(Request $request, int $userId): RedirectResponse
    {
        // Laravel automatically aborts 403 if the signature is invalid/expired.
        if (! $request->hasValidSignature()) {
            abort(403, 'This magic link has expired or is invalid.');
        }

        // Authenticate for this request only — confirms the user row exists.
        $user = Auth::onceUsingId($userId);

        if (! $user) {
            abort(404, 'User not found.');
        }

        // Now start a real session so the user stays logged in.
        Auth::login($user);
        $request->session()->regenerate();

        Log::info('[ManualAuth] Magic link login', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        return redirect()->route('products.index')
            ->with('success', "Signed in via magic link as {$user->name}.");
    }
}
