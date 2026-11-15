<?php

namespace App\Http\Middleware;

use App\Services\PageViewService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CountPageViews
{
    /** User agents of crawlers, link previewers and scripts, which are not visitors. */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|monitor|lighthouse|headless|curl|wget|python|httpclient/i';

    public function __construct(private readonly PageViewService $pageViews) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Counted after the response is sent, so it never slows a page down.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($this->isStorefrontPageView($request, $response)) {
            $this->pageViews->record();
        }
    }

    /**
     * A view is a successful storefront page load (a full page or an Inertia page
     * visit) by a person: not the admin area, the API, a prefetch, a partial reload,
     * a bot, or an admin browsing their own site.
     */
    private function isStorefrontPageView(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }

        if ($request->is('admin', 'admin/*', 'dashboard', 'dashboard/*', 'api/*', 'up')) {
            return false;
        }

        if ($request->prefetch() || $request->hasHeader('X-Inertia-Partial-Component')) {
            return false;
        }

        $isPage = $request->hasHeader('X-Inertia')
            || str_contains((string) $response->headers->get('Content-Type'), 'text/html');

        if (! $isPage) {
            return false;
        }

        if (preg_match(self::BOT_PATTERN, (string) $request->userAgent()) === 1) {
            return false;
        }

        return ! ($request->user()?->isAdmin() ?? false);
    }
}
