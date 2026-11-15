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
    <div class="mb-10 grid overflow-hidden rounded-md border bg-secondary md:grid-cols-[1fr_220px]">
        <div class="p-7 lg:p-10">
            <p class="eyebrow mb-3">Brand</p>
            <h1 class="page-heading">{b.name}</h1>
            <p class="section-intro">
                {b.description?.trim() || `Explore ${b.name} products available through DAS.`}
            </p>
            <p class="mt-5 text-[11px] text-muted-foreground">{b.productsCount} products</p>
            {#if brand.website_url}
                <a
                    href={brand.website_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-4 inline-flex items-center gap-1.5 text-[11px] text-primary"
                >
                    Official website
                    <ArrowUpRight class="size-3" />
                </a>
            {/if}
        </div>
        {#if b.logo}
            <div class="hidden items-center justify-center p-6 md:flex">
                <img src={b.logo} alt="{b.name} logo" class="max-h-40 w-full object-contain" />
            </div>
        {/if}
    </div>
    {#key b.slug}
        <CatalogueView fixedBrand={b.slug} urlFor={(query) => show.url(b.slug, { query })} {initial} />
    {/key}
</section>

<ContactBanner />
