<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import CategoryTiles from '@/components/storefront/CategoryTiles.svelte';
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
    <CategoryTiles items={list} />
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
