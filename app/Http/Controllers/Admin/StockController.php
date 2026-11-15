<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Stock\AdjustStockRequest;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin -> Stock Management: every (non-trashed) product, newest first, with
 * add/remove stock actions and a per-product history.
 */
class StockController extends Controller
{
    private const PER_PAGE = 25;

    private const HISTORY_LIMIT = 50;

    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $search = trim($request->string('search')->toString());

        $products = Product::query()
            ->with(['media' => fn ($media) => $media->where('type', 'image')])
            ->when($search !== '', function ($query) use ($search) {
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';

                $query->where(fn ($inner) => $inner
                    ->whereRaw("name like ? escape '!'", [$pattern])
                    ->orWhereRaw("sku like ? escape '!'", [$pattern])
                    ->orWhereRaw("model_number like ? escape '!'", [$pattern]));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, ['id', 'name', 'sku', 'status', 'stock_quantity', 'stock_status', 'created_at'])
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'status' => $product->status,
                'stock_quantity' => $product->stock_quantity,
                'stock_status' => $product->stock_status,
                'image_url' => $this->mainImage($product)?->resolvedUrl(),
            ]);

        return Inertia::render('admin/stock/Index', [
            'products' => $products,
            'filters' => ['search' => $search],
            'canAdjust' => $request->user()?->hasPermission('products.update') ?? false,
        ]);
    }

    public function store(AdjustStockRequest $request, Product $product): RedirectResponse
    {
        /** @var array{type: 'add'|'remove', quantity: int|string, note?: string|null} $data */
        $data = $request->validated();

        $this->stock->adjust($request->user(), $product, $data['type'], (int) $data['quantity'], $data['note'] ?? null);

        $verb = $data['type'] === 'add' ? 'added to' : 'removed from';
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$data['quantity']} {$verb} {$product->name}. Stock is now {$product->stock_quantity}.",
        ]);

        return back();
    }

    /**
     * The latest stock changes for one product, loaded by the history dialog.
     */
    public function history(Product $product): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $movements = $product->stockMovements()
            ->with('user:id,name')
            ->latest()
            ->orderByDesc('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
                'note' => $movement->note,
                'user' => $movement->user?->name,
                'created_at' => $movement->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $movements]);
    }

    private function mainImage(Product $product): ?ProductMedia
    {
        return $product->media->firstWhere('is_main', true) ?? $product->media->first();
    }
}
