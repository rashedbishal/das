<script lang="ts">
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import CatalogueView from '@/components/storefront/CatalogueView.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import {
        mapCategory,
        mapSubcategory,
        toProductPage,
        type RawCategory,
        type RawProductPage,
        type RawSubcategory,
    } from '@/data/catalogue';
    import { Link } from '@inertiajs/svelte';
    import { index as categoriesIndex, show, subcategory as subcategoryShow } from '@/routes/categories';

    // Resolved server-side; unknown or inactive subcategories get a real 404.
    let {
        category,
        subcategory,
        initialProducts = null,
    }: {
        category: RawCategory;
        subcategory: RawSubcategory;
        initialProducts?: RawProductPage | null;
    } = $props();

    const c = $derived(mapCategory(category));
    const s = $derived(mapSubcategory(subcategory));
    const initial = $derived(initialProducts ? toProductPage(initialProducts) : null);
    const siblings = $derived(c.subcategories.filter((sub) => sub.slug !== s.slug));
    const description = $derived(
        s.seo.description?.trim() ||
            s.description?.trim() ||
            `Explore DAS ${s.name.toLowerCase()} in ${c.name.toLowerCase()}: products, specifications and expert guidance.`,
    );
</script>

<PageMeta
    title={s.seo.title?.trim() || `${s.name} | ${c.name}`}
    {description}
    image={s.image ?? c.image}
    canonical={subcategoryShow.url([c.slug, s.slug])}
/>

<section class="container-wide pt-10 pb-16">
    <Breadcrumb
        items={[
            { label: 'Categories', href: categoriesIndex.url() },
            { label: c.name, href: show.url(c.slug) },
            { label: s.name },
        ]}
    />
    <div class="mb-10 grid overflow-hidden rounded-md border bg-secondary md:grid-cols-[1fr_220px]">
        <div class="p-7 lg:p-10">
            <p class="eyebrow mb-3">{c.name}</p>
            <h1 class="page-heading">{s.name}</h1>
            <p class="section-intro">
                {s.description?.trim() || `Explore ${s.name.toLowerCase()} selected around your clinical workflow.`}
            </p>
            {#if siblings.length > 0}
                <nav aria-label="More in {c.name}" class="mt-6 flex flex-wrap gap-2">
                    {#each siblings as sub (sub.id)}
                        <Link
                            href={subcategoryShow.url([c.slug, sub.slug])}
                            class="rounded-full border bg-background px-3 py-1 text-[11px] hover:border-primary hover:text-primary"
                        >
                            {sub.name}
                        </Link>
                    {/each}
                </nav>
            {/if}
        </div>
        {#if s.image}
            <img
                src={s.image}
                width={384}
                height={512}
                class="hidden h-64 w-full object-contain mix-blend-multiply md:block"
                alt={s.name}
            />
        {/if}
    </div>
    {#key `${c.slug}/${s.slug}`}
        <CatalogueView
            fixedCategory={c.slug}
            fixedSubcategory={s.slug}
            urlFor={(query) => subcategoryShow.url([c.slug, s.slug], { query })}
            {initial}
        />
    {/key}
</section>

<ContactBanner />
