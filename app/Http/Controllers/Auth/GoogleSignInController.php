<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\RedirectsAfterAuthentication;
use App\Services\GoogleSignInService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * Customer "Continue with Google" sign-in and sign-up. Customers land back on the
 * storefront (or the product page they were viewing), like the password login.
 */
class GoogleSignInController extends Controller
{
    use RedirectsAfterAuthentication;

    public function __construct(private readonly GoogleSignInService $googleSignIn) {}

    public function redirect(): SymfonyRedirectResponse
    {
        abort_unless($this->googleSignIn->isEnabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->googleSignIn->isEnabled(), 404);

        // The customer pressed "Cancel" on Google's consent screen.
        if ($request->filled('error')) {
            return $this->backToLogin('Google sign-in was cancelled.');
        }

        try {
            // Throws when the state check fails (e.g. an expired or replayed callback).
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return $this->backToLogin('Google sign-in was cancelled or didn\'t complete. Please try again.');
        }

        try {
            $user = $this->googleSignIn->customerFor($googleUser);
        } catch (ValidationException $exception) {
            return to_route('login')->withErrors($exception->errors());
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return $this->redirectFor($request);
    }

    private function backToLogin(string $message): RedirectResponse
    {
        return to_route('login')->withErrors(['email' => $message]);
    }
}
