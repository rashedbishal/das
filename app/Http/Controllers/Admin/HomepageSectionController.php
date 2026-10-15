<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomepageSection\StoreHomepageSectionRequest;
use App\Http\Requests\Admin\HomepageSection\UpdateHomepageSectionRequest;
use App\Models\HomepageSection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HomepageSectionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', HomepageSection::class);

        return Inertia::render('admin/homepage-sections/Index', [
            'sections' => HomepageSection::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreHomepageSectionRequest $request): RedirectResponse
    {
        HomepageSection::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Homepage section created.']);

        return to_route('admin.homepage-sections.index');
    }

    public function update(UpdateHomepageSectionRequest $request, HomepageSection $homepageSection): RedirectResponse
    {
        $homepageSection->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Homepage section updated.']);

        return to_route('admin.homepage-sections.index');
    }

    public function destroy(HomepageSection $homepageSection): RedirectResponse
    {
        $this->authorize('delete', $homepageSection);

        $homepageSection->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Homepage section deleted.']);

        return to_route('admin.homepage-sections.index');
    }
}
