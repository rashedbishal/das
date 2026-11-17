<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Search from '@lucide/svelte/icons/search';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { fetchProducts, mapBrand, type ProductCard, type RawBrand } from '@/data/catalogue';
    import { brands as brandsIndex } from '@/routes';
    import { show as brandShow } from '@/routes/brands';

    // Rendered server-side, so every brand (and its link) is in the HTML.
    let { brands }: { brands: RawBrand[] } = $props();

    const list = $derived(brands.map(mapBrand));

    let q = $state('');
    let previews = $state<Record<string, ProductCard[]>>({});
    /** Brands whose preview request has started, so each is only fetched once. */
    const requested = new Set<string>();

    // Only the small preview strip is fetched per brand, after the page loads;
    // a failed preview just stays empty.
    $effect(() => {
        for (const b of list) {
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
        list.filter((b) =>
            `${b.name} ${b.description ?? ''}`
                .toLowerCase()
                .includes(q.toLowerCase()),
        ),
    );
</script>

<PageMeta
    title="Brand Directory"
    description="Browse brands available through DAS and explore their dental products."
    canonical={brandsIndex.url()}
/>

<section class="container-wide pt-10 pb-20">
    <Breadcrumb items={[{ label: 'Brands' }]} />
    <p class="sf-enter eyebrow mb-3">Distinct expertise. Shared purpose.</p>
    <h1 class="sf-enter page-heading" style:--sf-i={1}>The brands behind the detail.</h1>
    <p class="sf-enter section-intro max-w-xl" style:--sf-i={2}>
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
                href={brandShow.url(b.slug)}
                class="group overflow-hidden rounded-md border"
            >
                <div class="flex h-40 items-center justify-center bg-secondary">
                    {#if b.logo}
                        <img src={b.logo} alt="{b.name} logo" class="max-h-24 max-w-[70%] object-contain" loading="lazy" />
                    {:else}
                        <span class="brand-word group-hover:text-primary">{b.name}</span>
                    {/if}
                </div>
                <div class="p-6">
                    <h2 class="text-lg">{b.name}</h2>
                    {#if b.description}
                        <p class="mt-2 line-clamp-3 text-xs leading-6 text-muted-foreground">{b.description}</p>
                    {/if}
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
    {#if !found.length && list.length}
        <div class="empty-state">No brands found. Try another search.</div>
    {:else if !list.length}
        <div class="empty-state">No brands yet.</div>
    {/if}
</section>

<ContactBanner />
