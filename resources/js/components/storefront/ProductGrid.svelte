<script lang="ts">
    import type { ProductCard as ProductCardType } from '@/data/catalogue';
    import ProductCard from './ProductCard.svelte';

    let {
        items,
        class: className = '',
        linkWholeCard = false,
    }: {
        items: ProductCardType[];
        class?: string;
        /** Whole-card links with a price strip (see ProductCard). */
        linkWholeCard?: boolean;
    } = $props();
</script>

<div
    class="sf-stagger grid grid-cols-2 gap-4 md:grid-cols-3 lg:gap-6 xl:grid-cols-4 {className}"
>
    {#each items as p (p.id)}
        <ProductCard product={p} {linkWholeCard} />
    {/each}
</div>
