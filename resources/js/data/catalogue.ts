import a0 from '@/assets/storefront/equipment-a0.jpg';
import a1 from '@/assets/storefront/equipment-a1.jpg';
import a2 from '@/assets/storefront/equipment-a2.jpg';
import a3 from '@/assets/storefront/equipment-a3.jpg';
import a4 from '@/assets/storefront/equipment-a4.jpg';
import a5 from '@/assets/storefront/equipment-a5.jpg';
import a6 from '@/assets/storefront/equipment-a6.jpg';
import a7 from '@/assets/storefront/equipment-a7.jpg';
import b0 from '@/assets/storefront/equipment-b0.jpg';
import b1 from '@/assets/storefront/equipment-b1.jpg';
import b2 from '@/assets/storefront/equipment-b2.jpg';
import b3 from '@/assets/storefront/equipment-b3.jpg';
import b4 from '@/assets/storefront/equipment-b4.jpg';
import b5 from '@/assets/storefront/equipment-b5.jpg';
import b6 from '@/assets/storefront/equipment-b6.jpg';
import b7 from '@/assets/storefront/equipment-b7.jpg';
import c0 from '@/assets/storefront/equipment-c0.jpg';
import c1 from '@/assets/storefront/equipment-c1.jpg';
import c2 from '@/assets/storefront/equipment-c2.jpg';
import c3 from '@/assets/storefront/equipment-c3.jpg';
import c4 from '@/assets/storefront/equipment-c4.jpg';
import c5 from '@/assets/storefront/equipment-c5.jpg';
import c6 from '@/assets/storefront/equipment-c6.jpg';
import c7 from '@/assets/storefront/equipment-c7.jpg';

export const business = {
    name: 'DENTORA',
    phone: '+44 20 7946 0000',
    telephone: '+442079460000',
    email: 'sales@dentora.example',
    address: 'Business address to be confirmed',
    hours: 'Mon–Fri, 9:00–17:00 (illustrative)',
    currency: 'GBP',
};

export const brands = [
    { id: 'nordent', name: 'NORDENT', tagline: 'Considered clinical design' },
    { id: 'prismatek', name: 'prismatek', tagline: 'Clarity in every detail' },
    { id: 'steriva', name: 'STERIVA', tagline: 'Precision in daily practice' },
    { id: 'avelis', name: 'avelis', tagline: 'Tools for modern dentistry' },
    { id: 'forma', name: 'FORMA', tagline: 'Built around your workflow' },
    { id: 'endora', name: 'endora', tagline: 'Focused endodontic technology' },
];

const categoryRows: [string, string, string, string][] = [
    [
        'dental-units',
        'Dental Units & Chairs',
        'Comfort meets clinical precision',
        a0,
    ],
    [
        'imaging',
        'Imaging & X-Ray Equipment',
        'A clearer clinical perspective',
        a1,
    ],
    [
        'sterilization',
        'Sterilization & Autoclaves',
        'A considered hygiene workflow',
        a2,
    ],
    ['handpieces', 'Handpieces & Instruments', 'Precision in your hands', a3],
    [
        'laboratory',
        'Dental Laboratory Equipment',
        'Made for meticulous work',
        a4,
    ],
    [
        'materials',
        'Dental Materials & Consumables',
        'The essentials, carefully selected',
        a5,
    ],
    [
        'endodontics',
        'Endodontic Equipment',
        'Confidence in the finer details',
        a6,
    ],
    ['accessories', 'Clinical Accessories', 'Complete your clinical setup', a7],
];

export const categories = categoryRows.map(
    ([id, name, description, image]) => ({
        id,
        name,
        description,
        image,
    }),
);

export type Product = {
    id: string;
    slug: string;
    name: string;
    brand: string;
    category: string;
    sku: string;
    shortDescription: string;
    description: string;
    image: string;
    gallery: string[];
    price?: number | undefined;
    priceLabel: string;
    specifications: Record<string, string>;
    features: string[];
    availability: string;
    type: string;
    featured: boolean;
    relatedProducts: string[];
};

const rows: [
    string,
    string,
    string,
    string,
    number | undefined,
    string,
    string,
    string,
][] = [
    [
        'LED Dental Treatment Unit',
        'nordent',
        'dental-units',
        a0,
        undefined,
        'Integrated LED light · ergonomic design',
        'Chair positions|3 programmable positions',
        'Clinical system',
    ],
    [
        'Digital Dental X-Ray Unit',
        'prismatek',
        'imaging',
        a1,
        2850,
        'Compact design · adjustable positioning',
        'Tube voltage|60–70 kV (illustrative)',
        'Clinical system',
    ],
    [
        'Class B Autoclave Sterilizer',
        'steriva',
        'sterilization',
        a2,
        1890,
        '23L chamber · intuitive digital controls',
        'Chamber capacity|23 litres',
        'Benchtop equipment',
    ],
    [
        'High-Speed Dental Handpiece',
        'avelis',
        'handpieces',
        a3,
        245,
        'LED illumination · precision spray',
        'Speed|Up to 400,000 rpm (illustrative)',
        'Handheld instrument',
    ],
    [
        'Laboratory Micromotor',
        'forma',
        'laboratory',
        a4,
        395,
        'Variable speed · compact controller',
        'Speed range|0–35,000 rpm (illustrative)',
        'Benchtop equipment',
    ],
    [
        'Universal Composite Kit',
        'avelis',
        'materials',
        a5,
        89,
        'Multi-shade selection · convenient syringes',
        'Contents|4 syringes and accessories',
        'Consumable',
    ],
    [
        'Digital Apex Locator',
        'endora',
        'endodontics',
        a6,
        325,
        'Colour display · compact footprint',
        'Display|3.5-inch colour screen',
        'Clinical system',
    ],
    [
        'LED Curing Light',
        'avelis',
        'accessories',
        a7,
        175,
        'Cordless operation · multiple modes',
        'Wavelength|420–480 nm (illustrative)',
        'Handheld instrument',
    ],
    [
        'Compact Dental Patient Chair',
        'nordent',
        'dental-units',
        b0,
        undefined,
        'Adjustable backrest · supportive upholstery',
        'Upholstery|Easy-clean synthetic finish',
        'Clinical system',
    ],
    [
        'Digital Dental X-Ray Sensor',
        'prismatek',
        'imaging',
        b1,
        1490,
        'USB connection · high-resolution imaging',
        'Active area|Size 1, 20 × 30 mm (illustrative)',
        'Clinical system',
    ],
    [
        'Ultrasonic Instrument Cleaner',
        'steriva',
        'sterilization',
        b2,
        285,
        'Stainless steel tank · timed cleaning',
        'Tank capacity|3 litres',
        'Benchtop equipment',
    ],
    [
        'Contra-Angle Handpiece',
        'avelis',
        'handpieces',
        b3,
        195,
        'Balanced grip · smooth rotary action',
        'Transmission|1:1 direct drive',
        'Handheld instrument',
    ],
    [
        'Dental Porcelain Furnace',
        'forma',
        'laboratory',
        b4,
        2450,
        'Programmable cycles · compact chamber',
        'Program memory|100 presets (illustrative)',
        'Benchtop equipment',
    ],
    [
        'Dental Examination Instrument Set',
        'avelis',
        'materials',
        b5,
        65,
        'Six-piece set · stainless steel',
        'Contents|Mirror, probe and examination tools',
        'Handheld instrument',
    ],
    [
        'Cordless Endodontic Motor',
        'endora',
        'endodontics',
        b6,
        465,
        'Adjustable torque · lightweight handpiece',
        'Speed range|150–800 rpm (illustrative)',
        'Handheld instrument',
    ],
    [
        'HD Intraoral Camera',
        'prismatek',
        'accessories',
        b7,
        295,
        'LED lighting · detailed chairside imaging',
        'Resolution|1920 × 1080 (illustrative)',
        'Handheld instrument',
    ],
    [
        'Ergonomic Saddle Stool',
        'nordent',
        'dental-units',
        c0,
        265,
        'Height adjustment · supportive saddle seat',
        'Seat height|450–600 mm (illustrative)',
        'Clinical accessory',
    ],
    [
        'Phosphor Plate Scanner',
        'prismatek',
        'imaging',
        c1,
        undefined,
        'Compact footprint · digital workflow',
        'Plate formats|Sizes 0–3 (illustrative)',
        'Benchtop equipment',
    ],
    [
        'Instrument Pouch Sealer',
        'steriva',
        'sterilization',
        c2,
        345,
        'Integrated roll holder · consistent sealing',
        'Seal width|12 mm (illustrative)',
        'Benchtop equipment',
    ],
    [
        'Ultrasonic Scaler',
        'avelis',
        'handpieces',
        c3,
        295,
        'Adjustable output · ergonomic handpiece',
        'Frequency|28–32 kHz (illustrative)',
        'Clinical system',
    ],
    [
        'Laboratory Vacuum Mixer',
        'forma',
        'laboratory',
        c4,
        695,
        'Clear mixing bowl · adjustable timer',
        'Mixing capacity|500 ml (illustrative)',
        'Benchtop equipment',
    ],
    [
        'Precision Impression Material Kit',
        'avelis',
        'materials',
        c5,
        75,
        'Cartridge delivery · precision mixing tips',
        'Contents|2 cartridges and mixing tips',
        'Consumable',
    ],
    [
        'Cordless Obturation Pen',
        'endora',
        'endodontics',
        c6,
        285,
        'Fine heated tip · cordless control',
        'Temperature|Adjustable settings (illustrative)',
        'Handheld instrument',
    ],
    [
        'LED Examination Lamp',
        'nordent',
        'accessories',
        c7,
        495,
        'Articulated arm · focused illumination',
        'Light output|Adjustable LED (illustrative)',
        'Clinical accessory',
    ],
];

export const products: Product[] = rows.map(
    (
        [name, brand, category, image, price, shortDescription, spec, type],
        i,
    ) => {
        const [key = 'Specification', value = 'To be confirmed'] =
            spec.split('|');
        const sku = `DT-${String(i + 1).padStart(4, '0')}`;

        return {
            id: `dt-${i + 1}`,
            slug: name.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
            name,
            brand,
            category,
            image,
            gallery: [image],
            price,
            priceLabel: price
                ? new Intl.NumberFormat('en-GB', {
                      style: 'currency',
                      currency: business.currency,
                      maximumFractionDigits: 0,
                  }).format(price)
                : 'Request price',
            sku,
            shortDescription,
            description: `The ${name.toLowerCase()} brings a considered approach to your daily workflow. ${shortDescription}. Explore the example configuration below and speak with our team about product suitability, installation requirements and available options.`,
            specifications: {
                [key]: value,
                'Model reference': sku,
                'Product type': type,
            },
            features: shortDescription.split(' · '),
            availability:
                i % 4 === 0
                    ? 'On request'
                    : i % 3 === 0
                      ? 'Available to enquire'
                      : 'In stock (demo)',
            type,
            featured: [0, 2, 3, 6, 9, 15, 19, 4].includes(i),
            relatedProducts: [],
        };
    },
);

products.forEach(
    (p) =>
        (p.relatedProducts = products
            .filter((x) => x.category === p.category && x.id !== p.id)
            .map((x) => x.slug)),
);

export type CatalogueFilters = {
    q?: string | undefined;
    category?: string | undefined;
    brand?: string | undefined;
    availability?: string | undefined;
    type?: string | undefined;
    maxPrice?: number | undefined;
    sort?: string | undefined;
};

export function filterProducts(
    filters: CatalogueFilters,
    dataset: Product[] = products,
) {
    const q = (filters.q ?? '').toLowerCase().trim();

    return dataset
        .filter(
            (p) =>
                (!q ||
                    [
                        p.name,
                        p.shortDescription,
                        p.brand,
                        categories.find((c) => c.id === p.category)?.name,
                        ...Object.values(p.specifications),
                    ]
                        .join(' ')
                        .toLowerCase()
                        .includes(q)) &&
                (!filters.category || p.category === filters.category) &&
                (!filters.brand || p.brand === filters.brand) &&
                (!filters.availability ||
                    p.availability === filters.availability) &&
                (!filters.type || p.type === filters.type) &&
                (!filters.maxPrice ||
                    (p.price !== undefined && p.price <= filters.maxPrice)),
        )
        .sort((a, b) =>
            filters.sort === 'name'
                ? a.name.localeCompare(b.name)
                : filters.sort === 'price-asc'
                  ? (a.price ?? Infinity) - (b.price ?? Infinity)
                  : filters.sort === 'price-desc'
                    ? (b.price ?? -Infinity) - (a.price ?? -Infinity)
                    : Number(b.featured) - Number(a.featured),
        );
}

export function parseFilters(url: string): CatalogueFilters {
    const search = new URL(url, 'http://localhost').searchParams;
    const text = (key: string) => search.get(key) || undefined;
    const maxPrice = Number(search.get('maxPrice'));

    return {
        q: text('q'),
        category: text('category'),
        brand: text('brand'),
        availability: text('availability'),
        type: text('type'),
        maxPrice: maxPrice > 0 ? maxPrice : undefined,
        sort: text('sort'),
    };
}

export function filterQuery(filters: CatalogueFilters) {
    return Object.fromEntries(
        Object.entries(filters)
            .filter(([, value]) => value !== undefined && value !== '')
            .map(([key, value]) => [key, String(value)]),
    );
}

export function pathSegment(url: string, index: number) {
    const segments = new URL(url, 'http://localhost').pathname
        .split('/')
        .filter(Boolean);

    return decodeURIComponent(segments[index] ?? '');
}
