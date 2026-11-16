<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admins land in the dashboard and customers on the storefront. The intended URL
 * (e.g. the product page that opened the login modal) wins only when it belongs
 * to the side of the app the user is allowed into. The login modal sends it as an
 * `intended` field; a page that required sign-in leaves it in the session.
 */
trait RedirectsAfterAuthentication
{
    protected function redirectFor(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $intended = $request->session()->pull('url.intended');
        $requested = self::localPath($request->input('intended'));

        if ($requested !== null) {
            $intended = url($requested);
        }
        $intendedPath = is_string($intended) ? trim((string) parse_url($intended, PHP_URL_PATH), '/') : null;
        $isAdminArea = $intendedPath !== null
            && ($intendedPath === 'dashboard' || $intendedPath === 'admin' || str_starts_with($intendedPath, 'admin/'));

        if ($user->isAdmin()) {
            return redirect()->to($intendedPath !== null && $isAdminArea ? $intended : route('dashboard'));
        }

        return redirect()->to($intendedPath !== null && ! $isAdminArea ? $intended : route('home'));
    }

    /**
     * A path on this site ("/products/chair"), or null for anything that could point
     * elsewhere (full URLs, "//host" and "/\host" forms), so it can't be used as an
     * open redirect.
     */
    protected static function localPath(mixed $path): ?string
    {
        if (! is_string($path) || strlen($path) > 2048 || ! str_starts_with($path, '/')) {
            return null;
        }

        if (str_starts_with($path, '//') || str_starts_with($path, '/\\') || preg_match('/[\x00-\x1F]/', $path) === 1) {
            return null;
        }

        return $path;
    }
}
