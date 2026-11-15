<script lang="ts">
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import CatalogueView from '@/components/storefront/CatalogueView.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { toProductPage, type RawProductPage } from '@/data/catalogue';
    import { catalogue } from '@/routes';

    let { initialProducts = null }: { initialProducts?: RawProductPage | null } =
        $props();

    // svelte-ignore state_referenced_locally
    const initial = initialProducts ? toProductPage(initialProducts) : null;
</script>

<PageMeta
    title="Equipment Catalogue"
    description="Browse DAS dental products by category, brand and availability."
    canonical={catalogue.url()}
/>

<section class="container-wide pt-9 pb-16 lg:pt-12">
    <Breadcrumb items={[{ label: 'Equipment Catalogue' }]} />
    <p class="eyebrow mb-3">The DAS collection</p>
    <h1 class="page-heading">Equipment for every ambition.</h1>
    <p class="section-intro mb-10 max-w-xl text-sm">
        From the everyday essential to your next big investment. Find what’s
        right for your practice.
    </p>
    <CatalogueView urlFor={(query) => catalogue.url({ query })} {initial} />
</section>

<ContactBanner />
