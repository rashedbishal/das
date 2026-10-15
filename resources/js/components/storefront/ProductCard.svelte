<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Phone from '@lucide/svelte/icons/phone';
    import { brands, business, categories } from '@/data/catalogue';
    import type { Product } from '@/data/catalogue';
    import { show } from '@/routes/products';

    let { product: p }: { product: Product } = $props();

    const brand = $derived(brands.find((b) => b.id === p.brand));
    const categoryName = $derived(
        categories.find((c) => c.id === p.category)?.name?.split(' & ')[0],
    );
    const href = $derived(show.url(p.slug));
</script>

<article class="product-card">
    <Link {href} class="product-photo" aria-label="View {p.name}">
        <img src={p.image} alt={p.name} loading="lazy" width={384} height={512} />
        {#if p.featured}
            <span
                class="absolute top-3 left-3 bg-background/95 px-2 py-1 text-[8px] font-semibold tracking-[1px] text-primary"
            >
                FEATURED
            </span>
        {/if}
    </Link>
    <div class="product-body">
        <div class="flex items-center gap-2 text-[9px] text-muted-foreground">
            <span class="font-semibold tracking-[1px] text-primary uppercase"
                >{brand?.name}</span
            >
            <span class="truncate">/ {categoryName}</span>
        </div>
        <Link {href}>
            <h3 class="product-title">{p.name}</h3>
        </Link>
        <p class="product-spec">{p.shortDescription}</p>
        <div
            class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t pt-4"
        >
            <div>
                <div class="product-price">{p.priceLabel}</div>
                {#if p.price}
                    <span class="text-[8px] text-muted-foreground"
                        >Illustrative price</span
                    >
                {/if}
            </div>
            <Link
                {href}
                class="detail-link flex items-center gap-1.5 text-[10px] font-semibold text-primary"
            >
                View Details
                <ArrowRight class="size-3.5" />
            </Link>
        </div>
        <a
            href="tel:{business.telephone}"
            class="mt-3 inline-flex items-center gap-1.5 text-[10px] text-muted-foreground hover:text-primary"
        >
            <Phone class="size-3" />
            Call for Price
        </a>
    </div>
</article>
