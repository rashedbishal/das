<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Check from '@lucide/svelte/icons/check';
    import Maximize2 from '@lucide/svelte/icons/maximize-2';
    import Phone from '@lucide/svelte/icons/phone';
    import CirclePlay from '@lucide/svelte/icons/circle-play';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import Button from '@/components/storefront/Button.svelte';
    import { buttonClass } from '@/components/storefront/button';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import MissingPage from '@/components/storefront/MissingPage.svelte';
    import Modal from '@/components/storefront/Modal.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import ProductGrid from '@/components/storefront/ProductGrid.svelte';
    import QuoteDialog from '@/components/storefront/QuoteDialog.svelte';
    import SectionHeading from '@/components/storefront/SectionHeading.svelte';
    import {
        fetchProduct,
        formatPrice,
        pathSegment,
        stockLabel,
        type ProductDetail,
    } from '@/data/catalogue';
    import LoginModal from '@/components/LoginModal.svelte';
    import { resolvePhone } from '@/lib/contact';
    import { catalogue } from '@/routes';
    import { show as categoryShow } from '@/routes/categories';

    let { loginMode = 'both' }: { loginMode?: 'email' | 'phone' | 'both' } = $props();

    const slug = $derived(pathSegment(page.url, 1));
    const phone = $derived(resolvePhone(page.props.contactWidgets.whatsapp));
    const authed = $derived(!!page.props.auth.user);

    let product = $state<ProductDetail | null>(null);
    let loading = $state(true);
    let zoom = $state(false);
    let loginModalOpen = $state(false);
    let activeIndex = $state(0);

    const gallery = $derived.by(() => {
        if (!product) {
            return [];
        }

        const media = product.media.filter((m) => m.url);

        return media.length > 0
            ? media
            : product.image
              ? [
                    {
                        type: 'image',
                        url: product.image,
                        isMain: true,
                        altText: null,
                    },
                ]
              : [];
    });
    const activeMedia = $derived(gallery[activeIndex] ?? null);

    $effect(() => {
        const main = gallery.findIndex(
            (m) => m.isMain && m.type === 'image',
        );
        activeIndex = main >= 0 ? main : 0;
    });

    $effect(() => {
        if (!authed) {
            loginModalOpen = true;
            loading = false;
            product = null;

            return;
        }

        loginModalOpen = false;
        loading = true;
        product = null;

        fetchProduct(slug)
            .then((result) => {
                product = result;
            })
            .catch(() => {
                product = null;
            })
            .finally(() => {
                loading = false;
            });
    });
</script>

{#if !authed}
    <section class="container-wide flex flex-col items-center gap-4 py-20 text-center">
        <h1 class="text-xl font-semibold">Sign in to view this product</h1>
        <p class="max-w-sm text-sm text-muted-foreground">
            Full product details, including pricing and specifications, are available to
            registered customers. Log in below to continue — no need to leave this page.
        </p>
        <Button onclick={() => (loginModalOpen = true)}>Log in</Button>
    </section>

    <LoginModal bind:open={loginModalOpen} {loginMode} />
{:else if product}
    {@const p = product}
    <PageMeta title={p.name} description={p.shortDescription ?? p.name} />

    <section class="container-wide pt-9 pb-16">
        <Breadcrumb
            items={[
                { label: 'Equipment', href: catalogue.url() },
                { label: p.category?.name ?? 'Equipment' },
                { label: p.name },
            ]}
        />
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
                                    'relative h-16 w-16 shrink-0 overflow-hidden rounded border bg-photo',
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
                <div class="eyebrow mb-4">
                    {p.brand?.name}
                </div>
                <h1 class="text-[31px] leading-tight font-semibold lg:text-[38px]">
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
                <div class="mt-5 flex gap-4 text-[10px] text-muted-foreground">
                    {#if p.sku}
                        <span>SKU: {p.sku}</span>
                        <span>•</span>
                    {/if}
                    {#if p.modelNumber}
                        <span>Model: {p.modelNumber}</span>
                        <span>•</span>
                    {/if}
                    <span>{stockLabel(p.stockStatus)}</span>
                </div>
                {#if p.shortDescription}
                    <p
                        class="mt-3 border-y py-6 text-sm leading-7 text-muted-foreground"
                    >
                        {p.shortDescription}
                    </p>
                {/if}
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
                <ul class="my-7 space-y-3">
                    {#each p.highlights as f (f)}
                        <li class="flex items-center gap-3 text-xs">
                            <Check class="size-4 text-primary" />
                            {f}
                        </li>
                    {/each}
                </ul>
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
{:else if !loading}
    <MissingPage />
{/if}
