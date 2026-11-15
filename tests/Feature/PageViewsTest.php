<?php

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;

function viewsOn(string $date): int
{
    return (int) DB::table('daily_page_views')->where('date', $date)->value('views');
}

beforeEach(function () {
    app(SettingsService::class)->set('general', 'timezone', 'Asia/Dhaka');
    $this->travelTo(now('Asia/Dhaka')->setTime(12, 0));
});

test('each storefront page view by a visitor is counted for today', function () {
    $this->get(route('home'))->assertOk();
    $this->get(route('about'))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('catalogue'))->assertOk();

    expect(viewsOn(now('Asia/Dhaka')->toDateString()))->toBe(3);
});

test('requests that are not visitor page views are not counted', function (callable $request) {
    $request($this);

    expect(DB::table('daily_page_views')->sum('views'))->toEqual(0);
})->with([
    'admin login page' => [fn ($test) => $test->get(route('admin.login'))],
    'api request' => [fn ($test) => $test->getJson(route('api.v1.categories.index'))],
    'not found page' => [fn ($test) => $test->get('/no-such-page')],
    'sitemap' => [fn ($test) => $test->get(route('sitemap'))],
    'search engine bot' => [fn ($test) => $test->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get(route('home'))],
    'link prefetch' => [fn ($test) => $test->withHeader('Purpose', 'prefetch')->get(route('home'))],
    'admin browsing the storefront' => [fn ($test) => $test->actingAs(User::factory()->admin()->create())->get(route('home'))],
]);

test('days follow the site timezone, not UTC', function () {
    // 19:00 UTC is already 01:00 the next day in Dhaka (UTC+6).
    $this->travelTo(now('UTC')->setDate(2026, 11, 15)->setTime(19, 0));

    $this->get(route('home'));

    expect(viewsOn('2026-11-16'))->toBe(1)
        ->and(viewsOn('2026-11-15'))->toBe(0);
});

test('the dashboard shows today, the last seven days and a 14 day trend', function () {
    $today = now('Asia/Dhaka')->startOfDay();
    DB::table('daily_page_views')->insert([
        ['date' => $today->toDateString(), 'views' => 5],
        ['date' => $today->copy()->subDays(3)->toDateString(), 'views' => 7],
        ['date' => $today->copy()->subDays(10)->toDateString(), 'views' => 4],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('pageViews.today', 5)
            ->where('pageViews.lastSevenDays', 12)
            ->has('pageViews.trend', 14)
            ->where('pageViews.trend.13.value', 5)
            ->where('pageViews.trend.3.value', 4));
});
