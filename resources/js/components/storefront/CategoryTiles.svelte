<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import { categories, products } from '@/data/catalogue';
    import { catalogue } from '@/routes';
</script>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-5">
    {#each categories as c, i (c.id)}
        <Link
            href={catalogue.url({ query: { category: c.id } })}
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
            <small
                >{products.filter((p) => p.category === c.id).length} products to
                explore</small
            >
            <ArrowUpRight class="cat-arrow size-4 text-primary" />
        </Link>
    {/each}
</div>
