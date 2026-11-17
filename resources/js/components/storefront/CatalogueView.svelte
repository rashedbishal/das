<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Search from '@lucide/svelte/icons/search';
    import SlidersHorizontal from '@lucide/svelte/icons/sliders-horizontal';
    import X from '@lucide/svelte/icons/x';
    import { flip } from 'svelte/animate';
    import { quintOut } from 'svelte/easing';
    import { fade } from 'svelte/transition';
    import {
        brands,
        categories,
        fetchBrandSubcategories,
        fetchProducts,
        filterQuery,
        filterValues,
        loadBrands,
        loadCategories,
        parseFilters,
        stockLabel,
        toggleFilterValue,
        type BrandSubcategory,
        type CatalogueFilters,
        type PriceRange,
        type ProductCard as ProductCardType,
        type ProductPage,
    } from '@/data/catalogue';
    import { motionMs } from '@/lib/motion.svelte';
    import Button from './Button.svelte';
    import Drawer from './Drawer.svelte';
    import PriceRangeFilter from './PriceRangeFilter.svelte';
    import ProductCard from './ProductCard.svelte';
    import ProductCardSkeleton from './ProductCardSkeleton.svelte';

    type FilterKey = keyof CatalogueFilters;

    /** Checkbox groups: each holds several comma-separated slugs (any of them matches). */
    const multiKeys: FilterKey[] = ['category', 'subcategory', 'brand', 'stockStatus'];

    const stockStatuses = ['in_stock', 'out_of_stock', 'backorder'];

    let {
        urlFor,
        fixedCategory,
        fixedSubcategory,
        fixedBrand,
        initial = null,
    }: {
        urlFor: (query: Record<string, string>) => string;
        fixedCategory?: string;
        fixedSubcategory?: string;
        fixedBrand?: string;
        /** First page rendered server-side for the current URL's filters. */
        initial?: ProductPage | null;
    } = $props();

    // Background lookups for the filter lists; a failure only leaves a list empty.
    loadCategories().catch(() => {});
    loadBrands().catch(() => {});

    /** Delay before fetching after a filter change, so typing doesn't fire a request per key. */
    const FETCH_DELAY_MS = 250;

    // svelte-ignore state_referenced_locally
    const firstPage = initial;
    let filters = $state<CatalogueFilters>(parseFilters(page.url));
    let writtenUrl = page.url;
    let mobile = $state(false);
    let items = $state<ProductCardType[]>(firstPage?.items ?? []);
    let total = $state(firstPage?.total ?? 0);
    let currentPage = $state(firstPage?.page ?? 1);
    let lastPage = $state(firstPage?.lastPage ?? 1);
    let priceRange = $state<PriceRange | null>(firstPage?.priceRange ?? null);
    let loading = $state(false);
    let failed = $state(false);
    let skipInitialFetch = firstPage !== null;
    /** Only the latest request may update the list, so slow stale responses are ignored. */
    let latestRequest = 0;

    // Prices are for signed-in customers, so guests can't filter or sort by price.
    const canUsePrice = $derived(!!page.props.auth.user);

    function withFixed(value: CatalogueFilters): CatalogueFilters {
        return {
            ...value,
            category: fixedCategory ?? value.category,
            subcategory: fixedSubcategory ?? value.subcategory,
            brand: fixedBrand ?? value.brand,
            ...(canUsePrice
                ? {}
                : {
                      minPrice: undefined,
                      maxPrice: undefined,
                      sort: value.sort?.startsWith('price') ? undefined : value.sort,
                  }),
        };
    }

    // Once a brand is chosen (or fixed by the page), offer the subcategories that
    // hold that brand's products, narrowed to the current category if any.
    const subcategoryBrand = $derived(fixedBrand ?? filters.brand);
    const subcategoryCategory = $derived(fixedCategory ?? filters.category);
    let brandSubcategories = $state<BrandSubcategory[]>([]);
    let latestSubcategoryRequest = 0;

    $effect(() => {
        const brand = subcategoryBrand;
        const category = subcategoryCategory;
        const request = ++latestSubcategoryRequest;

        if (fixedSubcategory || !brand) {
            brandSubcategories = [];

            return;
        }

        fetchBrandSubcategories(brand, category)
            .then((result) => {
                if (request === latestSubcategoryRequest) {
                    brandSubcategories = result;
                }
            })
            .catch(() => {
                if (request === latestSubcategoryRequest) {
                    brandSubcategories = [];
                }
            });
    });

    const subcategoryCounts = $derived(
        Object.fromEntries(brandSubcategories.map((s) => [s.slug, s.productsCount])),
    );

    const categoryCounts = $derived(
        Object.fromEntries($categories.map((c) => [c.slug, c.productsCount])),
    );

    $effect(() => {
        if (page.url !== writtenUrl) {
            writtenUrl = page.url;
            filters = parseFilters(page.url);
        }
    });

    $effect(() => {
        const activeFilters = withFixed(filters);

        if (skipInitialFetch) {
            skipInitialFetch = false;

            return;
        }

        const request = ++latestRequest;
        loading = true;
        failed = false;

        const timer = setTimeout(() => {
            fetchProducts(activeFilters, 1)
                .then((result) => {
                    if (request !== latestRequest) {
                        return;
                    }

                    items = result.items;
                    total = result.total;
                    currentPage = result.page;
                    lastPage = result.lastPage;
                    priceRange = result.priceRange;
                })
                .catch(() => {
                    if (request === latestRequest) {
                        failed = true;
                    }
                })
                .finally(() => {
                    if (request === latestRequest) {
                        loading = false;
                    }
                });
        }, FETCH_DELAY_MS);

        return () => clearTimeout(timer);
    });

    function retry() {
        filters = { ...filters };
    }

    function loadMore() {
        const activeFilters = withFixed(filters);
        const request = ++latestRequest;

        loading = true;
        failed = false;

        fetchProducts(activeFilters, currentPage + 1)
            .then((result) => {
                if (request !== latestRequest) {
                    return;
                }

                items = [...items, ...result.items];
                currentPage = result.page;
                lastPage = result.lastPage;
            })
            .catch(() => {
                if (request === latestRequest) {
                    failed = true;
                }
            })
            .finally(() => {
                if (request === latestRequest) {
                    loading = false;
                }
            });
    }

    function onChange(value: CatalogueFilters) {
        filters = value;
        writtenUrl = urlFor(filterQuery(value));
        router.replace({
            url: writtenUrl,
            preserveState: true,
            preserveScroll: true,
        });
    }

    function update(key: FilterKey, value: string | number | undefined) {
        const next = { ...filters, [key]: value || undefined };

        // A chosen subcategory belongs to the old brand/category scope.
        if ((key === 'brand' || key === 'category') && !fixedSubcategory) {
            next.subcategory = undefined;
        }

        onChange(next);
    }

    /** Ticks or unticks one option in a multi-select group. */
    function toggle(key: FilterKey, value: string, on: boolean) {
        update(key, toggleFilterValue(filters[key] as string | undefined, value, on));
    }

    function setPrice(min: number | undefined, max: number | undefined) {
        onChange({ ...filters, minPrice: min, maxPrice: max });
    }

    /** Removes one chip: a single option of a multi-select group, or the whole filter. */
    function removeChip(key: FilterKey, value: string | number) {
        if (multiKeys.includes(key)) {
            toggle(key, String(value), false);
        } else {
            update(key, undefined);
        }
    }

    function clear() {
        onChange({ q: filters.q, sort: filters.sort });
    }

    // One chip per chosen option, so each can be removed on its own.
    const active = $derived(
        (
            Object.entries(filters).filter(
                ([key, value]) => !['q', 'sort'].includes(key) && Boolean(value),
            ) as [FilterKey, string | number][]
        ).flatMap(([key, value]) =>
            multiKeys.includes(key)
                ? filterValues(value).map((item) => [key, item] as [FilterKey, string])
                : [[key, value] as [FilterKey, string | number]],
        ),
    );

    const filterGroups = $derived([
        ...(!fixedCategory
            ? [
                  {
                      key: 'category' as FilterKey,
                      name: 'Category',
                      options: $categories.map((c) => ({
                          value: c.slug,
                          label: c.name,
                      })),
                  },
              ]
            : []),
        ...(!fixedBrand
            ? [
                  {
                      key: 'brand' as FilterKey,
                      name: 'Brand',
                      options: $brands.map((b) => ({ value: b.slug, label: b.name })),
                  },
              ]
            : []),
        ...(brandSubcategories.length > 0
            ? [
                  {
                      key: 'subcategory' as FilterKey,
                      name: 'Subcategory',
                      options: brandSubcategories.map((s) => ({
                          value: s.slug,
                          label: s.name,
                      })),
                  },
              ]
            : []),
        {
            key: 'stockStatus' as FilterKey,
            name: 'Availability',
            options: stockStatuses.map((s) => ({
                value: s,
                label: stockLabel(s),
            })),
        },
    ]);

    function chipLabel(key: FilterKey, value: string | number) {
        if (key === 'category') {
            return $categories.find((c) => c.slug === value)?.name;
        }

        if (key === 'brand') {
            return $brands.find((b) => b.slug === value)?.name;
        }

        if (key === 'subcategory') {
            return brandSubcategories.find((s) => s.slug === value)?.name ?? value;
        }

        if (key === 'stockStatus') {
            return stockLabel(String(value));
        }

        if (key === 'minPrice') {
            return `From ${Number(value).toLocaleString('en-US')}`;
        }

        if (key === 'maxPrice') {
            return `Up to ${Number(value).toLocaleString('en-US')}`;
        }

        return value;
    }
</script>

{#snippet filterPanel(priceId: string)}
    <div>
        <div class="flex items-center justify-between border-b pb-5">
            <span class="text-sm font-semibold">Refine your selection</span>
            <Button variant="link" class="h-auto p-0 text-[10px]" onclick={clear}>
                Clear all
            </Button>
        </div>
        {#each filterGroups as group (group.key)}
            <fieldset class="border-b py-6">
                <legend class="filter-title float-left w-full">{group.name}</legend>
                <div class="clear-both space-y-3">
                    {#each group.options as o (o.value)}
                        <label
                            class="flex cursor-pointer items-start gap-2.5 text-[11px] leading-5"
                        >
                            <input
                                type="checkbox"
                                class="mt-0.5 size-3.5 accent-primary"
                                checked={filterValues(filters[group.key]).includes(
                                    o.value,
                                )}
                                onchange={(event) =>
                                    toggle(
                                        group.key,
                                        o.value,
                                        event.currentTarget.checked,
                                    )}
                            />
                            <span>{o.label}</span>
                            {#if group.key === 'category'}
                                <span class="ml-auto text-muted-foreground">
                                    {categoryCounts[o.value] ?? 0}
                                </span>
                            {:else if group.key === 'subcategory'}
                                <span class="ml-auto text-muted-foreground">
                                    {subcategoryCounts[o.value] ?? 0}
                                </span>
                            {/if}
                        </label>
                    {/each}
                </div>
            </fieldset>
        {/each}
        {#if canUsePrice}
        <fieldset class="border-b py-6">
            <legend class="filter-title float-left w-full">Price</legend>
            <div class="clear-both">
                <PriceRangeFilter
                    id={priceId}
                    range={priceRange}
                    min={filters.minPrice}
                    max={filters.maxPrice}
                    currency={items[0]?.currency || 'BDT'}
                    onchange={setPrice}
                />
                <p class="mt-3 text-[10px] leading-5 text-muted-foreground">
                    Price filters exclude products with pricing available on
                    request.
                </p>
            </div>
        </fieldset>
        {/if}
    </div>
{/snippet}

<div>
    <div
        class="mb-7 flex flex-wrap items-center gap-3 border-b pb-6 md:flex-nowrap"
    >
        <div class="relative min-w-44 flex-1">
            <Search
                class="absolute top-3.5 left-3.5 size-4 text-muted-foreground"
            />
            <input
                class="field search-field"
                value={filters.q ?? ''}
                oninput={(event) => update('q', event.currentTarget.value)}
                aria-label="Search catalogue"
                placeholder="Search equipment, brands, specifications…"
            />
            {#if filters.q}
                <Button
                    size="icon"
                    variant="ghost"
                    aria-label="Clear search"
                    class="absolute top-1 right-1"
                    onclick={() => update('q', undefined)}
                >
                    <X class="size-3.5" />
                </Button>
            {/if}
        </div>
        <Button
            variant="outline"
            class="h-11 lg:hidden"
            aria-expanded={mobile}
            onclick={() => (mobile = true)}
        >
            <SlidersHorizontal />
            Filters{active.length > 0 ? ` (${active.length})` : ''}
        </Button>
        <label class="flex items-center gap-2 text-[11px] text-muted-foreground">
            <span class="hidden whitespace-nowrap sm:block">Sort by</span>
            <select
                aria-label="Sort products"
                class="field w-auto min-w-40"
                value={filters.sort ?? 'featured'}
                onchange={(event) => update('sort', event.currentTarget.value)}
            >
                <option value="featured">Featured</option>
                <option value="name">Name: A–Z</option>
                {#if canUsePrice}
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                {/if}
            </select>
        </label>
    </div>
    <div
        class="grid gap-9 lg:grid-cols-[215px_1fr] xl:grid-cols-[235px_1fr] xl:gap-12"
    >
        <aside class="hidden lg:block">{@render filterPanel('max-price')}</aside>
        <div class="min-w-0">
            <div class="mb-5 flex min-h-7 flex-wrap items-center gap-3">
                <p class="mr-auto text-[11px] text-muted-foreground">
                    Showing {items.length} of {total} products
                </p>
                {#each active as [key, value] (`${key}:${value}`)}
                    <Button
                        variant="secondary"
                        size="sm"
                        class="h-7 text-[10px]"
                        onclick={() => removeChip(key, value)}
                    >
                        {chipLabel(key, value)}
                        <X class="size-3" />
                    </Button>
                {/each}
            </div>
            {#if failed}
                <div class="empty-state" role="alert">
                    <h2 class="text-xl">We couldn’t load the equipment list.</h2>
                    <p class="mt-3 mb-6 text-xs text-muted-foreground">
                        Please check your connection and try again.
                    </p>
                    <Button variant="outline" onclick={retry}>Try again</Button>
                </div>
            {/if}
            {#if items.length}
                <div
                    class="sf-stagger grid grid-cols-2 gap-4 md:grid-cols-3 lg:gap-5"
                >
                    {#each items as p (p.id)}
                        <!-- Cards glide to their new place when filters change;
                             new ones fade in. -->
                        <div
                            class="grid min-w-0"
                            animate:flip={{
                                duration: motionMs(420),
                                easing: quintOut,
                            }}
                            in:fade={{ duration: motionMs(260) }}
                        >
                            <ProductCard product={p} />
                        </div>
                    {/each}
                </div>
                {#if currentPage < lastPage}
                    <div class="mt-9 text-center">
                        <Button
                            variant="outline"
                            class="h-11 px-7"
                            disabled={loading}
                            onclick={loadMore}
                        >
                            Explore more equipment
                            <ArrowRight />
                        </Button>
                    </div>
                {/if}
            {:else if !loading && !failed}
                <div class="empty-state">
                    <Search class="mx-auto mb-5 size-8 text-primary" />
                    <h2 class="text-xl">No equipment found.</h2>
                    <p class="mt-3 mb-6 text-xs text-muted-foreground">
                        Try a different search or broaden your selection.
                    </p>
                    <Button
                        variant="outline"
                        onclick={() => {
                            onChange({});
                        }}
                    >
                        Reset search and filters
                    </Button>
                </div>
            {:else if loading && !failed}
                <div
                    class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:gap-5"
                    role="status"
                    aria-label="Loading products"
                >
                    {#each [0, 1, 2, 3, 4, 5] as n (n)}
                        <ProductCardSkeleton />
                    {/each}
                </div>
            {/if}
            <p
                class="mt-9 border-t pt-5 text-[10px] leading-6 text-muted-foreground"
            >
                Product availability, prices and specifications may change.
                Contact DAS to confirm the latest details before buying.
            </p>
        </div>
    </div>
</div>

<Drawer
    bind:open={mobile}
    side="left"
    label="Equipment filters"
    class="w-[90%] max-w-sm overflow-y-auto"
>
    <h2 class="mb-2 text-lg font-semibold text-foreground">Equipment filters</h2>
    <p class="mb-6 text-sm text-muted-foreground">
        Find equipment for your practice.
    </p>
    {@render filterPanel('max-price-mobile')}
    <Button class="mt-6 w-full" onclick={() => (mobile = false)}>
        Show {total} products
        <ArrowRight />
    </Button>
</Drawer>
