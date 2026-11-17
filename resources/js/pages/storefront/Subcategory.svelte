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
    <header class="mb-8 max-w-3xl">
        <p class="sf-enter eyebrow mb-2">{c.name}</p>
        <h1 class="sf-enter page-heading" style:--sf-i={1}>{s.name}</h1>
        <p class="sf-enter section-intro" style:--sf-i={2}>
            {s.description?.trim() || `Explore ${s.name.toLowerCase()} selected around your clinical workflow.`}
        </p>
    </header>
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
