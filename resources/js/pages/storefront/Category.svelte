<script lang="ts">
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
    import { index as categoriesIndex, show } from '@/routes/categories';

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
    <header class="mb-8 max-w-3xl">
        <p class="sf-enter eyebrow mb-2">A considered collection</p>
        <h1 class="sf-enter page-heading" style:--sf-i={1}>{c.name}</h1>
        <p class="sf-enter section-intro" style:--sf-i={2}>{intro}</p>
    </header>
    {#key c.slug}
        <CatalogueView
            fixedCategory={c.slug}
            urlFor={(query) => show.url(c.slug, { query })}
            {initial}
        />
    {/key}
</section>

<ContactBanner />
