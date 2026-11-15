<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Baseline hardening headers on every response, plus a Content Security Policy on
     * the admin panel. The storefront has no CSP because admin-managed tracking
     * snippets (e.g. Google Tag Manager) load arbitrary third-party scripts there.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->is('admin', 'admin/*', 'dashboard', 'dashboard/*')) {
            $response->headers->set('Content-Security-Policy', $this->adminPolicy());
        }

        return $response;
    }

    private function adminPolicy(): string
    {
        $scriptSources = ["'self'"];
        $connectSources = ["'self'"];

        // In development, assets and HMR come from the Vite dev server.
        if (Vite::isRunningHot()) {
            $devServer = trim(File::get(public_path('hot')));
            $scriptSources[] = $devServer;
            $connectSources[] = $devServer;
            $connectSources[] = preg_replace('#^http#', 'ws', $devServer);
        }

        $scripts = implode(' ', $scriptSources);
        $connect = implode(' ', $connectSources);

        return implode('; ', [
            "default-src 'self'",
            "script-src {$scripts}",
            "style-src {$scripts} 'unsafe-inline'",
            "img-src 'self' data: blob: https:",
            "media-src 'self' blob: https:",
            "font-src {$scripts} data:",
            "connect-src {$connect}",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
    }
}
