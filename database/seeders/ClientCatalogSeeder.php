<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ClientCatalogSeeder extends Seeder
{
    private const SHOFU_FEATURES = [
        'Excellent Firing Stability',
        'Fine Powder',
        'Excellent Build-Up Stability',
        'Opalescent & Fluorescent Effect Powders',
        'VITA-Based Standard Shades',
    ];

    /**
     * @var list<array{
     *     name: string,
     *     brand: string,
     *     category: string,
     *     subcategory: string|null,
     *     image: string,
     *     material: string,
     *     origin: string,
     *     warranty: string,
     *     specifications?: list<array{name: string, value: string}>,
     *     short_description?: string,
     *     highlights?: list<string>,
     *     variants?: list<string>
     * }>
     */
    private const PRODUCTS = [
        [
            'name' => 'Durable Manual Large Crucibles for Dental Lab',
            'brand' => 'DAS',
            'category' => 'Instrument',
            'subcategory' => null,
            'image' => '/images/client-catalog/crucible-large.jpg',
            'material' => 'Ceramic',
            'origin' => 'China',
            'warranty' => 'Not applicable',
        ],
        [
            'name' => 'Durable Manual Standard Size Crucibles for Dental Lab',
            'brand' => 'DAS',
            'category' => 'Instrument',
            'subcategory' => null,
            'image' => '/images/client-catalog/crucible-standard.jpg',
            'material' => 'Ceramic',
            'origin' => 'China',
            'warranty' => 'Not applicable',
        ],
        [
            'name' => 'Empty Cartridges for Flexible Denture',
            'brand' => 'DAS',
            'category' => 'Material',
            'subcategory' => 'Denture',
            'image' => '/images/client-catalog/empty-cartridges.jpg',
            'material' => 'Aluminum',
            'origin' => 'China',
            'warranty' => 'Not applicable',
            'short_description' => 'Aluminum empty cartridges for dental flexible injection systems.',
            'specifications' => [
                ['name' => 'Diameter', 'value' => '25 mm'],
            ],
            'variants' => ['45mm', '65mm', '85mm', '100mm'],
        ],
        [
            'name' => 'Firing Tray (Honeycomb, with Pins)',
            'brand' => 'DAS',
            'category' => 'Instrument',
            'subcategory' => null,
            'image' => '/images/client-catalog/firing-tray.jpg',
            'material' => 'Ceramic',
            'origin' => 'China',
            'warranty' => 'Not applicable',
            'short_description' => 'Circular ceramic honeycomb firing tray with SS and ceramic pins for dental laboratory ceramic furnaces.',
            'variants' => ['SS Pins', 'Ceramic Pins'],
        ],
        [
            'name' => 'Dental Lab Hanging Motor Foredom SR',
            'brand' => 'DAS',
            'category' => 'Instrument',
            'subcategory' => null,
            'image' => '/images/client-catalog/hanging-motor.jpg',
            'material' => 'Motor Metal Body',
            'origin' => 'China',
            'warranty' => 'Service warranty',
            'variants' => ['Silver', 'Golden'],
        ],
        [
            'name' => 'Hanging Motor Handpiece with Wrench',
            'brand' => 'DAS',
            'category' => 'Instrument',
            'subcategory' => null,
            'image' => '/images/client-catalog/hanging-motor-handpiece.jpg',
            'material' => 'Metal',
            'origin' => 'China',
            'warranty' => 'Not applicable',
            'variants' => ['Silver', 'Golden'],
        ],
        [
            'name' => 'Dental Lab Polishing Brush & Wheel',
            'brand' => 'DAS',
            'category' => 'Instrument',
            'subcategory' => null,
            'image' => '/images/client-catalog/polishing-brush-wheel.jpg',
            'material' => 'Plastic Bristles',
            'origin' => 'China',
            'warranty' => 'Not applicable',
            'variants' => ['Brush', 'Wheel'],
        ],
        [
            'name' => 'Dental Plaster Saw 128mm, Rubber Handle',
            'brand' => 'DAS',
            'category' => 'Instrument',
            'subcategory' => null,
            'image' => '/images/client-catalog/plaster-saw-rubber-handle.jpg',
            'material' => 'Stainless Steel and Rubber',
            'origin' => 'China',
            'warranty' => 'Not applicable',
            'specifications' => [
                ['name' => 'Length', 'value' => '128 mm'],
                ['name' => 'Pack', 'value' => '1 unit'],
            ],
        ],
        [
            'name' => 'Dental Plaster Saw 128mm, Steel Handle',
            'brand' => 'DAS',
            'category' => 'Instrument',
            'subcategory' => null,
            'image' => '/images/client-catalog/plaster-saw-steel-handle.jpg',
            'material' => 'Stainless Steel',
            'origin' => 'China',
            'warranty' => 'Not applicable',
            'specifications' => [
                ['name' => 'Length', 'value' => '128 mm'],
                ['name' => 'Pack', 'value' => '1 unit'],
            ],
        ],
        [
            'name' => 'SHOFU Vintage Pro Art Cream 3g',
            'brand' => 'SHOFU',
            'category' => 'Material',
            'subcategory' => 'Porceline',
            'image' => '/images/client-catalog/shofu-vintage-pro-art.jpg',
            'material' => 'Vintage Art',
            'origin' => 'Japan',
            'warranty' => 'Not applicable',
            'specifications' => [
                ['name' => 'Weight', 'value' => '3 g'],
            ],
            'highlights' => [
                'Exceptional Color depth',
                'Fine Powder',
                'Highly Fluorescent Stain',
                'Thin layer application',
            ],
            'variants' => [
                'Pink',
                'Yellow',
                'Blue',
                'Orange',
                'V',
                'Gr (GR)',
                'B',
                'White',
                'DR-Br (DRBR)',
                'Br (BR)',
                'O-Br (OBR)',
                'B-Br (BBR)',
                'K',
                'G',
                'Bl-G (BLG)',
                'CY',
                'RP',
                'AS',
                'BS',
                'CS',
                'DS',
            ],
        ],
        [
            'name' => 'SHOFU Vintage Pro Base Opaque Powder 50g',
            'brand' => 'SHOFU',
            'category' => 'Material',
            'subcategory' => 'Porceline',
            'image' => '/images/client-catalog/shofu-vintage-pro-base-opaque-powder.jpg',
            'material' => 'Base Opaque Powder',
            'origin' => 'Japan',
            'warranty' => 'Not applicable',
            'specifications' => [
                ['name' => 'Weight', 'value' => '50 g'],
            ],
            'highlights' => self::SHOFU_FEATURES,
        ],
        [
            'name' => 'SHOFU Vintage Pro Base Opaque Paste 6g',
            'brand' => 'SHOFU',
            'category' => 'Material',
            'subcategory' => 'Porceline',
            'image' => '/images/client-catalog/shofu-vintage-pro-base-opaque-paste.jpg',
            'material' => 'Opaque paste',
            'origin' => 'Japan',
            'warranty' => 'Not applicable',
            'specifications' => [
                ['name' => 'Weight', 'value' => '6 g'],
            ],
            'highlights' => [
                'Excellent Firing Stability',
                'Excellent Build-Up Stability',
                'Opalescent & Fluorescent Effect Powders',
                'VITA-Based Standard Shades',
            ],
        ],
        [
            'name' => 'SHOFU Vintage Pro Body 200g',
            'brand' => 'SHOFU',
            'category' => 'Material',
            'subcategory' => 'Porceline',
            'image' => '/images/client-catalog/shofu-vintage-pro-body-200g.jpg',
            'material' => 'Porcelain',
            'origin' => 'Japan',
            'warranty' => 'Not applicable',
            'specifications' => [
                ['name' => 'Weight', 'value' => '200 g'],
            ],
            'highlights' => self::SHOFU_FEATURES,
            'variants' => [
                'A1', 'A2', 'A3', 'A3.5', 'A4',
                'B1', 'B2', 'B3', 'B4',
                'C1', 'C2', 'C3', 'C4',
                'D2', 'D3', 'D4',
            ],
        ],
        [
            'name' => 'SHOFU Vintage Pro Body 50g',
            'brand' => 'SHOFU',
            'category' => 'Material',
            'subcategory' => 'Porceline',
            'image' => '/images/client-catalog/shofu-vintage-pro-body-50g.jpg',
            'material' => 'Porcelain',
            'origin' => 'Japan',
            'warranty' => 'Not applicable',
            'specifications' => [
                ['name' => 'Weight', 'value' => '50 g'],
            ],
            'highlights' => self::SHOFU_FEATURES,
            'variants' => [
                'A1', 'A2', 'A3', 'A3.5', 'A4',
                'B1', 'B2', 'B3', 'B4',
                'C1', 'C2', 'C3', 'C4',
                'D2', 'D3', 'D4',
            ],
        ],
        [
            'name' => 'SHOFU Vintage Pro Opaque 50g',
            'brand' => 'SHOFU',
            'category' => 'Material',
            'subcategory' => 'Porceline',
            'image' => '/images/client-catalog/shofu-vintage-pro-opaque-50g.jpg',
            'material' => 'Opaque',
            'origin' => 'Japan',
            'warranty' => 'Not applicable',
            'specifications' => [
                ['name' => 'Weight', 'value' => '50 g'],
            ],
            'highlights' => self::SHOFU_FEATURES,
            'variants' => [
                'A1', 'A2', 'A3', 'A3.5', 'A4',
                'B1', 'B2', 'B3', 'B4',
                'C1', 'C2', 'C3', 'C4',
                'D2', 'D3', 'D4',
            ],
        ],
    ];

    public function run(): void
    {
        $this->assertImageAssetsExist();

        /** @var array<string, array{disk: string, path: string}> $oldMediaFiles */
        $oldMediaFiles = [];
        /** @var array<string, true> $oldCatalogImages */
        $oldCatalogImages = [];

        DB::transaction(function () use (&$oldMediaFiles, &$oldCatalogImages): void {
            $products = Product::withTrashed()->with('media')->get();
            foreach ($products as $product) {
                foreach ($product->media as $media) {
                    if ($media->source === 'upload' && $media->disk && $media->path) {
                        $key = $media->disk.'|'.$media->path;
                        $oldMediaFiles[$key] = [
                            'disk' => $media->disk,
                            'path' => $media->path,
                        ];
                    }
                }

                $product->forceDelete();
            }

            foreach (Subcategory::withTrashed()->get() as $subcategory) {
                $path = $this->storedCatalogImagePath($subcategory->image);
                if ($path !== null) {
                    $oldCatalogImages[$path] = true;
                }

                $subcategory->forceDelete();
            }

            foreach (Category::withTrashed()->get() as $category) {
                $path = $this->storedCatalogImagePath($category->image);
                if ($path !== null) {
                    $oldCatalogImages[$path] = true;
                }

                $category->forceDelete();
            }

            foreach (Brand::withTrashed()->get() as $brand) {
                $path = $this->storedCatalogImagePath($brand->logo);
                if ($path !== null) {
                    $oldCatalogImages[$path] = true;
                }

                $brand->forceDelete();
            }

            Tag::query()->delete();

            $brands = collect(['DAS', 'SHOFU'])
                ->mapWithKeys(fn (string $name) => [
                    $name => Brand::query()->create([
                        'name' => $name,
                        'slug' => Str::slug($name),
                        'is_active' => true,
                    ]),
                ]);

            $categories = collect([
                ['name' => 'Instrument', 'sort_order' => 0],
                ['name' => 'Material', 'sort_order' => 1],
            ])->mapWithKeys(fn (array $definition) => [
                $definition['name'] => Category::query()->create([
                    ...$definition,
                    'slug' => Str::slug($definition['name']),
                    'is_active' => true,
                ]),
            ]);

            $subcategories = collect([
                ['name' => 'Porceline', 'sort_order' => 0],
                ['name' => 'Denture', 'sort_order' => 1],
                ['name' => 'cad-cam-material', 'sort_order' => 2],
            ])->mapWithKeys(fn (array $definition) => [
                $definition['name'] => Subcategory::query()->create([
                    ...$definition,
                    'category_id' => $categories['Material']->id,
                    'slug' => Str::slug($definition['name']),
                    'is_active' => true,
                ]),
            ]);

            $variantSku = 1;
            foreach (self::PRODUCTS as $index => $definition) {
                $product = Product::query()->create([
                    'name' => $definition['name'],
                    'slug' => Str::slug($definition['name']),
                    'sku' => sprintf('DAS-P-%04d', $index + 1),
                    'brand_id' => $brands[$definition['brand']]->id,
                    'category_id' => $categories[$definition['category']]->id,
                    'subcategory_id' => $definition['subcategory'] === null
                        ? null
                        : $subcategories[$definition['subcategory']]->id,
                    'short_description' => $definition['short_description'] ?? null,
                    'full_description' => null,
                    'highlights' => $definition['highlights'] ?? [],
                    'status' => 'published',
                    'is_featured' => false,
                    'regular_price' => null,
                    'sale_price' => null,
                    'currency' => 'BDT',
                    'stock_quantity' => 0,
                    'stock_status' => 'on_order',
                    'published_at' => now(),
                ]);

                $specifications = [
                    ['name' => 'Material', 'value' => $definition['material']],
                    ['name' => 'Warranty', 'value' => $definition['warranty']],
                    ['name' => 'After-sale Service', 'value' => 'Online technical support'],
                    ['name' => 'Place of Origin', 'value' => $definition['origin']],
                    ...($definition['specifications'] ?? []),
                ];
                foreach ($specifications as $sortOrder => $specification) {
                    $product->specifications()->create([
                        ...$specification,
                        'sort_order' => $sortOrder,
                    ]);
                }

                $product->media()->create([
                    'type' => 'image',
                    'source' => 'url',
                    'url' => $definition['image'],
                    'is_main' => true,
                    'alt_text' => $definition['name'],
                    'sort_order' => 0,
                ]);

                foreach ($definition['variants'] ?? [] as $sortOrder => $name) {
                    $product->variants()->create([
                        'name' => $name,
                        'sku' => sprintf('DAS-%04d', $variantSku++),
                        'price' => null,
                        'stock_quantity' => 0,
                        'stock_status' => 'on_order',
                        'sort_order' => $sortOrder,
                        'is_active' => true,
                    ]);
                }
            }

            Setting::query()->updateOrCreate(
                ['group' => 'catalog', 'key' => 'delivery_note'],
                ['value' => 'Through courier all over Bangladesh'],
            );
        });

        Cache::forget('settings.all');
        $this->deleteOldFiles($oldMediaFiles, array_keys($oldCatalogImages));
    }

    private function assertImageAssetsExist(): void
    {
        foreach (self::PRODUCTS as $product) {
            $path = public_path(ltrim($product['image'], '/'));
            if (! is_file($path)) {
                throw new RuntimeException("Client catalog image is missing: {$path}");
            }
        }
    }

    private function storedCatalogImagePath(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! Str::startsWith($path, '/storage/')) {
            return null;
        }

        $path = substr($path, strlen('/storage/'));
        if (array_intersect(['.', '..'], explode('/', $path)) !== []) {
            return null;
        }

        return Str::startsWith($path, ['categories/', 'subcategories/', 'brands/'])
            ? $path
            : null;
    }

    /**
     * @param  array<string, array{disk: string, path: string}>  $mediaFiles
     * @param  list<string>  $catalogImages
     */
    private function deleteOldFiles(array $mediaFiles, array $catalogImages): void
    {
        foreach ($mediaFiles as $file) {
            $disk = Storage::disk($file['disk']);
            if ($disk->exists($file['path']) && ! $disk->delete($file['path'])) {
                throw new RuntimeException("Unable to remove old product media: {$file['path']}");
            }
        }

        $disk = Storage::disk('public');
        foreach ($catalogImages as $path) {
            if ($disk->exists($path) && ! $disk->delete($path)) {
                throw new RuntimeException("Unable to remove old catalog image: {$path}");
            }
        }
    }
}
