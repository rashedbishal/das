<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import CatalogueView from '@/components/storefront/CatalogueView.svelte';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import MissingPage from '@/components/storefront/MissingPage.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { categories, loadCategories, pathSegment } from '@/data/catalogue';
    import { catalogue } from '@/routes';
    import { index as categoriesIndex, show } from '@/routes/categories';

    let loaded = $state(false);

    loadCategories().then(() => (loaded = true));

    const category = $derived(
        $categories.find((c) => c.slug === pathSegment(page.url, 1)),
    );
</script>

{#if category}
    {@const c = category}
    <PageMeta
        title={c.name}
        description="Explore DAS dental products and specifications in this category. Contact us for product guidance."
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
                <p class="section-intro">
                    {c.description}. Explore equipment selected around your
                    clinical workflow.
                </p>
                <div class="mt-7 flex flex-wrap gap-4 text-[10px] text-primary">
                    <Link href={catalogue.url({ query: { category: c.slug } })}>
                        View all in this category →
                    </Link>
                </div>
            </div>
            <img
                src={c.image}
                width={384}
                height={512}
                class="hidden h-64 w-full object-contain mix-blend-multiply md:block"
                alt={c.name}
            />
        </div>
        {#key c.slug}
            <CatalogueView
                fixedCategory={c.slug}
                urlFor={(query) => show.url(c.slug, { query })}
            />
        {/key}
    </section>

    <ContactBanner />
{:else}
    <MissingPage />
{/if}
