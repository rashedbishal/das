<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import { categories, loadCategories } from '@/data/catalogue';
    import { show as categoryShow } from '@/routes/categories';

    // Product counts come with the category list (one request, not one per category).
    loadCategories().catch(() => {});

    const counts = $derived(
        Object.fromEntries($categories.map((c) => [c.slug, c.productsCount])),
    );
</script>

<!-- Wrapping, centred row (not a grid) so a short last row sits in the middle. -->
<div class="category-cards">
    {#each $categories as c (c.id)}
        <Link
            href={categoryShow.url(c.slug)}
            class="category-card"
        >
            <div class="category-card-media">
                {#if c.image}
                    <img
                        src={c.image}
                        alt=""
                        width={384}
                        height={512}
                        loading="lazy"
                    />
                {:else}
                    <LayoutGrid class="size-8 text-primary/40" strokeWidth={1.3} />
                {/if}
            </div>
            <div class="category-card-body">
                <div class="min-w-0">
                    <h3>{c.name}</h3>
                    <small>
                        {#if c.slug in counts}
                            {counts[c.slug]}
                            {counts[c.slug] === 1 ? 'product' : 'products'}
                        {:else}
                            &nbsp;
                        {/if}
                    </small>
                </div>
                <span class="category-card-arrow" aria-hidden="true">
                    <ArrowUpRight class="size-3.5" />
                </span>
            </div>
        </Link>
    {/each}
</div>
