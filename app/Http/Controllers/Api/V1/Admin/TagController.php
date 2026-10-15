<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tag\StoreTagRequest;
use App\Http\Requests\Admin\Tag\UpdateTagRequest;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(private readonly TagService $tags) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Tag::class);

        return response()->json(['data' => Tag::query()->withCount('products')->orderBy('name')->get()]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->tags->create($request->validated())], 201);
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        return response()->json(['data' => $this->tags->update($tag, $request->validated())]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);

        $this->tags->delete($tag);

        return response()->json(status: 204);
    }
}
