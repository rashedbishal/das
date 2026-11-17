<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { mapCategory, type RawCategory } from '@/data/catalogue';
    import { index as categoriesIndex, show, subcategory as subcategoryShow } from '@/routes/categories';

    // Rendered server-side, so every category (and its links) is in the HTML.
    let { categories }: { categories: RawCategory[] } = $props();

    const list = $derived(categories.map(mapCategory));
</script>

<PageMeta
    title="Equipment Categories"
    description="Browse DAS dental equipment by category, from instruments to laboratory materials."
    canonical={categoriesIndex.url()}
/>

<section class="container-wide pt-10 pb-16">
    <Breadcrumb items={[{ label: 'Categories' }]} />
    <p class="sf-enter eyebrow mb-3">Built around your practice</p>
    <h1 class="sf-enter page-heading" style:--sf-i={1}>Everything Your Practice Needs</h1>
    <p class="sf-enter section-intro mb-10 max-w-xl" style:--sf-i={2}>
        Explore the tools, technology and clinical essentials that bring your
        practice together.
    </p>
    <div class="grid gap-6 md:grid-cols-2">
        {#each list as c, i (c.id)}
            <Link
                href={show.url(c.slug)}
                class="group grid h-68 grid-cols-[1.1fr_1fr] overflow-hidden rounded-md border lg:h-72"
            >
                <div class="flex min-h-0 min-w-0 flex-col items-start p-6 lg:p-8">
                    <span class="mb-5 text-[10px] text-primary"
                        >0{i + 1} / EQUIPMENT</span
                    >
                    <h2 class="line-clamp-2 text-xl leading-snug">{c.name}</h2>
                    <p class="mt-3 line-clamp-3 text-xs leading-6 text-muted-foreground">
                        {c.description}
                    </p>
                    <span
                        class="mt-auto flex items-center gap-2 pt-5 text-[11px] text-primary"
                    >
                        {c.productsCount} products
                        <ArrowUpRight class="size-4" />
                    </span>
                </div>
                <!-- The image fills its half edge to edge at any uploaded ratio
                     (centre kept), and fades to white towards the text. -->
                <div class="relative overflow-hidden bg-secondary">
                    {#if c.image}
                        <img
                            class="absolute inset-0 size-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                            src={c.image}
                            alt={c.name}
                            loading="lazy"
                            width={384}
                            height={512}
                        />
                        <div
                            class="pointer-events-none absolute inset-0 bg-linear-to-r from-background via-background/40 via-25% to-transparent to-60%"
                            aria-hidden="true"
                        ></div>
                    {:else}
                        <div class="flex h-full items-center justify-center">
                            <LayoutGrid class="size-8 text-primary/40" strokeWidth={1.3} />
                        </div>
                    {/if}
                </div>
            </Link>
        {/each}
    </div>
    {#if list.some((c) => c.subcategories.length > 0)}
        <nav aria-label="Subcategories" class="mt-14 border-t pt-10">
            <h2 class="mb-6 text-xl">Browse by subcategory</h2>
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                {#each list.filter((c) => c.subcategories.length > 0) as c (c.id)}
                    <div>
                        <h3 class="mb-3 text-sm font-semibold">
                            <Link href={show.url(c.slug)} class="hover:text-primary">{c.name}</Link>
                        </h3>
                        <ul class="space-y-2 text-xs text-muted-foreground">
                            {#each c.subcategories as sub (sub.id)}
                                <li>
                                    <Link href={subcategoryShow.url([c.slug, sub.slug])} class="hover:text-primary">
                                        {sub.name}
                                    </Link>
                                </li>
                            {/each}
                        </ul>
                    </div>
                {/each}
            </div>
        </nav>
    {/if}
</section>

<ContactBanner />
