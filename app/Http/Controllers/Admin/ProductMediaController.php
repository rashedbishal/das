<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductMediaRequest;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\ProductMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductMediaController extends Controller
{
    public function __construct(private readonly ProductMediaService $media) {}

    public function store(StoreProductMediaRequest $request, Product $product): RedirectResponse
    {
        $type = $request->string('type')->toString();
        $limits = ProductMediaService::limits()[$type];

        if ($product->media()->where('type', $type)->count() >= $limits['max']) {
            return back()->withErrors([
                'file' => "This product already has the maximum of {$limits['max']} {$type}s.",
            ]);
        }

        $meta = $request->only(['is_main', 'alt_text', 'sort_order']);

        if ($request->string('source')->toString() === 'upload') {
            $this->media->addUpload($product, $request->file('file'), $request->string('type')->toString(), $meta);
        } else {
            $this->media->addUrl($product, $request->string('type')->toString(), (string) $request->string('url'), $meta);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Media added.']);

        return back();
    }

    public function update(Request $request, Product $product, ProductMedia $media): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_if($media->product_id !== $product->id, 404);

        $this->media->setMain($media);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Main image updated.']);

        return back();
    }

    public function destroy(Product $product, ProductMedia $media): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_if($media->product_id !== $product->id, 404);

        $this->media->delete($media);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Media removed.']);

        return back();
    }
}
