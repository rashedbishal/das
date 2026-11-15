<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import CatalogueView from '@/components/storefront/CatalogueView.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import {
        mapCategory,
        toProductPage,
        type RawCategory,
        type RawProductPage,
    } from '@/data/catalogue';
    import { catalogue } from '@/routes';
    import { index as categoriesIndex, show, subcategory as subcategoryShow } from '@/routes/categories';

    // The category is resolved server-side; unknown slugs get a real 404 response.
    let {
        category,
        initialProducts = null,
    }: { category: RawCategory; initialProducts?: RawProductPage | null } =
        $props();

    const c = $derived(mapCategory(category));
    const initial = $derived(
        initialProducts ? toProductPage(initialProducts) : null,
    );
    const intro = $derived(
        c.description?.trim()
            ? `${c.description.trim().replace(/\.$/, '')}. Explore equipment selected around your clinical workflow.`
            : 'Explore equipment selected around your clinical workflow.',
    );
</script>

<PageMeta
    title={c.seo.title?.trim() || c.name}
    description={c.seo.description?.trim() ||
        c.description?.trim() ||
        `Explore DAS ${c.name.toLowerCase()} products and specifications. Contact us for product guidance.`}
    image={c.image}
    canonical={show.url(c.slug)}
/>

<section class="container-wide pt-10 pb-16">
    <Breadcrumb
        items={[
            { label: 'Categories', href: categoriesIndex.url() },
            { label: c.name },
        ]}
    />
    <div
        class="mb-10 grid overflow-hidden rounded-md border bg-secondary md:grid-cols-[1fr_220px]"
    >
        <div class="p-7 lg:p-10">
            <p class="eyebrow mb-3">A considered collection</p>
            <h1 class="page-heading">{c.name}</h1>
            <p class="section-intro">{intro}</p>
            {#if c.subcategories.length > 0}
                <nav aria-label="Subcategories" class="mt-6 flex flex-wrap gap-2">
                    {#each c.subcategories as sub (sub.id)}
                        <Link
                            href={subcategoryShow.url([c.slug, sub.slug])}
                            class="rounded-full border bg-background px-3 py-1 text-[11px] hover:border-primary hover:text-primary"
                        >
                            {sub.name}
                        </Link>
                    {/each}
                </nav>
            {/if}
            <div class="mt-7 flex flex-wrap gap-4 text-[10px] text-primary">
                <Link href={catalogue.url({ query: { category: c.slug } })}>
                    View all in this category →
                </Link>
            </div>
        </div>
        {#if c.image}
            <img
                src={c.image}
                width={384}
                height={512}
                class="hidden h-64 w-full object-contain mix-blend-multiply md:block"
                alt={c.name}
            />
        {/if}
    </div>
    {#key c.slug}
        <CatalogueView
            fixedCategory={c.slug}
            urlFor={(query) => show.url(c.slug, { query })}
            {initial}
        />
    {/key}
</section>

<ContactBanner />
