<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductMediaRequest;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\ProductMediaService;
use Illuminate\Http\JsonResponse;

class ProductMediaController extends Controller
{
    public function __construct(private readonly ProductMediaService $media) {}

    public function store(StoreProductMediaRequest $request, Product $product): JsonResponse
    {
        $meta = $request->only(['is_main', 'alt_text', 'sort_order']);

        $media = $request->string('source')->toString() === 'upload'
            ? $this->media->addUpload($product, $request->file('file'), $request->string('type')->toString(), $meta)
            : $this->media->addUrl($product, $request->string('type')->toString(), (string) $request->string('url'), $meta);

        return response()->json(['data' => $media], 201);
    }

    public function destroy(Product $product, ProductMedia $media): JsonResponse
    {
        $this->authorize('update', $product);

        abort_if($media->product_id !== $product->id, 404);

        $this->media->delete($media);

        return response()->json(status: 204);
    }
}
