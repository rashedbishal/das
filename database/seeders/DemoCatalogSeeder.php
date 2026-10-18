<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\User;
use App\Support\SlugGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fills the catalog with a realistic, dense demo dataset (categories, subcategories,
 * brands, tags, ~170 products with images, specs and related products, customers) so
 * the storefront and admin can be judged "full". Additive and re-runnable: existing
 * rows are kept and anything already seeded is skipped.
 *
 *   php artisan db:seed --class=DemoCatalogSeeder
 *
 * Demo products have SKUs starting with "DEMO-" and images under storage "demo/".
 */
class DemoCatalogSeeder extends Seeder
{
    private const IMAGE_SOURCE = 'resources/js/assets/storefront';

    private const PRODUCTS_PER_SUBCATEGORY = [5, 9];

    /**
     * Category => [image, [subcategory => [image, [noun variants], [min price, max price], [spec name => values]]]].
     * Images are the single-product photos in resources/js/assets/storefront.
     *
     * @var array<string, array{0: string, 1: string, 2: array<string, array{0: string, 1: list<string>, 2: array{0: int, 1: int}, 3: array<string, list<string>>}>}>
     */
    private const CATALOG = [
        'Dental Chairs & Units' => ['equipment-a0', 'Complete treatment centres, patient chairs and operatory seating built for long clinical days.', [
            'Treatment Units' => ['equipment-a0', ['Dental Treatment Unit', 'Integrated Dental Unit', 'Treatment Centre'], [350000, 1250000], [
                'Chair Movement' => ['Electro-mechanical', 'Hydraulic', 'Electric, 4 memory positions'],
                'Light' => ['LED, 8 bulbs, 35,000 lux', 'LED, 6 bulbs, 30,000 lux', 'Shadowless LED, 50,000 lux'],
                'Instrument Tray' => ['Over-the-patient', 'Cart-mounted', 'Side delivery'],
                'Upholstery' => ['Seamless PU leather', 'Memory foam, PU leather', 'Microfibre'],
            ]],
            'Patient Chairs' => ['equipment-b0', ['Patient Chair', 'Ergonomic Patient Chair', 'Electric Patient Chair'], [120000, 420000], [
                'Max. Patient Weight' => ['180 kg', '200 kg', '220 kg'],
                'Seat Height' => ['400–800 mm', '380–780 mm', '420–820 mm'],
                'Backrest' => ['Double-articulating', 'Single-articulating'],
            ]],
            'Operator Stools' => ['equipment-c0', ['Saddle Stool', 'Operator Stool', 'Assistant Stool'], [9500, 38000], [
                'Seat Height' => ['52–68 cm', '48–64 cm', '55–75 cm'],
                'Base' => ['Polished aluminium, 5 castors', 'Chrome, 5 castors'],
                'Seat' => ['Saddle, PU leather', 'Round, memory foam'],
            ]],
            'Operating Lights' => ['equipment-c7', ['LED Operating Light', 'Ceiling Operatory Light', 'Shadowless Dental Light'], [45000, 210000], [
                'Illuminance' => ['35,000 lux', '45,000 lux', '50,000 lux'],
                'Colour Temperature' => ['5,000 K', '5,500 K', '4,000–6,000 K adjustable'],
                'Control' => ['Touch-free sensor', 'Handle switch', 'Sensor and handle'],
            ]],
        ]],
        'Imaging & Radiology' => ['equipment-a1', 'Digital radiography, sensors and intraoral cameras for faster, clearer diagnosis.', [
            'Intraoral X-Ray' => ['equipment-a1', ['Wall-Mounted X-Ray Unit', 'Intraoral X-Ray System', 'DC X-Ray Unit'], [180000, 520000], [
                'Tube Voltage' => ['60 kV', '65 kV', '70 kV'],
                'Focal Spot' => ['0.4 mm', '0.5 mm', '0.7 mm'],
                'Arm Reach' => ['165 cm', '180 cm', '200 cm'],
            ]],
            'Digital Sensors' => ['equipment-b1', ['Digital X-Ray Sensor', 'RVG Sensor', 'Intraoral Sensor'], [95000, 260000], [
                'Sensor Size' => ['Size 1', 'Size 1.5', 'Size 2'],
                'Resolution' => ['20 lp/mm', '25 lp/mm', '27 lp/mm'],
                'Connection' => ['USB 2.0', 'USB 3.0', 'Wi-Fi'],
            ]],
            'Plate Scanners' => ['equipment-c1', ['Phosphor Plate Scanner', 'PSP Scanner', 'Imaging Plate Scanner'], [320000, 780000], [
                'Plate Sizes' => ['0, 1, 2, 3', '0–4', '1, 2'],
                'Scan Time' => ['6 s', '8 s', '10 s'],
            ]],
            'Intraoral Cameras' => ['equipment-b7', ['Intraoral Camera', 'Wireless Intraoral Camera', 'HD Intraoral Camera'], [18000, 85000], [
                'Resolution' => ['1280 × 720', '1920 × 1080', '2560 × 1440'],
                'Focus' => ['Auto focus', '5–35 mm fixed', 'Liquid lens auto focus'],
                'Connection' => ['USB', 'Wi-Fi', 'USB and Wi-Fi'],
            ]],
        ]],
        'Sterilization & Infection Control' => ['equipment-a2', 'Autoclaves, cleaners and sealers that keep every instrument cycle safe and traceable.', [
            'Autoclaves' => ['equipment-a2', ['Class B Autoclave', 'Vacuum Autoclave', 'Benchtop Steriliser'], [140000, 480000], [
                'Chamber Volume' => ['18 L', '23 L', '29 L'],
                'Class' => ['Class B', 'Class S', 'Class N'],
                'Cycle Time' => ['30 min', '38 min', '45 min'],
            ]],
            'Ultrasonic Cleaners' => ['equipment-b2', ['Ultrasonic Cleaner', 'Digital Ultrasonic Bath', 'Heated Ultrasonic Cleaner'], [14000, 65000], [
                'Tank Capacity' => ['2.5 L', '3.2 L', '6 L'],
                'Frequency' => ['40 kHz', '42 kHz'],
                'Heating' => ['20–80 °C', 'None', '20–60 °C'],
            ]],
            'Pouch Sealers' => ['equipment-c2', ['Pouch Sealing Machine', 'Rotary Sealer', 'Heat Sealer'], [22000, 95000], [
                'Seal Width' => ['10 mm', '12 mm', '14 mm'],
                'Max. Roll Width' => ['250 mm', '300 mm', '350 mm'],
            ]],
        ]],
        'Handpieces & Motors' => ['equipment-a3', 'High-speed handpieces, contra-angles and motors with dependable torque and long bearing life.', [
            'High-Speed Handpieces' => ['equipment-a3', ['High-Speed Handpiece', 'LED Turbine', 'Push-Button Turbine'], [8500, 48000], [
                'Speed' => ['350,000 rpm', '380,000 rpm', '420,000 rpm'],
                'Spray' => ['Single water spray', 'Triple water spray', 'Quattro water spray'],
                'Coupling' => ['2-hole Borden', '4-hole Midwest', 'Quick coupling'],
            ]],
            'Contra-Angles' => ['equipment-b3', ['Contra-Angle Handpiece', '1:5 Speed Increasing Contra-Angle', '1:1 Contra-Angle'], [12000, 72000], [
                'Ratio' => ['1:1', '1:5', '16:1'],
                'Light' => ['Fibre optic', 'LED', 'None'],
            ]],
            'Micromotors' => ['equipment-a4', ['Lab Micromotor', 'Brushless Micromotor', 'Electric Micromotor'], [16000, 78000], [
                'Max. Speed' => ['35,000 rpm', '45,000 rpm', '50,000 rpm'],
                'Torque' => ['3.5 Ncm', '5 Ncm', '8 Ncm'],
            ]],
        ]],
        'Endodontics' => ['equipment-a6', 'Apex locators, endo motors and obturation systems for predictable root canal treatment.', [
            'Apex Locators' => ['equipment-a6', ['Apex Locator', 'Colour Display Apex Locator', 'Multi-Frequency Apex Locator'], [18000, 68000], [
                'Display' => ['3.5" colour LCD', '4.3" touch screen', 'LED'],
                'Battery' => ['Rechargeable Li-ion', 'AA batteries'],
            ]],
            'Endo Motors' => ['equipment-b6', ['Cordless Endo Motor', 'Endo Motor with Apex Locator', 'Reciprocating Endo Motor'], [28000, 125000], [
                'Speed Range' => ['100–2,500 rpm', '150–1,000 rpm', '50–3,000 rpm'],
                'Torque Range' => ['0.4–4.0 Ncm', '0.6–5.0 Ncm'],
                'Modes' => ['Rotary and reciprocating', 'Rotary', 'Rotary, reciprocating, auto-reverse'],
            ]],
            'Obturation' => ['equipment-c6', ['Cordless Obturation Pen', 'Gutta-Percha Heating Pen', 'Warm Vertical Obturation System'], [22000, 88000], [
                'Temperature' => ['100–250 °C', '150–230 °C'],
                'Heat-up Time' => ['0.5 s', '1 s'],
            ]],
        ]],
        'Restorative' => ['equipment-a7', 'Composites, bonding and curing for aesthetic, durable restorations.', [
            'Composites' => ['equipment-a5', ['Nano-Hybrid Composite Kit', 'Universal Composite', 'Flowable Composite'], [1200, 9800], [
                'Shades' => ['A1, A2, A3, A3.5', 'A1–D3 (16 shades)', 'A2, A3, B1'],
                'Pack' => ['4 × 4 g syringes', '1 × 4 g syringe', '20 × 0.25 g capsules'],
                'Filler Load' => ['78% by weight', '82% by weight', '68% by weight'],
            ]],
            'Curing Lights' => ['equipment-a7', ['LED Curing Light', 'Cordless Curing Light', 'Wide-Spectrum Curing Light'], [6500, 42000], [
                'Intensity' => ['1,200 mW/cm²', '1,500 mW/cm²', '2,300 mW/cm²'],
                'Wavelength' => ['420–480 nm', '385–515 nm'],
                'Modes' => ['Full, ramp, pulse', 'Standard, high power', 'Full, ramp'],
            ]],
        ]],
        'Periodontics & Hygiene' => ['equipment-c3', 'Scalers and precision hand instruments for thorough, comfortable hygiene care.', [
            'Ultrasonic Scalers' => ['equipment-c3', ['Ultrasonic Scaler', 'Piezo Scaler with LED', 'Scaler with Water Bottle'], [9500, 62000], [
                'Frequency' => ['28 kHz', '30 kHz', '32 kHz'],
                'Tips Included' => ['5 tips', '6 tips', '8 tips'],
                'Water Supply' => ['Mains', 'Bottle', 'Mains and bottle'],
            ]],
            'Hand Instruments' => ['equipment-b5', ['Diagnostic Instrument Kit', 'Periodontal Instrument Set', 'Scaler and Curette Kit'], [1800, 14000], [
                'Pieces' => ['6 pieces', '8 pieces', '12 pieces'],
                'Material' => ['Surgical stainless steel', 'Hardened stainless steel'],
                'Handle' => ['Knurled, 8 mm', 'Silicone grip', 'Hollow, 10 mm'],
            ]],
        ]],
        'Impression & Lab' => ['equipment-b4', 'Impression materials, mixers and furnaces for the clinic and the dental lab.', [
            'Impression Materials' => ['equipment-c5', ['VPS Impression Material', 'Light Body Impression Material', 'Putty Impression Kit'], [1600, 8500], [
                'Viscosity' => ['Light body', 'Medium body', 'Putty'],
                'Working Time' => ['1:30 min', '2:00 min', '2:30 min'],
                'Pack' => ['2 × 50 ml cartridges', '2 × 300 ml', '4 × 50 ml + tips'],
            ]],
            'Mixers' => ['equipment-c4', ['Alginate Mixer', 'Vacuum Mixer', 'Automatic Mixing Unit'], [24000, 98000], [
                'Bowl Capacity' => ['500 ml', '750 ml', '1,000 ml'],
                'Mixing Time' => ['Adjustable 5–60 s', 'Preset 15 s'],
            ]],
            'Furnaces' => ['equipment-b4', ['Sintering Furnace', 'Porcelain Furnace', 'Press Furnace'], [420000, 1450000], [
                'Max. Temperature' => ['1,200 °C', '1,530 °C', '1,650 °C'],
                'Programs' => ['100', '200', '500'],
                'Vacuum' => ['Integrated pump', 'External pump', 'None'],
            ]],
        ]],
    ];

    /** @var list<string> */
    private const BRANDS = [
        'Aurelia Dental', 'Clarion Medical', 'Vireo Instruments', 'Orbis Dental', 'Lumina Clinical',
        'Kestrel Surgical', 'Halden Dental Works', 'Novatek Dental', 'Serene Care Systems', 'Tessera Labs',
        'Polaris Dental', 'Arcadia Medical', 'Veridian Dental', 'Brightline Dental', 'Meridian Surgical',
        'Solstice Dental', 'Corvid Medical', 'Pinnacle Dentech',
    ];

    /** @var list<string> */
    private const SERIES = [
        'Aero', 'Nova', 'Vista', 'Prime', 'Orion', 'Lyra', 'Zen', 'Flux', 'Apex', 'Terra', 'Nimbus', 'Pulse',
        'Astra', 'Sola', 'Vega', 'Atlas', 'Kairo', 'Elan',
    ];

    /** @var list<string> */
    private const TAGS = [
        'Best Seller', 'New Arrival', 'Clinic Essential', 'Cordless', 'LED', 'Digital', 'Autoclavable',
        'Portable', '2-Year Warranty', 'CE Certified', 'Bulk Pack', 'Energy Efficient', 'Low Noise', 'Wireless',
        'Starter Kit', 'Lab Use',
    ];

    /** @var array<string, string> Generic specs every product gets a few of. */
    private const COMMON_SPECS = [
        'Warranty' => '1 year|2 years|3 years|18 months',
        'Country of Origin' => 'Germany|Italy|Japan|South Korea|Taiwan|China|Switzerland|USA',
        'Certification' => 'CE, ISO 13485|CE, FDA 510(k)|CE|ISO 13485',
        'Power Supply' => '220–240 V, 50 Hz|100–240 V, 50/60 Hz|Rechargeable battery',
    ];

    public function run(): void
    {
        fake()->seed(20261018);

        $tags = collect(self::TAGS)->map(fn (string $name) => Tag::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name],
        ));

        $brands = collect(self::BRANDS)->map(fn (string $name) => Brand::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => $name,
                'description' => "{$name} designs professional equipment and consumables for modern dental practices.",
                'website_url' => 'https://example.com/'.Str::slug($name),
                'is_active' => true,
            ],
        ));

        $sortOrder = (int) Category::query()->max('sort_order') + 1;
        $created = 0;

        foreach (self::CATALOG as $categoryName => [$categoryImage, $categoryDescription, $subcategories]) {
            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'description' => $categoryDescription, 'is_active' => true, 'sort_order' => $sortOrder++],
            );
            $category->image ??= $this->publicImage($categoryImage, "categories/{$category->slug}");
            $category->save();

            $subSort = 0;
            foreach ($subcategories as $subName => [$subImage, $nouns, $priceRange, $specs]) {
                $subcategory = Subcategory::query()->firstOrCreate(
                    ['slug' => Str::slug($subName)],
                    [
                        'category_id' => $category->id,
                        'name' => $subName,
                        'description' => "Browse {$subName} for clinics and labs.",
                        'is_active' => true,
                        'sort_order' => $subSort++,
                    ],
                );
                $subcategory->image ??= $this->publicImage($subImage, "subcategories/{$subcategory->slug}");
                $subcategory->save();

                $siblingImages = array_values(array_unique(array_map(fn (array $s) => $s[0], $subcategories)));

                for ($i = 0, $n = fake()->numberBetween(...self::PRODUCTS_PER_SUBCATEGORY); $i < $n; $i++) {
                    $product = $this->product($category, $subcategory, $brands->random(), $nouns, $priceRange, $specs, $subImage, $siblingImages);

                    if ($product) {
                        $product->tags()->syncWithoutDetaching($tags->random(fake()->numberBetween(1, 3))->pluck('id'));
                        $created++;
                    }
                }
            }
        }

        $this->relateProducts();
        $this->seedCustomers();

        $this->command->info("Demo catalog seeded: {$created} new products.");
    }

    /**
     * @param  list<string>  $nouns
     * @param  array{0: int, 1: int}  $priceRange
     * @param  array<string, list<string>>  $specs
     * @param  list<string>  $siblingImages
     */
    private function product(Category $category, Subcategory $subcategory, Brand $brand, array $nouns, array $priceRange, array $specs, string $image, array $siblingImages): ?Product
    {
        $series = fake()->randomElement(self::SERIES);
        $model = fake()->randomElement(['', ' Pro', ' Plus', ' S', ' X', ' Lite', ' Max']);
        $number = fake()->randomElement([100, 200, 300, 350, 500, 700, 900, 1200]);
        $noun = fake()->randomElement($nouns);
        $name = "{$series} {$number}{$model} {$noun}";
        $sku = 'DEMO-'.strtoupper(Str::substr(Str::slug($subcategory->name, ''), 0, 4)).'-'.fake()->unique()->numerify('#####');

        if (Product::withTrashed()->where('name', $name)->where('subcategory_id', $subcategory->id)->exists()) {
            return null;
        }

        $regular = round(fake()->numberBetween(...$priceRange), -2);
        $onSale = fake()->boolean(30);
        $status = fake()->randomElement([...array_fill(0, 18, 'published'), 'draft', 'archived']);
        $stockStatus = fake()->randomElement([...array_fill(0, 15, 'in_stock'), 'backorder', 'backorder', 'backorder', 'out_of_stock', 'out_of_stock']);
        $createdAt = Carbon::now()->subDays(fake()->numberBetween(0, 175))->subMinutes(fake()->numberBetween(0, 1440));

        $product = Product::query()->create([
            'name' => $name,
            'slug' => SlugGenerator::unique('products', $name),
            'sku' => $sku,
            'model_number' => strtoupper(Str::substr($series, 0, 2)).'-'.$number.trim(strtoupper($model)),
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'short_description' => fake()->randomElement([
                "A dependable {$noun} built for busy clinics.",
                "Precision-engineered {$noun} with a compact, easy-to-clean design.",
                "Clinical-grade {$noun} that balances performance and value.",
                "Our most popular {$noun}, trusted by practices across the country.",
            ]),
            'full_description' => implode("\n\n", [
                "The {$name} from {$brand->name} is designed around everyday clinical workflow: quick to set up, intuitive to use and straightforward to maintain.",
                fake()->randomElement([
                    'Every component is tested for durability and consistent performance, so your team can focus on patients rather than equipment.',
                    'Thoughtful ergonomics reduce fatigue over long sessions, while smooth surfaces make infection control simple.',
                    'It pairs reliable core performance with sensible features that matter in practice, not on a spec sheet.',
                ]),
                'Talk to our team for installation, training and after-sales support tailored to your practice.',
            ]),
            'highlights' => fake()->randomElements([
                'Easy to clean, infection-control friendly surfaces',
                'Quiet, low-vibration operation',
                'Compact footprint for smaller operatories',
                'Intuitive controls with minimal training',
                'Backed by local service and spare parts',
                'Energy-efficient design',
                'Ergonomic for long clinical sessions',
                'Compatible with standard accessories',
            ], fake()->numberBetween(3, 5)),
            'status' => $status,
            'is_featured' => $status === 'published' && fake()->boolean(12),
            'regular_price' => $regular,
            'sale_price' => $onSale ? round($regular * fake()->randomFloat(2, 0.75, 0.92), -2) : null,
            'currency' => 'BDT',
            'stock_quantity' => $stockStatus === 'out_of_stock' ? 0 : fake()->numberBetween(1, 120),
            'stock_status' => $stockStatus,
            'seo_title' => "{$name} | {$brand->name}",
            'seo_description' => "Buy the {$name} by {$brand->name}. Professional dental equipment with expert support.",
            'seo_keywords' => implode(', ', [$noun, $subcategory->name, $category->name, $brand->name]),
            'published_at' => $status === 'published' ? $createdAt : null,
        ]);

        $product->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        $specIndex = 0;
        foreach ($specs as $specName => $values) {
            $product->specifications()->create(['name' => $specName, 'value' => fake()->randomElement($values), 'sort_order' => $specIndex++]);
        }
        foreach (Arr::random(self::COMMON_SPECS, fake()->numberBetween(2, 4), true) as $specName => $values) {
            $product->specifications()->create(['name' => $specName, 'value' => fake()->randomElement(explode('|', $values)), 'sort_order' => $specIndex++]);
        }

        // Main image is the subcategory's photo; extra gallery shots come from the same category.
        $gallery = [$image, ...fake()->randomElements(array_values(array_diff($siblingImages, [$image])), min(2, max(0, count($siblingImages) - 1)))];
        foreach ($gallery as $index => $source) {
            $product->media()->create([
                'type' => 'image',
                'source' => 'upload',
                'disk' => 'public',
                'path' => $this->storeWebp($source, "products/{$product->id}"),
                'is_main' => $index === 0,
                'alt_text' => $name,
                'sort_order' => $index,
            ]);
        }

        return $product;
    }

    private function relateProducts(): void
    {
        $demo = Product::query()->where('sku', 'like', 'DEMO-%')->get(['id', 'category_id']);

        foreach ($demo->groupBy('category_id') as $products) {
            foreach ($products as $product) {
                $related = $products->where('id', '!=', $product->id)->shuffle()->take(4)->pluck('id');
                Product::query()->find($product->id)?->relatedProducts()->syncWithoutDetaching($related);
            }
        }
    }

    private function seedCustomers(): void
    {
        $existing = User::query()->where('email', 'like', '%@demo-customer.test')->count();

        for ($i = $existing; $i < 40; $i++) {
            User::factory()
                ->when($i % 2 === 0, fn ($factory) => $factory->withPhone())
                ->create([
                    'name' => fake()->name(),
                    'email' => "customer{$i}@demo-customer.test",
                    'created_at' => Carbon::now()->subDays(fake()->numberBetween(0, 175)),
                ]);
        }
    }

    /** Converts a bundled photo to WebP on the public disk and returns its path. */
    private function storeWebp(string $source, string $directory): string
    {
        $file = base_path(self::IMAGE_SOURCE."/{$source}.jpg");
        $image = imagecreatefromjpeg($file);

        if ($image === false) {
            throw new \RuntimeException("Could not read demo image {$file}.");
        }

        ob_start();
        imagewebp($image, null, 84);
        $data = (string) ob_get_clean();

        $path = "demo/{$directory}/".Str::uuid().'.webp';
        Storage::disk('public')->put($path, $data);

        return $path;
    }

    /** Category/subcategory images are stored as full public URLs (as CategoryService does). */
    private function publicImage(string $source, string $directory): string
    {
        return Storage::disk('public')->url($this->storeWebp($source, $directory));
    }
}
