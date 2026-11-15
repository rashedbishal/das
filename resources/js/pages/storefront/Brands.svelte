<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Search from '@lucide/svelte/icons/search';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import {
        brands,
        fetchProducts,
        loadBrands,
        type ProductCard,
    } from '@/data/catalogue';
    import { catalogue } from '@/routes';

    let q = $state('');
    let brandsFailed = $state(false);
    let previews = $state<Record<string, ProductCard[]>>({});
    /** Brands whose preview request has started, so each is only fetched once. */
    const requested = new Set<string>();

    function load() {
        brandsFailed = false;
        loadBrands().catch(() => (brandsFailed = true));
    }

    load();

    // Product counts come with the brand list; only the small preview strip is
    // fetched per brand, and a failed preview just stays empty.
    $effect(() => {
        for (const b of $brands) {
            if (!requested.has(b.slug) && b.productsCount > 0) {
                requested.add(b.slug);
                fetchProducts({ brand: b.slug }, 1, 3)
                    .then((result) => {
                        previews = { ...previews, [b.slug]: result.items };
                    })
                    .catch(() => {});
            }
        }
    });

    const found = $derived(
        $brands.filter((b) =>
            `${b.name} ${b.description ?? ''}`
                .toLowerCase()
                .includes(q.toLowerCase()),
        ),
    );
</script>

<PageMeta
    title="Brand Directory"
    description="Browse brands available through DAS and explore their dental products."
/>

<section class="container-wide pt-10 pb-20">
    <Breadcrumb items={[{ label: 'Brands' }]} />
    <p class="eyebrow mb-3">Distinct expertise. Shared purpose.</p>
    <h1 class="page-heading">The brands behind the detail.</h1>
    <p class="section-intro max-w-xl">
        Explore a range of specialist perspectives on clinical design, daily
        essentials and dental technology.
    </p>
    <div class="relative my-9 max-w-md">
        <Search class="absolute top-3.5 left-3 size-4 text-muted-foreground" />
        <input
            class="field search-field"
            aria-label="Search brands"
            placeholder="Find a brand…"
            bind:value={q}
        />
    </div>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {#each found as b (b.id)}
            <Link
                href={catalogue.url({ query: { brand: b.slug } })}
                class="group overflow-hidden rounded-md border"
            >
                <div class="flex h-40 items-center justify-center bg-secondary">
                    <span class="brand-word group-hover:text-primary">{b.name}</span>
                </div>
                <div class="p-6">
                    <h2 class="text-lg">{b.description ?? b.name}</h2>
                    <div class="mt-5 flex gap-2">
                        {#each previews[b.slug] ?? [] as p (p.id)}
                            <img
                                src={p.image}
                                alt={p.name}
                                class="size-14 bg-secondary object-contain"
                                width={56}
                                height={56}
                                loading="lazy"
                            />
                        {/each}
                    </div>
                    <div
                        class="mt-6 flex items-center justify-between text-xs text-primary"
                    >
                        Explore {b.productsCount} products
                        <ArrowUpRight class="size-4" />
                    </div>
                </div>
            </Link>
        {/each}
    </div>
    {#if brandsFailed}
        <div class="empty-state" role="alert">
            We couldn’t load the brand list.
            <button class="ml-1 underline" onclick={load}>Try again</button>
        </div>
    {:else if !found.length && $brands.length}
        <div class="empty-state">No brands found. Try another search.</div>
    {/if}
</section>

<ContactBanner />
