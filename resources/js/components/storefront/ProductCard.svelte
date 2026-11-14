<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Phone from '@lucide/svelte/icons/phone';
    import { formatPrice } from '@/data/catalogue';
    import type { ProductCard as ProductCardType } from '@/data/catalogue';
    import { resolvePhone } from '@/lib/contact';
    import { show } from '@/routes/products';

    let { product: p }: { product: ProductCardType } = $props();

    const href = $derived(show.url(p.slug));
    const priceLabel = $derived(formatPrice(p));
    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
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
                >{p.brand?.name}</span
            >
        </div>
        <Link {href}>
            <h3 class="product-title" title={p.name}>{p.name}</h3>
        </Link>
        <p class="product-spec">{p.shortDescription}</p>
        {#if p.showStock && p.stockQuantity !== null}
            <p class="mt-2 text-[10px] text-muted-foreground">
                Stock: {p.stockQuantity}
            </p>
        {/if}
        <div
            class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t pt-4"
        >
            {#if p.showPrice}
                <div>
                    <div class="product-price">{priceLabel}</div>
                </div>
            {/if}
            <Link
                {href}
                class="detail-link flex items-center gap-1.5 text-[10px] font-semibold text-primary"
            >
                View Details
                <ArrowRight class="size-3.5" />
            </Link>
        </div>
        <a
            href="tel:{phone.tel}"
            class="mt-3 inline-flex items-center gap-1.5 text-[10px] text-muted-foreground hover:text-primary"
        >
            <Phone class="size-3" />
            Call for Price
        </a>
    </div>
</article>
