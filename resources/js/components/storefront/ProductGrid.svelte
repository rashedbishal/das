<script lang="ts">
    import type { ProductCard as ProductCardType } from '@/data/catalogue';
    import ProductCard from './ProductCard.svelte';

    let {
        items,
        class: className = '',
    }: { items: ProductCardType[]; class?: string } = $props();
</script>

<div
    class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:gap-6 xl:grid-cols-4 {className}"
>
    {#each items as p (p.id)}
        <ProductCard product={p} />
    {/each}
</div>
