<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tag\StoreTagRequest;
use App\Http\Requests\Admin\Tag\UpdateTagRequest;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(private readonly TagService $tags) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Tag::class);

        return Inertia::render('admin/tags/Index', [
            'tags' => Tag::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $this->tags->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tag created.']);

        return to_route('admin.tags.index');
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $this->tags->update($tag, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tag updated.']);

        return to_route('admin.tags.index');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $this->tags->delete($tag);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tag deleted.']);

        return to_route('admin.tags.index');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:tags,id'],
            'action' => ['required', Rule::in(['delete'])],
        ]);

        $tags = Tag::query()->whereKey($data['ids'])->get();
        if ($tags->count() !== count($data['ids'])) {
            throw ValidationException::withMessages(['ids' => 'One or more selected tags are unavailable.']);
        }

        foreach ($tags as $tag) {
            $this->authorize('delete', $tag);
        }

        if ($tags->contains(fn (Tag $tag) => $tag->products()->exists())) {
            throw ValidationException::withMessages([
                'ids' => 'Tags assigned to products cannot be deleted.',
            ]);
        }

        $tags->each(fn (Tag $tag) => $this->tags->delete($tag));

        Inertia::flash('toast', ['type' => 'success', 'message' => count($tags).' tags deleted.']);

        return to_route('admin.tags.index');
    }
}
