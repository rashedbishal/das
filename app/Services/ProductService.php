<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Support\SlugGenerator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private readonly ActivityLogService $activity,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters, bool $includeAllStatuses = false): LengthAwarePaginator
    {
        $query = Product::query()->with(['brand', 'category', 'subcategory', 'tags', 'media']);

        if ($includeAllStatuses) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            $query->published();
        }

        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('model_number', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['category'])) {
            $category = $filters['category'];
            $query->whereHas('category', fn ($inner) => $inner->where('slug', $category));
        }

        if (! empty($filters['subcategory'])) {
            $subcategory = $filters['subcategory'];
            $query->whereHas('subcategory', fn ($inner) => $inner->where('slug', $subcategory));
        }

        if (! empty($filters['brand'])) {
            $brand = $filters['brand'];
            $query->whereHas('brand', fn ($inner) => $inner->where('brands.slug', $brand));
        }

        if (array_key_exists('min_price', $filters) && $filters['min_price'] !== null) {
            $query->where('regular_price', '>=', $filters['min_price']);
        }

        if (array_key_exists('max_price', $filters) && $filters['max_price'] !== null) {
            $query->where('regular_price', '<=', $filters['max_price']);
        }

        if (! empty($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        if (! empty($filters['model_number'])) {
            $query->where('model_number', 'like', '%'.$filters['model_number'].'%');
        }

        if (! empty($filters['featured'])) {
            $query->featured();
        }

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('regular_price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('regular_price')->orderBy('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderBy('id'),
        };

        $perPage = (int) ($filters['per_page'] ?? $this->settings->get('catalog', 'page_size', 24));

        return $query->paginate(max(1, min($perPage, 100)))->withQueryString();
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

            $this->activity->log('product.created', $product, "Created product {$product->name}");

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

            $this->syncRelations($product, $data);

            $this->activity->log('product.updated', $product, "Updated product {$product->name}");

            return $product->fresh(['brand', 'category', 'subcategory', 'tags', 'media', 'specifications']);
        });
    }

    public function delete(Product $product): void
    {
        $this->activity->log('product.deleted', $product, "Deleted product {$product->name}");

        $product->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncRelations(Product $product, array $data): void
    {
        if (array_key_exists('tag_ids', $data)) {
            $product->tags()->sync($data['tag_ids'] ?? []);
        }

        if (array_key_exists('related_product_ids', $data)) {
            $ids = array_values(array_diff($data['related_product_ids'] ?? [], [$product->id]));
            $product->relatedProducts()->sync($ids);
        }

        if (array_key_exists('specifications', $data)) {
            $product->specifications()->delete();

            foreach ($data['specifications'] ?? [] as $index => $spec) {
                $product->specifications()->create([
                    'name' => $spec['name'],
                    'value' => $spec['value'],
                    'sort_order' => $spec['sort_order'] ?? $index,
                ]);
            }
        }
    }
}
