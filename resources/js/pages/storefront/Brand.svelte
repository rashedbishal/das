<script lang="ts">
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import CatalogueView from '@/components/storefront/CatalogueView.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { mapBrand, toProductPage, type RawBrand, type RawProductPage } from '@/data/catalogue';
    import { brands as brandsIndex } from '@/routes';
    import { show } from '@/routes/brands';

    // Resolved server-side; unknown or inactive brands get a real 404.
    let {
        brand,
        initialProducts = null,
    }: { brand: RawBrand & { website_url?: string | null }; initialProducts?: RawProductPage | null } = $props();

    const b = $derived(mapBrand(brand));
    const initial = $derived(initialProducts ? toProductPage(initialProducts) : null);
    const description = $derived(
        b.seo.description?.trim() ||
            b.description?.trim() ||
            `Explore ${b.name} dental equipment and supplies available from DAS in Bangladesh.`,
    );
</script>

<PageMeta
    title={b.seo.title?.trim() || `${b.name} Dental Products`}
    {description}
    image={b.logo}
    canonical={show.url(b.slug)}
/>

<section class="container-wide pt-10 pb-16">
    <Breadcrumb items={[{ label: 'Brands', href: brandsIndex.url() }, { label: b.name }]} />
    <header class="mb-8 max-w-3xl">
        <p class="sf-enter eyebrow mb-2">Brand</p>
        <h1 class="sf-enter page-heading" style:--sf-i={1}>{b.name}</h1>
        <p class="sf-enter section-intro" style:--sf-i={2}>
            {b.description?.trim() || `Explore ${b.name} products available through DAS.`}
            {#if brand.website_url}
                <a
                    href={brand.website_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="ml-1 inline-flex items-center gap-1 whitespace-nowrap text-primary"
                >
                    Official website
                    <ArrowUpRight class="size-3" />
                </a>
            {/if}
        </p>
    </header>
    {#key b.slug}
        <CatalogueView fixedBrand={b.slug} urlFor={(query) => show.url(b.slug, { query })} {initial} />
    {/key}
</section>

<ContactBanner />
