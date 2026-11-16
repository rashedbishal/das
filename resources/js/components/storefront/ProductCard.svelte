<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Phone from '@lucide/svelte/icons/phone';
    import { formatPrice } from '@/data/catalogue';
    import type { ProductCard as ProductCardType } from '@/data/catalogue';
    import { resolvePhone } from '@/lib/contact';
    import { markMorph, motion } from '@/lib/motion.svelte';
    import { show } from '@/routes/products';
    import ProductLink from './ProductLink.svelte';

    let { product: p }: { product: ProductCardType } = $props();

    const href = $derived(show.url(p.slug));
    // Clicking through to the product morphs this photo into the product page
    // (guests get the login modal instead, so nothing to morph).
    const morph = () => {
        if (page.props.auth.user) {
            markMorph(p.slug);
        }
    };
    // Shown in the login modal when a guest clicks this card.
    const promptProduct = $derived({ name: p.name, image: p.image });
    const priceLabel = $derived(formatPrice(p));
    // "Call for Price" only makes sense when the card isn't already showing a price.
    const showsPrice = $derived(p.showPrice && (p.salePrice ?? p.regularPrice) !== null);
    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
</script>

<article class="product-card">
    <ProductLink
        {href}
        product={promptProduct}
        onclick={morph}
        class="product-photo"
        aria-label="View {p.name}"
        data-morph={motion.morphSlug === p.slug ? '' : undefined}
    >
        <img src={p.image} alt={p.name} loading="lazy" width={384} height={512} />
        {#if p.featured}
            <span
                class="absolute top-3 left-3 bg-background/95 px-2 py-1 text-[8px] font-semibold tracking-[1px] text-primary"
            >
                FEATURED
            </span>
        {/if}
    </ProductLink>
    <div class="product-body">
        <div class="flex items-center gap-2 text-[9px] text-muted-foreground">
            <span class="font-semibold tracking-[1px] text-primary uppercase"
                >{p.brand?.name}</span
            >
        </div>
        <ProductLink {href} product={promptProduct} onclick={morph}>
            <h3 class="product-title" title={p.name}>{p.name}</h3>
        </ProductLink>
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
            {:else if p.priceLoginRequired}
                <ProductLink {href} product={promptProduct} onclick={morph} class="text-[10px] font-medium text-muted-foreground hover:text-primary">
                    Sign in to see price
                </ProductLink>
            {/if}
            <ProductLink
                {href}
                product={promptProduct}
                onclick={morph}
                class="detail-link flex items-center gap-1.5 text-[10px] font-semibold text-primary"
            >
                View Details
                <ArrowRight class="size-3.5" />
            </ProductLink>
        </div>
        {#if !showsPrice}
            <a
                href="tel:{phone.tel}"
                class="mt-3 inline-flex items-center gap-1.5 text-[10px] text-muted-foreground hover:text-primary"
            >
                <Phone class="size-3" />
                Call for Price
            </a>
        {/if}
    </div>
</article>
