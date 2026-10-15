<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Search from '@lucide/svelte/icons/search';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import {
        brands,
        countProducts,
        fetchProducts,
        loadBrands,
        type ProductCard,
    } from '@/data/catalogue';
    import { catalogue } from '@/routes';

    loadBrands();

    let q = $state('');
    let counts = $state<Record<string, number>>({});
    let previews = $state<Record<string, ProductCard[]>>({});

    $effect(() => {
        for (const b of $brands) {
            if (!(b.slug in counts)) {
                countProducts({ brand: b.slug }).then((total) => {
                    counts = { ...counts, [b.slug]: total };
                });
                fetchProducts({ brand: b.slug }, 1, 3).then((result) => {
                    previews = { ...previews, [b.slug]: result.items };
                });
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
    description="Explore the illustrative DENTORA brand directory and discover associated dental equipment."
/>

<section class="container-wide pt-10 pb-20">
    <Breadcrumb items={[{ label: 'Brands' }]} />
    <p class="eyebrow mb-3">Distinct expertise. Shared purpose.</p>
    <h1 class="page-heading">The brands behind the detail.</h1>
    <p class="section-intro max-w-xl">
        Explore a range of specialist perspectives on clinical design, daily
        essentials and dental technology.
    </p>
    <p class="mt-4 text-[11px] leading-6 text-muted-foreground">
        These are sample brand identities for this demo, not actual suppliers or
        verified distributor relationships.
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
                        Explore {counts[b.slug] ?? 0} products
                        <ArrowUpRight class="size-4" />
                    </div>
                </div>
            </Link>
        {/each}
    </div>
    {#if !found.length}
        <div class="empty-state">No brands found. Try another search.</div>
    {/if}
</section>

<ContactBanner />
