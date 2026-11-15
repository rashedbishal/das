<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareTrackingScripts
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Tracking snippets run unescaped, so they are only injected into the public
     * storefront. Admin pages never receive them: a snippet running in an admin's
     * session could act with that admin's permissions.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isAdminArea = $request->is('admin', 'admin/*', 'dashboard', 'dashboard/*');

        View::share('headScripts', $isAdminArea ? '' : (string) $this->settings->get('tracking', 'head_scripts', ''));
        View::share('bodyScripts', $isAdminArea ? '' : (string) $this->settings->get('tracking', 'body_scripts', ''));

        return $next($request);
    }
}
