<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Search from '@lucide/svelte/icons/search';
    import SlidersHorizontal from '@lucide/svelte/icons/sliders-horizontal';
    import X from '@lucide/svelte/icons/x';
    import {
        brands,
        categories,
        countProducts,
        fetchProducts,
        filterQuery,
        loadBrands,
        loadCategories,
        parseFilters,
        stockLabel,
        type CatalogueFilters,
        type ProductCard as ProductCardType,
    } from '@/data/catalogue';
    import Button from './Button.svelte';
    import Drawer from './Drawer.svelte';
    import ProductCard from './ProductCard.svelte';

    type FilterKey = keyof CatalogueFilters;

    const stockStatuses = ['in_stock', 'out_of_stock', 'backorder'];

    let {
        urlFor,
        fixedCategory,
    }: {
        urlFor: (query: Record<string, string>) => string;
        fixedCategory?: string;
    } = $props();

    loadCategories();
    loadBrands();

    let filters = $state<CatalogueFilters>(parseFilters(page.url));
    let writtenUrl = page.url;
    let mobile = $state(false);
    let items = $state<ProductCardType[]>([]);
    let total = $state(0);
    let currentPage = $state(1);
    let lastPage = $state(1);
    let loading = $state(false);
    let categoryCounts = $state<Record<string, number>>({});

    $effect(() => {
        if (page.url !== writtenUrl) {
            writtenUrl = page.url;
            filters = parseFilters(page.url);
        }
    });

    $effect(() => {
        const activeFilters = { ...filters, category: fixedCategory ?? filters.category };

        loading = true;

        fetchProducts(activeFilters, 1).then((result) => {
            items = result.items;
            total = result.total;
            currentPage = result.page;
            lastPage = result.lastPage;
            loading = false;
        });
    });

    $effect(() => {
        for (const c of $categories) {
            if (!(c.slug in categoryCounts)) {
                countProducts({ category: c.slug }).then((count) => {
                    categoryCounts = { ...categoryCounts, [c.slug]: count };
                });
            }
        }
    });

    function loadMore() {
        const activeFilters = { ...filters, category: fixedCategory ?? filters.category };

        loading = true;

        fetchProducts(activeFilters, currentPage + 1).then((result) => {
            items = [...items, ...result.items];
            currentPage = result.page;
            lastPage = result.lastPage;
            loading = false;
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
        onChange({ ...filters, [key]: value || undefined });
    }

    function clear() {
        onChange({ q: filters.q, sort: filters.sort });
    }

    const active = $derived(
        Object.entries(filters).filter(
            ([key, value]) => !['q', 'sort'].includes(key) && Boolean(value),
        ) as [FilterKey, string | number][],
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
        {
            key: 'brand' as FilterKey,
            name: 'Brand',
            options: $brands.map((b) => ({ value: b.slug, label: b.name })),
        },
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

        if (key === 'stockStatus') {
            return stockLabel(String(value));
        }

        if (key === 'maxPrice') {
            return `Up to ${value}`;
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
                                checked={filters[group.key] === o.value}
                                onchange={(event) =>
                                    update(
                                        group.key,
                                        event.currentTarget.checked
                                            ? o.value
                                            : undefined,
                                    )}
                            />
                            <span>{o.label}</span>
                            {#if group.key === 'category'}
                                <span class="ml-auto text-muted-foreground">
                                    {categoryCounts[o.value] ?? 0}
                                </span>
                            {/if}
                        </label>
                    {/each}
                </div>
            </fieldset>
        {/each}
        <fieldset class="border-b py-6">
            <legend class="filter-title float-left w-full">Illustrative price</legend>
            <div class="clear-both">
                <label for={priceId} class="text-[11px] text-muted-foreground">
                    Maximum price
                </label>
                <select
                    id={priceId}
                    class="field mt-3"
                    value={filters.maxPrice ? String(filters.maxPrice) : ''}
                    onchange={(event) =>
                        update(
                            'maxPrice',
                            event.currentTarget.value
                                ? Number(event.currentTarget.value)
                                : undefined,
                        )}
                >
                    <option value="">Any price</option>
                    <option value="100">Up to 100</option>
                    <option value="300">Up to 300</option>
                    <option value="500">Up to 500</option>
                    <option value="1000">Up to 1,000</option>
                    <option value="3000">Up to 3,000</option>
                </select>
                <p class="mt-3 text-[10px] leading-5 text-muted-foreground">
                    Price filters exclude products with pricing available on
                    request.
                </p>
            </div>
        </fieldset>
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
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
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
                {#each active as [key, value] (key)}
                    <Button
                        variant="secondary"
                        size="sm"
                        class="h-7 text-[10px]"
                        onclick={() => update(key, undefined)}
                    >
                        {chipLabel(key, value)}
                        <X class="size-3" />
                    </Button>
                {/each}
            </div>
            {#if items.length}
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:gap-5">
                    {#each items as p (p.id)}
                        <ProductCard product={p} />
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
            {:else if !loading}
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
            {/if}
            <p
                class="mt-9 border-t pt-5 text-[10px] leading-6 text-muted-foreground"
            >
                Illustrative catalogue. All brands, availability, prices and
                technical specifications are demo content, not verified
                manufacturer information. Contact us to discuss actual
                equipment.
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
