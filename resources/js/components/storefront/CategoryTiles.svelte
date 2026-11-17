<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import { categories, loadCategories, type Category } from '@/data/catalogue';
    import { show as categoryShow } from '@/routes/categories';

    /**
     * `items`: categories already on the page (e.g. server-rendered on the
     * categories page). Without it, the list is loaded in the browser.
     * `limit` caps how many show (in CMS sort order); `phoneLimit` shows fewer
     * on phones (below 640px, hidden with CSS so nothing depends on JS).
     */
    let {
        items,
        limit,
        phoneLimit,
    }: { items?: Category[]; limit?: number; phoneLimit?: number } = $props();

    // svelte-ignore state_referenced_locally
    let pending = $state(!items);

    // Product counts come with the category list (one request, not one per category).
    // svelte-ignore state_referenced_locally
    if (!items) {
        loadCategories()
            .catch(() => {})
            .finally(() => (pending = false));
    }

    const list = $derived((items ?? $categories).slice(0, limit));
</script>

<!-- Wrapping, centred row (not a grid) so a short last row sits in the middle. -->
<div class="category-cards">
    {#each list as c, i (c.id)}
        <Link
            href={categoryShow.url(c.slug)}
            class={phoneLimit !== undefined && i >= phoneLimit
                ? 'category-card is-beyond-phone-limit'
                : 'category-card'}
        >
            <div class="category-card-media" class:has-image={!!c.image}>
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
                        {c.productsCount}
                        {c.productsCount === 1 ? 'product' : 'products'}
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
