<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Phone from '@lucide/svelte/icons/phone';
    import { formatPrice } from '@/data/catalogue';
    import type { ProductCard as ProductCardType } from '@/data/catalogue';
    import { resolvePhone } from '@/lib/contact';
    import { markMorph, motion } from '@/lib/motion.svelte';
    import { contact } from '@/routes';
    import { show } from '@/routes/products';
    import ProductLink from './ProductLink.svelte';

    /**
     * `linkWholeCard`: the whole card opens the product (its title link is
     * stretched over the card) and there's no "View Details" link. Used by the
     * home page's featured list; other grids keep the standard card.
     */
    let {
        product: p,
        linkWholeCard = false,
    }: { product: ProductCardType; linkWholeCard?: boolean } = $props();

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
    // ...and not when signing in would reveal the price: that card already says
    // "Sign in to see price", so it shows only one of the two.
    const showsPriceContact = $derived(!showsPrice && !p.priceLoginRequired);
    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
</script>

{#snippet priceContact(className: string)}
    {#if phone}
        <a href="tel:{phone.tel}" class={className}>
            <Phone class="size-3" />
            Call for Price
        </a>
    {:else}
        <Link href={`${contact.url({ query: { quote: 1 } })}#enquiry`} class={className}>
            Ask for Price
        </Link>
    {/if}
{/snippet}

{#if linkWholeCard}
    <article class="product-card is-whole-link">
        <div
            class="product-photo"
            data-morph={motion.morphSlug === p.slug ? '' : undefined}
        >
            <img src={p.image} alt="" loading="lazy" width={384} height={512} />
            {#if p.featured}
                <span
                    class="absolute top-3 left-3 bg-background/95 px-2 py-1 text-[8px] font-semibold tracking-[1px] text-primary"
                >
                    FEATURED
                </span>
            {/if}
        </div>
        <div class="product-body">
            <div class="flex items-center gap-2 text-[9px] text-muted-foreground">
                <span class="font-semibold tracking-[1px] text-primary uppercase"
                    >{p.brand?.name}</span
                >
            </div>
            <ProductLink {href} product={promptProduct} onclick={morph} class="stretched-link">
                <h3 class="product-title" title={p.name}>{p.name}</h3>
            </ProductLink>
            <p class="product-spec">{p.shortDescription}</p>
            {#if p.showStock && p.stockQuantity !== null}
                <p class="mt-1.5 text-[10px] text-muted-foreground md:mt-2">
                    Stock: {p.stockQuantity}
                </p>
            {/if}
            <div class="mt-auto border-t pt-2 md:pt-4">
                {#if p.showPrice}
                    <div class="product-price">{priceLabel}</div>
                {:else if p.priceLoginRequired}
                    <span class="text-[10px] font-medium text-muted-foreground">
                        Sign in to see price
                    </span>
                {/if}
                {#if showsPriceContact}
                    <!-- Above the stretched card link, so it still calls. -->
                    {@render priceContact(
                        `relative z-[2] inline-flex items-center gap-1.5 text-[10px] text-muted-foreground hover:text-primary ${
                            p.showPrice ? 'mt-1.5 md:mt-3' : ''
                        }`,
                    )}
                {/if}
            </div>
        </div>
    </article>
{:else}
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
            <p class="mt-1.5 text-[10px] text-muted-foreground md:mt-2">
                Stock: {p.stockQuantity}
            </p>
        {/if}
        <div
            class="mt-auto flex flex-wrap items-center justify-between gap-x-2 gap-y-1 border-t pt-2 md:gap-y-2 md:pt-4"
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
        {#if showsPriceContact}
            {@render priceContact(
                'mt-1.5 inline-flex items-center gap-1.5 text-[10px] text-muted-foreground hover:text-primary md:mt-3',
            )}
        {/if}
    </div>
</article>
{/if}
