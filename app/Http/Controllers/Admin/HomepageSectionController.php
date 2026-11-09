<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomepageSection\StoreHomepageSectionRequest;
use App\Http\Requests\Admin\HomepageSection\UpdateHomepageSectionRequest;
use App\Http\Resources\HomepageSectionResource;
use App\Models\HomepageSection;
use App\Services\BannerImageService;
use App\Services\HomepageSectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HomepageSectionController extends Controller
{
    public function __construct(private readonly HomepageSectionService $sections) {}

    public function index(): Response
    {
        $this->authorize('viewAny', HomepageSection::class);

        return Inertia::render('admin/homepage-sections/Index', [
            'sections' => HomepageSectionResource::collection(
                HomepageSection::query()->orderBy('sort_order')->get(),
            )->resolve(),
            'imageSizes' => BannerImageService::SIZES,
        ]);
    }

    public function store(StoreHomepageSectionRequest $request): RedirectResponse
    {
        $this->sections->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Homepage section created.']);

        return to_route('admin.homepage-sections.index');
    }

    public function update(UpdateHomepageSectionRequest $request, HomepageSection $homepageSection): RedirectResponse
    {
        $this->sections->update($homepageSection, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Homepage section updated.']);

        return to_route('admin.homepage-sections.index');
    }

    public function destroy(HomepageSection $homepageSection): RedirectResponse
    {
        $this->authorize('delete', $homepageSection);

        $this->sections->delete($homepageSection);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Homepage section deleted.']);

        return to_route('admin.homepage-sections.index');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:homepage_sections,id'],
            'action' => ['required', Rule::in(['delete', 'activate', 'deactivate'])],
        ]);

        $sections = HomepageSection::query()->whereKey($data['ids'])->get();

        foreach ($sections as $section) {
            $this->authorize($data['action'] === 'delete' ? 'delete' : 'update', $section);
        }

        if ($data['action'] === 'delete') {
            $sections->each(fn (HomepageSection $section) => $this->sections->delete($section));
        } else {
            $sections->each(fn (HomepageSection $section) => $section->update([
                'is_active' => $data['action'] === 'activate',
            ]));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => count($sections).' banners '.($data['action'] === 'delete' ? 'deleted.' : 'updated.'),
        ]);

        return to_route('admin.homepage-sections.index');
    }
}
