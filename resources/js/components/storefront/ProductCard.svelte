<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Phone from '@lucide/svelte/icons/phone';
    import { formatPrice } from '@/data/catalogue';
    import type { ProductCard as ProductCardType } from '@/data/catalogue';
    import { resolvePhone } from '@/lib/contact';
    import { markMorph, motion } from '@/lib/motion.svelte';
    import { contact } from '@/routes';
    import { show } from '@/routes/products';
    import ProductLink from './ProductLink.svelte';

    /**
     * The storefront's one product card. The whole card opens the product: the
     * title link is stretched over the card (storefront.css, "Whole-card product
     * links"), and "Call for Price" sits above it as its own link, so there is
     * never a link inside a link.
     */
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
    // ...and not when signing in would reveal the price: that card already says
    // "Sign in to see price", so it shows only one of the two.
    const showsPriceContact = $derived(!showsPrice && !p.priceLoginRequired);
    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
    const priceContactClass = $derived(
        `relative z-[2] inline-flex items-center gap-1.5 text-[10px] text-muted-foreground hover:text-primary ${
            p.showPrice ? 'mt-1.5 md:mt-3' : ''
        }`,
    );
</script>

<article class="product-card">
    <div
        class="product-photo"
        data-morph={motion.morphSlug === p.slug ? '' : undefined}
    >
        <!-- The title link names the card, so the photo itself is decorative. -->
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
                {#if phone}
                    <a href="tel:{phone.tel}" class={priceContactClass}>
                        <Phone class="size-3" />
                        Call for Price
                    </a>
                {:else}
                    <Link href={`${contact.url({ query: { quote: 1 } })}#enquiry`} class={priceContactClass}>
                        Ask for Price
                    </Link>
                {/if}
            {/if}
        </div>
    </div>
</article>
