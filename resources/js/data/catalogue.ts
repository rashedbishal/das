import { writable } from 'svelte/store';
import { ApiError, apiFetch } from '@/lib/api';
import { homepage as homepageApi } from '@/routes/api/v1';
import { index as brandsIndex } from '@/routes/api/v1/brands';
import { index as categoriesIndex } from '@/routes/api/v1/categories';
import {
    index as productsIndex,
    show as productShow,
} from '@/routes/api/v1/products';

export type ProductBrand = { name: string; slug: string };
export type ProductCategory = { name: string; slug: string };

export type ProductCard = {
    id: number;
    slug: string;
    name: string;
    shortDescription: string | null;
    brand: ProductBrand | null;
    image: string | null;
    regularPrice: number | null;
    salePrice: number | null;
    showPrice: boolean;
    /** The product has a price, but it is only shown to signed-in customers. */
    priceLoginRequired: boolean;
    showStock: boolean;
    currency: string;
    stockStatus: string | null;
    stockQuantity: number | null;
    featured: boolean;
};

export type ProductMedia = {
    type: string;
    url: string;
    isMain: boolean;
    altText: string | null;
};

export type ProductSpecification = { name: string; value: string };
export type ProductVariant = {
    name: string;
    price: string | null;
    stockStatus: string | null;
};

export type ProductDetail = ProductCard & {
    sku: string | null;
    modelNumber: string | null;
    category: ProductCategory | null;
    subcategory: ProductCategory | null;
    tags: string[];
    fullDescription: string | null;
    highlights: string[];
    specifications: ProductSpecification[];
    variants: ProductVariant[];
    media: ProductMedia[];
    stockQuantity: number | null;
    relatedProducts: ProductCard[];
    seo: ProductSeo;
};

export type ProductSeo = {
    title: string | null;
    description: string | null;
    canonicalUrl: string | null;
};

/** Optional search-engine title/description set in the CMS. */
export type PageSeo = { title: string | null; description: string | null };

export type CategorySubcategory = {
    id: number;
    name: string;
    slug: string;
    image: string | null;
};

export type Subcategory = CategorySubcategory & {
    description: string | null;
    seo: PageSeo;
};

export type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
    productsCount: number;
    subcategories: CategorySubcategory[];
    seo: PageSeo;
};

export type Brand = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo: string | null;
    productsCount: number;
    seo: PageSeo;
};

export type CatalogueFilters = {
    q?: string | undefined;
    category?: string | undefined;
    subcategory?: string | undefined;
    brand?: string | undefined;
    stockStatus?: string | undefined;
    maxPrice?: number | undefined;
    sort?: string | undefined;
};

export type RawProductCard = {
    id: number;
    name: string;
    slug: string;
    short_description: string | null;
    brand: ProductBrand | null;
    main_image: string | null;
    regular_price: string | null;
    sale_price: string | null;
    currency: string;
    stock_status: string | null;
    stock_quantity: number | null;
    show_price: boolean;
    price_login_required?: boolean;
    show_stock: boolean;
    is_featured: boolean;
};

export type RawProductDetail = RawProductCard & {
    sku: string | null;
    model_number: string | null;
    status: string;
    category: ProductCategory | null;
    subcategory: ProductCategory | null;
    tags: string[];
    full_description: string | null;
    highlights: string[] | null;
    specifications: ProductSpecification[];
    variants: {
        name: string;
        price: string | null;
        stock_status: string | null;
    }[];
    media: {
        type: string;
        url: string;
        is_main: boolean;
        alt_text: string | null;
    }[];
    related_products: RawProductCard[];
    seo: {
        title: string | null;
        description: string | null;
        canonical_url: string | null;
    } | null;
};

function toNumber(value: string | null): number | null {
    if (value === null) {
        return null;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : null;
}

export function mapProductCard(raw: RawProductCard): ProductCard {
    return {
        id: raw.id,
        slug: raw.slug,
        name: raw.name,
        shortDescription: raw.short_description,
        brand: raw.brand,
        image: raw.main_image,
        regularPrice: toNumber(raw.regular_price),
        salePrice: toNumber(raw.sale_price),
        showPrice: raw.show_price,
        priceLoginRequired: raw.price_login_required ?? false,
        showStock: raw.show_stock,
        currency: raw.currency,
        stockStatus: raw.stock_status,
        stockQuantity: raw.stock_quantity,
        featured: raw.is_featured,
    };
}

export function mapProductDetail(raw: RawProductDetail): ProductDetail {
    return {
        ...mapProductCard(raw),
        sku: raw.sku,
        modelNumber: raw.model_number,
        category: raw.category,
        subcategory: raw.subcategory,
        tags: raw.tags ?? [],
        fullDescription: raw.full_description,
        highlights: raw.highlights ?? [],
        specifications: raw.specifications ?? [],
        variants: (raw.variants ?? []).map((variant) => ({
            name: variant.name,
            price: variant.price,
            stockStatus: variant.stock_status,
        })),
        media: (raw.media ?? []).map((m) => ({
            type: m.type,
            url: m.url,
            isMain: m.is_main,
            altText: m.alt_text,
        })),
        relatedProducts: (raw.related_products ?? []).map(mapProductCard),
        seo: {
            title: raw.seo?.title ?? null,
            description: raw.seo?.description ?? null,
            canonicalUrl: raw.seo?.canonical_url ?? null,
        },
    };
}

export type RawCategory = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
    products_count?: number;
    seo?: PageSeo;
    subcategories?: RawSubcategory[];
};

export type RawSubcategory = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    image: string | null;
    seo?: PageSeo;
};

export function mapSubcategory(raw: RawSubcategory): Subcategory {
    return {
        id: raw.id,
        name: raw.name,
        slug: raw.slug,
        image: raw.image,
        description: raw.description ?? null,
        seo: {
            title: raw.seo?.title ?? null,
            description: raw.seo?.description ?? null,
        },
    };
}

export function mapCategory(raw: RawCategory): Category {
    return {
        id: raw.id,
        name: raw.name,
        slug: raw.slug,
        description: raw.description,
        image: raw.image,
        productsCount: raw.products_count ?? 0,
        subcategories: (raw.subcategories ?? []).map((s) => ({
            id: s.id,
            name: s.name,
            slug: s.slug,
            image: s.image,
        })),
        seo: {
            title: raw.seo?.title ?? null,
            description: raw.seo?.description ?? null,
        },
    };
}

export type RawBrand = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo: string | null;
    products_count?: number;
    seo?: PageSeo;
};

export function mapBrand(raw: RawBrand): Brand {
    return {
        id: raw.id,
        name: raw.name,
        slug: raw.slug,
        description: raw.description,
        logo: raw.logo,
        productsCount: raw.products_count ?? 0,
        seo: {
            title: raw.seo?.title ?? null,
            description: raw.seo?.description ?? null,
        },
    };
}

export type ProductPage = {
    items: ProductCard[];
    page: number;
    lastPage: number;
    total: number;
};

const sortMap: Record<string, string> = {
    featured: 'newest',
    name: 'name',
    'price-asc': 'price_asc',
    'price-desc': 'price_desc',
};

export type RawProductPage = {
    data: RawProductCard[];
    meta: { current_page: number; last_page: number; total: number };
};

/** Converts a paginated /api/v1/products payload (or the same shape passed as page props). */
export function toProductPage(result: RawProductPage | null): ProductPage {
    return {
        items: (result?.data ?? []).map(mapProductCard),
        page: result?.meta.current_page ?? 1,
        lastPage: result?.meta.last_page ?? 1,
        total: result?.meta.total ?? 0,
    };
}

export async function fetchProducts(
    filters: CatalogueFilters,
    page = 1,
    perPage = 12,
): Promise<ProductPage> {
    const result = await apiFetch<RawProductPage>(
        productsIndex.url({
            query: {
                search: filters.q,
                category: filters.category,
                subcategory: filters.subcategory,
                brand: filters.brand,
                stock_status: filters.stockStatus,
                max_price: filters.maxPrice,
                sort: sortMap[filters.sort ?? 'featured'] ?? 'newest',
                per_page: perPage,
                page,
            },
        }),
    );

    return toProductPage(result);
}

export class ProductUnauthorizedError extends Error {}

export async function fetchProduct(
    slug: string,
): Promise<ProductDetail | null> {
    try {
        const result = await apiFetch<{ data: RawProductDetail }>(
            productShow.url(slug),
        );

        return result ? mapProductDetail(result.data) : null;
    } catch (error) {
        if (error instanceof ApiError) {
            if (error.status === 401) {
                throw new ProductUnauthorizedError('Unauthenticated');
            }

            if (error.status === 404) {
                return null;
            }
        }

        throw error;
    }
}

export async function fetchFeaturedProducts(): Promise<ProductCard[]> {
    const result = await apiFetch<{ featured_products: RawProductCard[] }>(
        homepageApi.url(),
    );

    return (result?.featured_products ?? []).map(mapProductCard);
}

export const categories = writable<Category[]>([]);
export const brands = writable<Brand[]>([]);

let categoriesPromise: Promise<void> | null = null;
let brandsPromise: Promise<void> | null = null;

/**
 * Loads categories once per page session. A failed load is forgotten (not cached),
 * so the next caller retries instead of being stuck with an empty list.
 */
export function loadCategories(): Promise<void> {
    categoriesPromise ??= apiFetch<{ data: RawCategory[] }>(
        categoriesIndex.url(),
    )
        .then((result) => {
            categories.set((result?.data ?? []).map(mapCategory));
        })
        .catch((error: unknown) => {
            categoriesPromise = null;

            throw error;
        });

    return categoriesPromise;
}

export function loadBrands(): Promise<void> {
    brandsPromise ??= apiFetch<{ data: Parameters<typeof mapBrand>[0][] }>(
        brandsIndex.url(),
    )
        .then((result) => {
            brands.set((result?.data ?? []).map(mapBrand));
        })
        .catch((error: unknown) => {
            brandsPromise = null;

            throw error;
        });

    return brandsPromise;
}

export function formatPrice(product: ProductCard): string {
    const amount = product.salePrice ?? product.regularPrice;

    if (amount === null) {
        return 'Contact for price';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: product.currency || 'BDT',
        maximumFractionDigits: 0,
    }).format(amount);
}

const stockLabels: Record<string, string> = {
    in_stock: 'In stock',
    out_of_stock: 'Out of stock',
    backorder: 'Available on backorder',
    on_order: 'On order',
};

export function stockLabel(status: string): string {
    return stockLabels[status] ?? status;
}

export function parseFilters(url: string): CatalogueFilters {
    const search = new URL(url, 'http://localhost').searchParams;
    const text = (key: string) => search.get(key) || undefined;
    const maxPrice = Number(search.get('maxPrice'));

    return {
        q: text('q'),
        category: text('category'),
        subcategory: text('subcategory'),
        brand: text('brand'),
        stockStatus: text('stockStatus'),
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
