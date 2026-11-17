<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import { categories, loadCategories } from '@/data/catalogue';
    import { show as categoryShow } from '@/routes/categories';

    let pending = $state(true);

    // Product counts come with the category list (one request, not one per category).
    loadCategories()
        .catch(() => {})
        .finally(() => (pending = false));

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
                        alt={c.name}
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
    {:else}
        <!-- Placeholders while the list loads. -->
        {#if pending}
            {#each [0, 1, 2, 3, 4] as n (n)}
                <div class="category-card is-skeleton" aria-hidden="true">
                    <div class="category-card-media sf-skel"></div>
                    <div class="category-card-body">
                        <div class="min-w-0 flex-1">
                            <div class="sf-skel h-3 w-3/4 rounded-sm"></div>
                            <div class="sf-skel mt-2 h-2 w-1/3 rounded-sm"></div>
                        </div>
                    </div>
                </div>
            {/each}
        {/if}
    {/each}
</div>
