<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\PageViewService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PageViewService $pageViews): Response|RedirectResponse
    {
        $user = $request->user();

        // The dashboard is admin-only; customers use the storefront.
        if (! $user->isAdmin()) {
            return redirect()->route('home');
        }

        $can = [
            'products' => $user->hasPermission('products.view'),
            'categories' => $user->hasPermission('categories.view'),
            'brands' => $user->hasPermission('brands.view'),
            'tags' => $user->hasPermission('tags.view'),
            'enquiries' => $user->hasPermission('enquiries.view') || $user->hasPermission('enquiries.manage'),
            'customers' => $user->hasPermission('customers.view'),
            'admins' => $user->hasPermission('admins.view'),
            'settings' => $user->hasPermission('settings.view'),
            'roles' => $user->hasPermission('roles.view'),
            'activity' => $user->hasPermission('activity_logs.view'),
        ];

        // Storefront traffic is shown to every admin; it holds no customer data.
        $viewTrend = $pageViews->lastDays(14);
        $lastSeven = array_slice($viewTrend, -7);

        return Inertia::render('Dashboard', [
            'permissions' => $can,
            'pageViews' => [
                'today' => end($viewTrend)['value'] ?? 0,
                'lastSevenDays' => array_sum(array_column($lastSeven, 'value')),
                'trend' => array_map(fn (array $day) => ['label' => $day['label'], 'value' => $day['value']], $viewTrend),
            ],
            'stats' => [
                'products' => $can['products'] ? [
                    'total' => Product::query()->count(),
                    'published' => Product::query()->published()->count(),
                    'draft' => Product::query()->where('status', 'draft')->count(),
                ] : null,
                'categories' => $can['categories'] ? ['total' => Category::query()->count()] : null,
                'subcategories' => $can['categories'] ? ['total' => Subcategory::query()->count()] : null,
                'brands' => $can['brands'] ? ['total' => Brand::query()->count()] : null,
                'customers' => $can['customers'] ? ['total' => User::query()->customers()->count()] : null,
                'admins' => $can['admins'] ? ['total' => User::query()->admins()->count()] : null,
            ],
            'categoryBreakdown' => $can['products'] && $can['categories']
                ? Category::query()
                    ->withCount('products')
                    ->orderByDesc('products_count')
                    ->limit(6)
                    ->get(['id', 'name'])
                    ->map(fn (Category $category) => [
                        'label' => $category->name,
                        'value' => $category->products_count,
                    ])
                : null,
            'monthlyTrend' => $can['products'] ? $this->monthlyProductTrend() : null,
            'lowStock' => $can['products']
                ? Product::query()
                    ->where(fn ($query) => $query
                        ->whereIn('stock_status', ['out_of_stock', 'backorder'])
                        ->orWhere('stock_quantity', '<=', 5))
                    ->orderBy('stock_quantity')
                    ->limit(6)
                    ->get(['id', 'name', 'sku', 'stock_quantity', 'stock_status'])
                : null,
            'recentActivity' => $can['activity']
                ? Activity::query()
                    ->with('causer')
                    ->latest()
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get()
                    ->map(fn (Activity $activity) => [
                        'id' => $activity->id,
                        'action' => $activity->event ?? $activity->description,
                        'description' => $activity->description,
                        'created_at' => $activity->created_at?->toIso8601String(),
                        'user' => $activity->causer instanceof User ? ['name' => $activity->causer->name] : null,
                    ])
                : null,
        ]);
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function monthlyProductTrend(): array
    {
        $start = Carbon::now()->subMonths(5)->startOfMonth();

        $counts = Product::query()
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn (CarbonImmutable $date) => $date->format('Y-m'));

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $months[] = [
                'label' => $month->format('M'),
                'value' => $counts->get($key, 0),
            ];
        }

        return $months;
    }
}
