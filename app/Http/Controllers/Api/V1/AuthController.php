<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
        private readonly CreateNewUser $createNewUser,
    ) {}

    /**
     * Register a new customer account. Mirrors the web registration flow and
     * establishes the same session, so subsequent /api/v1 requests from this
     * stateful SPA are authenticated via the session cookie.
     */
    public function register(Request $request): JsonResponse
    {
        $this->ensureStateful($request);

        $user = $this->createNewUser->create($request->all());

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['data' => $user], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->authentication->resolveUser($request->string('identifier')->toString(), $request->string('password')->toString());

        if ($user === null) {
            event(new Failed('web', null, ['identifier' => $request->string('identifier')->toString()]));

            throw ValidationException::withMessages([
                'identifier' => 'These credentials do not match our records.',
            ]);
        }

        $this->ensureStateful($request);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['data' => $user]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(status: 204);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    /**
     * These endpoints sign in by session cookie (Sanctum stateful SPA mode), which only
     * exists for requests from a configured first-party origin. Anything else gets a
     * clear 400 instead of failing with "Session store not set" (a 500).
     */
    private function ensureStateful(Request $request): void
    {
        abort_unless(
            $request->hasSession(),
            400,
            'Session authentication is only available to the first-party site (see SANCTUM_STATEFUL_DOMAINS).',
        );
    }
}
