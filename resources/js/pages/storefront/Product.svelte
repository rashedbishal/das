<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Check from '@lucide/svelte/icons/check';
    import Lock from '@lucide/svelte/icons/lock';
    import Maximize2 from '@lucide/svelte/icons/maximize-2';
    import Phone from '@lucide/svelte/icons/phone';
    import CirclePlay from '@lucide/svelte/icons/circle-play';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import Button from '@/components/storefront/Button.svelte';
    import { buttonClass } from '@/components/storefront/button';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import Modal from '@/components/storefront/Modal.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import ProductGrid from '@/components/storefront/ProductGrid.svelte';
    import QuoteDialog from '@/components/storefront/QuoteDialog.svelte';
    import SectionHeading from '@/components/storefront/SectionHeading.svelte';
    import {
        formatPrice,
        mapProductDetail,
        stockLabel,
        type RawProductDetail,
    } from '@/data/catalogue';
    import LoginModal from '@/components/LoginModal.svelte';
    import { resolvePhone } from '@/lib/contact';
    import { absoluteUrl, jsonLdScript } from '@/lib/seo';
    import { catalogue } from '@/routes';
    import { show as brandShow } from '@/routes/brands';
    import { show as categoryShow, subcategory as subcategoryShow } from '@/routes/categories';
    import { show as productShow } from '@/routes/products';

    // Rendered server-side (public, so search engines can index it). Prices are only
    // included for signed-in customers; guests see "Sign in to see price".
    let {
        product,
        isPreview = false,
        loginMode = 'both',
    }: {
        product: RawProductDetail;
        isPreview?: boolean;
        loginMode?: 'email' | 'phone' | 'both';
    } = $props();

    const p = $derived(mapProductDetail(product));
    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
    const authed = $derived(!!page.props.auth.user);

    let zoom = $state(false);
    let loginModalOpen = $state(false);
    let activeIndex = $state(0);

    const gallery = $derived.by(() => {
        const media = p.media.filter((m) => m.url);

        return media.length > 0
            ? media
            : p.image
              ? [{ type: 'image', url: p.image, isMain: true, altText: null }]
              : [];
    });
    const activeMedia = $derived(gallery[activeIndex] ?? null);
    const images = $derived(gallery.filter((m) => m.type === 'image').map((m) => m.url));

    $effect(() => {
        const main = gallery.findIndex((m) => m.isMain && m.type === 'image');
        activeIndex = main >= 0 ? main : 0;
    });

    const canonical = $derived(p.seo.canonicalUrl || productShow.url(p.slug));
    const metaDescription = $derived(
        p.seo.description?.trim() ||
            p.shortDescription?.trim() ||
            p.fullDescription?.trim().slice(0, 160) ||
            `${p.name}${p.brand ? ` by ${p.brand.name}` : ''}, available from DAS. Contact us for price and availability.`,
    );

    const breadcrumbs = $derived([
        { label: 'Categories', href: catalogue.url() },
        ...(p.category ? [{ label: p.category.name, href: categoryShow.url(p.category.slug) }] : []),
        ...(p.category && p.subcategory
            ? [{ label: p.subcategory.name, href: subcategoryShow.url([p.category.slug, p.subcategory.slug]) }]
            : []),
        { label: p.name },
    ]);

    /** schema.org Product, so search engines understand the page (no price for guests). */
    const productJsonLd = $derived.by(() => {
        const appUrl = page.props.seo.appUrl;
        const availability: Record<string, string> = {
            in_stock: 'https://schema.org/InStock',
            out_of_stock: 'https://schema.org/OutOfStock',
            backorder: 'https://schema.org/BackOrder',
            on_order: 'https://schema.org/PreOrder',
        };
        const price = p.salePrice ?? p.regularPrice;

        return jsonLdScript({
            '@context': 'https://schema.org',
            '@type': 'Product',
            name: p.name,
            description: metaDescription,
            url: absoluteUrl(appUrl, productShow.url(p.slug)),
            ...(images.length ? { image: images.map((url) => absoluteUrl(appUrl, url)) } : {}),
            ...(p.sku ? { sku: p.sku } : {}),
            ...(p.modelNumber ? { mpn: p.modelNumber, model: p.modelNumber } : {}),
            ...(p.brand ? { brand: { '@type': 'Brand', name: p.brand.name } } : {}),
            ...(p.category ? { category: p.subcategory ? `${p.category.name} > ${p.subcategory.name}` : p.category.name } : {}),
            ...(p.showPrice && price !== null
                ? {
                      offers: {
                          '@type': 'Offer',
                          price,
                          priceCurrency: p.currency || 'BDT',
                          url: absoluteUrl(appUrl, productShow.url(p.slug)),
                          ...(p.stockStatus && availability[p.stockStatus]
                              ? { availability: availability[p.stockStatus] }
                              : {}),
                      },
                  }
                : {}),
        });
    });
</script>

<svelte:head>
    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
    {@html productJsonLd}
</svelte:head>

<PageMeta
    title={p.seo.title?.trim() || p.name}
    description={metaDescription}
    image={images[0] ?? null}
    {canonical}
    noindex={isPreview}
    type="product"
/>

{#if isPreview}
    <div class="border-b bg-amber-50 px-4 py-2 text-center text-xs text-amber-900 dark:bg-amber-950 dark:text-amber-100">
        Preview: this product is not published, so customers and search engines can’t see it.
    </div>
{/if}

    <section class="container-wide pt-9 pb-16">
        <Breadcrumb items={breadcrumbs} />
        <div class="grid gap-9 lg:grid-cols-2 lg:gap-16">
            <div>
                <div
                    class="relative aspect-square overflow-hidden rounded-md bg-photo"
                >
                    {#if activeMedia?.type === 'video'}
                        <video
                            src={activeMedia.url}
                            class="h-full w-full object-contain"
                            controls
                            playsinline
                        >
                            <track kind="captions" />
                        </video>
                    {:else if activeMedia}
                        <img
                            src={activeMedia.url}
                            alt={activeMedia.altText ?? p.name}
                            width={600}
                            height={600}
                            class="h-full w-full object-contain mix-blend-multiply"
                        />
                        <Button
                            variant="outline"
                            size="icon"
                            class="absolute right-4 bottom-4"
                            aria-label="Enlarge product image"
                            title="Enlarge product image"
                            onclick={() => (zoom = true)}
                        >
                            <Maximize2 />
                        </Button>
                    {/if}
                </div>
                {#if gallery.length > 1}
                    <div class="mt-4 flex gap-3 overflow-x-auto">
                        {#each gallery as item, i (i)}
                            <button
                                type="button"
                                onclick={() => (activeIndex = i)}
                                class={[
                                    'relative h-16 w-16 shrink-0 overflow-hidden rounded-md border bg-photo',
                                    i === activeIndex
                                        ? 'border-primary'
                                        : 'border-transparent',
                                ]}
                                aria-label={item.type === 'video'
                                    ? 'Show video'
                                    : 'Show image'}
                            >
                                {#if item.type === 'video'}
                                    <video
                                        src={item.url}
                                        class="h-full w-full object-cover"
                                        muted
                                        preload="metadata"
                                    >
                                        <track kind="captions" />
                                    </video>
                                    <span
                                        class="absolute inset-0 flex items-center justify-center bg-foreground/20"
                                    >
                                        <CirclePlay
                                            class="size-5 text-background"
                                        />
                                    </span>
                                {:else}
                                    <img
                                        src={item.url}
                                        alt=""
                                        class="h-full w-full object-contain"
                                    />
                                {/if}
                            </button>
                        {/each}
                    </div>
                {/if}
            </div>
            <div class="lg:pt-4">
                {#if p.brand}
                    <Link href={brandShow.url(p.brand.slug)} class="eyebrow mb-4 block hover:underline">
                        {p.brand.name}
                    </Link>
                {/if}
                <h1 class="text-[31px] leading-tight font-semibold [overflow-wrap:anywhere] lg:text-[38px]">
                    {p.name}
                </h1>
                {#if p.category}
                    <Link
                        href={categoryShow.url(p.category.slug)}
                        class="mt-4 inline-flex items-center gap-2 text-xs text-primary"
                    >
                        {p.category.name}
                        <ArrowUpRight class="size-3" />
                    </Link>
                {/if}
                <div class="mt-5 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-muted-foreground [overflow-wrap:anywhere]">
                    {#if p.sku}
                        <span>SKU: {p.sku}</span>
                        <span>•</span>
                    {/if}
                    {#if p.modelNumber}
                        <span>Model: {p.modelNumber}</span>
                        <span>•</span>
                    {/if}
                    {#if p.showStock && p.stockStatus}
                        <span>{stockLabel(p.stockStatus)}</span>
                    {/if}
                    {#if p.showStock && p.stockQuantity !== null}
                        <span>Stock: {p.stockQuantity}</span>
                    {/if}
                </div>
                {#if p.shortDescription}
                    <p
                        class="mt-3 border-y py-6 text-sm leading-7 text-muted-foreground"
                    >
                        {p.shortDescription}
                    </p>
                {/if}
                <p class="mt-4 text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">Delivery:</span>
                    {page.props.siteSettings.deliveryNote}
                </p>
                {#if p.showPrice}
                    <div class="mt-6">
                    <div class="text-[27px] font-semibold">
                        {p.regularPrice !== null || p.salePrice !== null
                            ? formatPrice(p)
                            : 'Contact for Price'}
                        {#if p.salePrice !== null && p.regularPrice !== null}
                            <span
                                class="ml-2 text-base font-normal text-muted-foreground line-through"
                            >
                                {formatPrice({ ...p, salePrice: null })}
                            </span>
                        {/if}
                    </div>
                    {#if p.regularPrice === null && p.salePrice === null}
                        <span class="text-[10px] text-muted-foreground">
                            Let’s discuss the configuration that suits your
                            practice.
                        </span>
                    {/if}
                    </div>
                {:else if p.priceLoginRequired}
                    <div class="mt-6 flex flex-wrap items-center gap-3 rounded-md border border-dashed p-4">
                        <Lock class="size-4 text-primary" />
                        <span class="text-sm">Prices are available to registered customers.</span>
                        <Button variant="outline" size="sm" onclick={() => (loginModalOpen = true)}>
                            Sign in to see price
                        </Button>
                    </div>
                {/if}
                <ul class="my-7 space-y-3">
                    {#each p.highlights as f (f)}
                        <li class="flex items-center gap-3 text-xs">
                            <Check class="size-4 text-primary" />
                            {f}
                        </li>
                    {/each}
                </ul>
                {#if authed}
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="tel:{phone.tel}"
                            class={buttonClass({ class: 'h-12 px-7 shadow-none' })}
                        >
                            <Phone />
                            Call for Price
                        </a>
                        {#key p.slug}
                            <QuoteDialog product={p.name} />
                        {/key}
                    </div>
                {:else}
                    <div class="flex flex-wrap gap-3">
                        <Button class="h-12 px-7" onclick={() => (loginModalOpen = true)}>
                            Sign in to enquire
                        </Button>
                    </div>
                {/if}
            </div>
        </div>
        <div
            class="mt-14 grid gap-10 border-t pt-12 lg:grid-cols-[1fr_1fr] lg:gap-20"
        >
            <div>
                <p class="eyebrow mb-3">Considered in every detail</p>
                <h2 class="text-2xl">Product overview</h2>
                <p class="section-intro text-sm leading-8">{p.fullDescription}</p>
                <h3 class="mt-8 mb-5 text-lg">Key features</h3>
                {#each p.highlights as f (f)}
                    <div class="flex gap-3 border-b py-3 text-sm">
                        <Check class="size-4 text-primary" />
                        {f}
                    </div>
                {/each}
                {#if p.variants.length}
                    <h3 class="mt-8 mb-4 text-lg">Available options</h3>
                    <ul class="flex flex-wrap gap-2">
                        {#each p.variants as variant (variant.name)}
                            <li class="rounded-md border px-3 py-1.5 text-sm">{variant.name}</li>
                        {/each}
                    </ul>
                {/if}
            </div>
            <div>
                <h2 class="mb-5 text-2xl">Technical specifications</h2>
                <dl>
                    {#each p.specifications as spec (spec.name)}
                        <div class="spec-row">
                            <dt class="text-muted-foreground">{spec.name}</dt>
                            <dd>{spec.value}</dd>
                        </div>
                    {/each}
                </dl>
            </div>
        </div>
    </section>

    {#if p.relatedProducts.length > 0}
    <section class="border-t bg-secondary">
        <div class="container-wide section-space">
            <SectionHeading
                eyebrow="Complete your workflow"
                title="Explore Related Equipment"
                href={catalogue.url()}
            />
            <ProductGrid items={p.relatedProducts} />
        </div>
    </section>
    {/if}

    <ContactBanner />

    <Modal
        bind:open={zoom}
        title={p.name}
        class="max-h-[95dvh] max-w-3xl overflow-y-auto"
    >
        <img
            src={activeMedia?.url ?? p.image}
            alt={activeMedia?.altText ?? p.name}
            class="h-[65dvh] w-full bg-photo object-contain"
        />
    </Modal>

<LoginModal bind:open={loginModalOpen} {loginMode} />
