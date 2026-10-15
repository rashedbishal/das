<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import { categories, countProducts, loadCategories } from '@/data/catalogue';
    import { catalogue } from '@/routes';

    loadCategories();

    let counts = $state<Record<string, number>>({});

    $effect(() => {
        for (const c of $categories) {
            if (!(c.slug in counts)) {
                countProducts({ category: c.slug }).then((total) => {
                    counts = { ...counts, [c.slug]: total };
                });
            }
        }
    });
</script>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-5">
    {#each $categories as c, i (c.id)}
        <Link
            href={catalogue.url({ query: { category: c.slug } })}
            class="category-tile {i === 0 || i === 6 ? 'bg-accent' : ''}"
        >
            <img
                src={c.image}
                alt={c.name}
                width={384}
                height={512}
                loading="lazy"
            />
            <h3>{c.name}</h3>
            <small>{counts[c.slug] ?? 0} products to explore</small>
            <ArrowUpRight class="cat-arrow size-4 text-primary" />
        </Link>
    {/each}
</div>
