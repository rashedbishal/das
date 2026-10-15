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
    currency: string;
    stockStatus: string;
    featured: boolean;
};

export type ProductMedia = {
    type: string;
    url: string;
    isMain: boolean;
    altText: string | null;
};

export type ProductSpecification = { name: string; value: string };

export type ProductDetail = ProductCard & {
    sku: string | null;
    modelNumber: string | null;
    category: ProductCategory | null;
    subcategory: ProductCategory | null;
    tags: string[];
    fullDescription: string | null;
    highlights: string[];
    specifications: ProductSpecification[];
    media: ProductMedia[];
    stockQuantity: number | null;
    relatedProducts: ProductCard[];
};

export type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
};

export type Brand = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo: string | null;
};

export type CatalogueFilters = {
    q?: string | undefined;
    category?: string | undefined;
    brand?: string | undefined;
    stockStatus?: string | undefined;
    maxPrice?: number | undefined;
    sort?: string | undefined;
};

type RawProductCard = {
    id: number;
    name: string;
    slug: string;
    short_description: string | null;
    brand: ProductBrand | null;
    main_image: string | null;
    regular_price: string;
    sale_price: string | null;
    currency: string;
    stock_status: string;
    is_featured: boolean;
};

type RawProductDetail = RawProductCard & {
    sku: string | null;
    model_number: string | null;
    status: string;
    category: ProductCategory | null;
    subcategory: ProductCategory | null;
    tags: string[];
    full_description: string | null;
    highlights: string[] | null;
    specifications: ProductSpecification[];
    media: {
        type: string;
        url: string;
        is_main: boolean;
        alt_text: string | null;
    }[];
    stock_quantity: number | null;
    related_products: RawProductCard[];
};

function toNumber(value: string | null): number | null {
    if (value === null) {
        return null;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : null;
}

function mapProductCard(raw: RawProductCard): ProductCard {
    return {
        id: raw.id,
        slug: raw.slug,
        name: raw.name,
        shortDescription: raw.short_description,
        brand: raw.brand,
        image: raw.main_image,
        regularPrice: toNumber(raw.regular_price),
        salePrice: toNumber(raw.sale_price),
        currency: raw.currency,
        stockStatus: raw.stock_status,
        featured: raw.is_featured,
    };
}

function mapProductDetail(raw: RawProductDetail): ProductDetail {
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
        media: (raw.media ?? []).map((m) => ({
            type: m.type,
            url: m.url,
            isMain: m.is_main,
            altText: m.alt_text,
        })),
        stockQuantity: raw.stock_quantity,
        relatedProducts: (raw.related_products ?? []).map(mapProductCard),
    };
}

function mapCategory(raw: {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
}): Category {
    return {
        id: raw.id,
        name: raw.name,
        slug: raw.slug,
        description: raw.description,
        image: raw.image,
    };
}

function mapBrand(raw: {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo: string | null;
}): Brand {
    return {
        id: raw.id,
        name: raw.name,
        slug: raw.slug,
        description: raw.description,
        logo: raw.logo,
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

export async function fetchProducts(
    filters: CatalogueFilters,
    page = 1,
    perPage = 12,
): Promise<ProductPage> {
    const result = await apiFetch<{
        data: RawProductCard[];
        meta: { current_page: number; last_page: number; total: number };
    }>(
        productsIndex.url({
            query: {
                search: filters.q,
                category: filters.category,
                brand: filters.brand,
                stock_status: filters.stockStatus,
                max_price: filters.maxPrice,
                sort: sortMap[filters.sort ?? 'featured'] ?? 'newest',
                per_page: perPage,
                page,
            },
        }),
    );

    return {
        items: (result?.data ?? []).map(mapProductCard),
        page: result?.meta.current_page ?? 1,
        lastPage: result?.meta.last_page ?? 1,
        total: result?.meta.total ?? 0,
    };
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

export async function countProducts(
    filters: CatalogueFilters,
): Promise<number> {
    const result = await fetchProducts(filters, 1, 1);

    return result.total;
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

export function loadCategories(): Promise<void> {
    categoriesPromise ??= apiFetch<{ data: Category[] }>(
        categoriesIndex.url(),
    ).then((result) => {
        categories.set((result?.data ?? []).map(mapCategory));
    });

    return categoriesPromise;
}

export function loadBrands(): Promise<void> {
    brandsPromise ??= apiFetch<{ data: Brand[] }>(brandsIndex.url()).then(
        (result) => {
            brands.set((result?.data ?? []).map(mapBrand));
        },
    );

    return brandsPromise;
}

export function formatPrice(product: ProductCard): string {
    const amount = product.salePrice ?? product.regularPrice;

    if (amount === null) {
        return 'Request price';
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
