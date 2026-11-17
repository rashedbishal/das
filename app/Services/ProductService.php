<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Support\SlugGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        private readonly ActivityLogService $activity,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Up to $limit published products to show as "related" on a product page:
     * hand-picked related products first, then products sharing the most tags,
     * then the same subcategory, then the same category.
     *
     * @return EloquentCollection<int, Product>
     */
    public function relatedFor(Product $product, int $limit = 8): EloquentCollection
    {
        $related = $product->relatedProducts()->published()->with(['brand', 'media'])->limit($limit)->get();

        $fill = function (callable $scope) use ($product, &$related, $limit): void {
            if ($related->count() >= $limit) {
                return;
            }

            $query = Product::query()
                ->published()
                ->whereKeyNot([$product->id, ...$related->modelKeys()])
                ->with(['brand', 'media']);

            $scope($query);

            $related = $related->concat($query->limit($limit - $related->count())->get());
        };

        $tagIds = $product->tags()->pluck('tags.id')->all();

        if ($tagIds !== []) {
            $fill(fn ($query) => $query
                ->whereHas('tags', fn ($tags) => $tags->whereKey($tagIds))
                ->withCount(['tags as shared_tags_count' => fn ($tags) => $tags->whereKey($tagIds)])
                ->orderByDesc('shared_tags_count')
                ->orderByDesc('created_at'));
        }

        if ($product->subcategory_id !== null) {
            $fill(fn ($query) => $query->where('subcategory_id', $product->subcategory_id)->orderByDesc('created_at'));
        }

        $fill(fn ($query) => $query->where('category_id', $product->category_id)->orderByDesc('created_at'));

        return new EloquentCollection($related->values()->all());
    }

    /**
     * Prices are only shown to signed-in customers, so guests can't filter or sort
     * by price either (that would reveal prices indirectly).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function withoutPriceFiltersForGuests(array $filters, bool $signedIn): array
    {
        if ($signedIn) {
            return $filters;
        }

        unset($filters['min_price'], $filters['max_price']);

        if (in_array($filters['sort'] ?? null, ['price_asc', 'price_desc'], true)) {
            $filters['sort'] = 'newest';
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters, bool $includeAllStatuses = false): LengthAwarePaginator
    {
        // The admin list shows every relation; public product cards only need the
        // brand and images (see ProductCardResource).
        $query = $includeAllStatuses
            ? Product::query()->with(['brand', 'category', 'subcategory', 'tags', 'media'])
            : Product::query()->with(['brand', 'media' => fn ($media) => $media->where('type', 'image')]);

        if ($includeAllStatuses) {
            if (! empty($filters['trashed'])) {
                $query->onlyTrashed();
            }

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            $query->published();
        }

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderByRaw('CASE WHEN show_price THEN regular_price END ASC')->orderBy('id'),
            'price_desc' => $query->orderByRaw('CASE WHEN show_price THEN regular_price END DESC')->orderBy('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderBy('id'),
        };

        $perPage = (int) ($filters['per_page'] ?? $this->settings->get('catalog', 'page_size', 24));

        return $query->paginate(max(1, min($perPage, 100)))->withQueryString();
    }

    /**
     * Lowest and highest shown price among published products matching the
     * filters, ignoring the price filters themselves - the bounds for the
     * storefront's price slider. Both null when nothing has a shown price.
     *
     * @param  array<string, mixed>  $filters
     * @return array{min: float|null, max: float|null}
     */
    public function priceRange(array $filters): array
    {
        $query = Product::query()->published();
        $this->applyFilters($query, Arr::except($filters, ['min_price', 'max_price']));

        $range = $query->where('show_price', true)
            ->whereNotNull('regular_price')
            ->toBase()
            ->selectRaw('MIN(regular_price) as min_price, MAX(regular_price) as max_price')
            ->first();

        return [
            'min' => $range?->min_price !== null ? (float) $range->min_price : null,
            'max' => $range?->max_price !== null ? (float) $range->max_price : null,
        ];
    }

    /**
     * A filter value as a list of slugs: "a,b" or ['a', 'b'] (the storefront's
     * multi-select filters match any of them).
     *
     * @return list<string>
     */
    public static function slugList(mixed $value): array
    {
        $items = is_array($value) ? $value : explode(',', is_scalar($value) ? (string) $value : '');

        return array_values(array_unique(array_filter(
            array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $items),
            fn (string $item) => $item !== '',
        )));
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $pattern = '%'.$this->escapeLike(trim((string) $filters['search'])).'%';
            $query->where(function ($inner) use ($pattern) {
                $inner->whereRaw("name like ? escape '!'", [$pattern])
                    ->orWhereRaw("sku like ? escape '!'", [$pattern])
                    ->orWhereRaw("model_number like ? escape '!'", [$pattern]);
            });
        }

        if ($categories = self::slugList($filters['category'] ?? null)) {
            $query->whereHas('category', fn ($inner) => $inner->whereIn('slug', $categories));
        }

        if ($subcategories = self::slugList($filters['subcategory'] ?? null)) {
            $query->whereHas('subcategory', fn ($inner) => $inner->whereIn('slug', $subcategories));
        }

        if ($brands = self::slugList($filters['brand'] ?? null)) {
            $query->whereHas('brand', fn ($inner) => $inner->whereIn('brands.slug', $brands));
        }

        if (array_key_exists('min_price', $filters) && $filters['min_price'] !== null) {
            $query->where('show_price', true)->where('regular_price', '>=', $filters['min_price']);
        }

        if (array_key_exists('max_price', $filters) && $filters['max_price'] !== null) {
            $query->where('show_price', true)->where('regular_price', '<=', $filters['max_price']);
        }

        if ($stockStatuses = self::slugList($filters['stock_status'] ?? null)) {
            $query->where('show_stock', true)->whereIn('stock_status', $stockStatuses);
        }

        if (! empty($filters['model_number'])) {
            $query->whereRaw("model_number like ? escape '!'", ['%'.$this->escapeLike((string) $filters['model_number']).'%']);
        }

        if (! empty($filters['featured'])) {
            $query->featured();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Product
    {
        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('products', $data['slug'])
            : SlugGenerator::unique('products', $data['name']);

        return DB::transaction(function () use ($actor, $data) {
            $product = Product::query()->create([
                ...Arr::except($data, ['tag_ids', 'related_product_ids', 'specifications']),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'published_at' => ($data['status'] ?? null) === 'published' ? now() : null,
            ]);

            $this->syncRelations($product, $data);

            return $product->fresh(['brand', 'category', 'subcategory', 'tags', 'media', 'specifications']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Product $product, array $data): Product
    {
        if (! empty($data['slug'])) {
            $data['slug'] = SlugGenerator::unique('products', $data['slug'], $product->id);
        } else {
            unset($data['slug']);
        }

        return DB::transaction(function () use ($actor, $product, $data) {
            $becomingPublished = ($data['status'] ?? null) === 'published' && $product->status !== 'published';

            $fillData = [
                ...Arr::except($data, ['tag_ids', 'related_product_ids', 'specifications']),
                'updated_by' => $actor->id,
            ];

            if ($becomingPublished) {
                $fillData['published_at'] = now();
            }

            $product->fill($fillData);
            $product->save();

            $changed = $this->syncRelations($product, $data);

            if ($changed !== []) {
                $this->activity->log(
                    'product.relations_updated',
                    $product,
                    'Updated '.implode(', ', $changed)." of product {$product->name}",
                    ['changed' => $changed],
                );
            }

            return $product->fresh(['brand', 'category', 'subcategory', 'tags', 'media', 'specifications']);
        });
    }

    /**
     * Moves the product to the trash (soft delete); it can be restored later.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function restore(Product $product): void
    {
        $product->restore();
    }

    /**
     * Permanently deletes a trashed product, its media rows (via FK cascade) and its
     * uploaded files, which frees its SKU and slug for reuse.
     */
    public function forceDelete(Product $product): void
    {
        $productId = $product->id;
        $product->forceDelete();

        Storage::disk('public')->deleteDirectory("products/{$productId}");
    }

    /**
     * Makes "%" and "_" in a search term match literally ("!" is the escape character,
     * which MySQL and SQLite both accept in an ESCAPE clause).
     */
    private function escapeLike(string $term): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string> the relations that actually changed ("tags", "related products", "specifications")
     */
    private function syncRelations(Product $product, array $data): array
    {
        $changed = [];

        if (array_key_exists('tag_ids', $data)) {
            $result = $product->tags()->sync($data['tag_ids'] ?? []);

            if ($result['attached'] !== [] || $result['detached'] !== []) {
                $changed[] = 'tags';
            }
        }

        if (array_key_exists('related_product_ids', $data)) {
            $ids = array_values(array_diff($data['related_product_ids'] ?? [], [$product->id]));
            $result = $product->relatedProducts()->sync($ids);

            if ($result['attached'] !== [] || $result['detached'] !== []) {
                $changed[] = 'related products';
            }
        }

        if (array_key_exists('specifications', $data)) {
            $before = $product->specifications()->get(['name', 'value'])
                ->map(fn ($spec) => [$spec->name, $spec->value])->all();

            $product->specifications()->delete();

            foreach ($data['specifications'] ?? [] as $index => $spec) {
                $product->specifications()->create([
                    'name' => $spec['name'],
                    'value' => $spec['value'],
                    'sort_order' => $spec['sort_order'] ?? $index,
                ]);
            }

            $after = array_map(fn ($spec) => [$spec['name'], $spec['value']], array_values($data['specifications'] ?? []));

            if ($before !== $after) {
                $changed[] = 'specifications';
            }
        }

        return $changed;
    }
}
