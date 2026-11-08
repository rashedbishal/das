<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import CirclePlay from '@lucide/svelte/icons/circle-play';
    import AppHead from '@/components/AppHead.svelte';
    import LoginModal from '@/components/LoginModal.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        show as productShow,
    } from '@/routes/catalog/products';

    type MediaItem = { type: 'image' | 'video'; url: string | null; is_main: boolean; alt_text: string | null };

    type ProductDetail = {
        name: string;
        sku: string;
        model_number: string | null;
        status: string;
        brand: { name: string; slug: string } | null;
        category: { name: string; slug: string } | null;
        subcategory: { name: string; slug: string } | null;
        tags: string[];
        short_description: string | null;
        full_description: string | null;
        highlights: string[] | null;
        specifications: { name: string; value: string }[];
        media: MediaItem[];
        regular_price: string;
        sale_price: string | null;
        currency: string;
        stock_quantity: number;
        stock_status: string;
        seo: { title: string | null; description: string | null };
        related_products: { id: number; name: string; slug: string; main_image: string | null }[];
    };

    let {
        product,
        authRequired = false,
        loginMode = 'both',
    }: {
        product: { data: ProductDetail } | null;
        authRequired?: boolean;
        loginMode?: 'email' | 'phone' | 'both';
    } = $props();
    const detail = $derived(product?.data);
    const gallery = $derived(detail?.media.filter((m) => m.url) ?? []);

    let activeIndex = $state(0);
    $effect(() => {
        const mainIdx = gallery.findIndex((m) => m.is_main && m.type === 'image');
        activeIndex = mainIdx >= 0 ? mainIdx : 0;
    });
    const activeMedia = $derived(gallery[activeIndex] ?? null);

    let loginModalOpen = $state(false);

    $effect(() => {
        if (authRequired) {
            loginModalOpen = true;
        }
    });
</script>

<AppHead title={detail?.name ?? 'Sign in to view this product'} />

<div class="mx-auto max-w-5xl px-4 py-8">

    {#if authRequired || !detail}
        <div class="flex flex-col items-center gap-4 rounded-lg border py-20 text-center">
            <h1 class="text-xl font-semibold">Sign in to view this product</h1>
            <p class="max-w-sm text-muted-foreground">
                Full product details, including pricing and specifications, are available to
                registered customers. Log in below to continue — no need to leave this page.
            </p>
            <Button onclick={() => (loginModalOpen = true)}>Log in</Button>
        </div>

        <LoginModal bind:open={loginModalOpen} {loginMode} />
    {:else}
    <div class="grid gap-8 md:grid-cols-2">
        <div class="flex flex-col gap-3">
            <div class="aspect-square overflow-hidden rounded-lg bg-muted">
                {#if activeMedia?.type === 'video' && activeMedia.url}
                    <video
                        src={activeMedia.url}
                        class="h-full w-full object-cover"
                        controls
                        playsinline
                    >
                        <track kind="captions" />
                    </video>
                {:else if activeMedia?.url}
                    <img
                        src={activeMedia.url}
                        alt={activeMedia.alt_text ?? detail.name}
                        class="h-full w-full object-cover"
                    />
                {/if}
            </div>

            {#if gallery.length > 1}
                <div class="flex gap-2 overflow-x-auto">
                    {#each gallery as item, i (i)}
                        <button
                            type="button"
                            onclick={() => (activeIndex = i)}
                            class={[
                                'relative aspect-square w-16 shrink-0 overflow-hidden rounded-md border-2 bg-muted',
                                i === activeIndex ? 'border-primary' : 'border-transparent',
                            ].join(' ')}
                            aria-label={item.type === 'video' ? 'Show video' : 'Show image'}
                        >
                            {#if item.type === 'video'}
                                <video src={item.url} class="h-full w-full object-cover" muted preload="metadata">
                                    <track kind="captions" />
                                </video>
                                <span class="absolute inset-0 flex items-center justify-center bg-black/20">
                                    <CirclePlay class="size-5 text-white" />
                                </span>
                            {:else if item.url}
                                <img src={item.url} alt="" class="h-full w-full object-cover" />
                            {/if}
                        </button>
                    {/each}
                </div>
            {/if}
        </div>

        <div>
            {#if detail.brand}
                <p class="text-sm text-muted-foreground">
                    {detail.brand.name}
                </p>
            {/if}
            <h1 class="text-2xl font-semibold">{detail.name}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                SKU: {detail.sku}
                {#if detail.model_number}
                    &middot; Model: {detail.model_number}
                {/if}
            </p>

            <div class="mt-4 flex items-center gap-2">
                <Badge variant={detail.stock_status === 'in_stock' ? 'default' : 'secondary'}>
                    {detail.stock_status.replace('_', ' ')}
                </Badge>
                {#if detail.category}
                    <Badge variant="outline">{detail.category.name}</Badge>
                {/if}
                {#if detail.subcategory}
                    <Badge variant="outline">{detail.subcategory.name}</Badge>
                {/if}
            </div>

            <p class="mt-4 text-2xl font-bold">
                {#if detail.sale_price}
                    {detail.currency} {detail.sale_price}
                    <span class="ml-2 text-base font-normal text-muted-foreground line-through">
                        {detail.currency} {detail.regular_price}
                    </span>
                {:else}
                    {detail.currency} {detail.regular_price}
                {/if}
            </p>

            {#if detail.short_description}
                <p class="mt-4 text-muted-foreground">{detail.short_description}</p>
            {/if}
            <p class="mt-4 text-sm text-muted-foreground">
                <span class="font-medium text-foreground">Delivery:</span>
                {page.props.siteSettings.deliveryNote}
            </p>

            {#if detail.highlights?.length}
                <ul class="mt-4 list-inside list-disc space-y-1 text-sm">
                    {#each detail.highlights as highlight}
                        <li>{highlight}</li>
                    {/each}
                </ul>
            {/if}
        </div>
    </div>

    {#if detail.full_description}
        <section class="mt-10">
            <h2 class="mb-2 text-lg font-semibold">Description</h2>
            <p class="whitespace-pre-line text-muted-foreground">{detail.full_description}</p>
        </section>
    {/if}

    {#if detail.specifications.length}
        <section class="mt-10">
            <h2 class="mb-2 text-lg font-semibold">Specifications</h2>
            <dl class="divide-y rounded-lg border">
                {#each detail.specifications as spec (spec.name)}
                    <div class="grid grid-cols-2 gap-2 px-4 py-2 text-sm">
                        <dt class="text-muted-foreground">{spec.name}</dt>
                        <dd>{spec.value}</dd>
                    </div>
                {/each}
            </dl>
        </section>
    {/if}

    {#if detail.related_products.length}
        <section class="mt-10">
            <h2 class="mb-4 text-lg font-semibold">Related products</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                {#each detail.related_products as related (related.id)}
                    <Link href={productShow.url(related.slug)} class="rounded-lg border p-3 hover:shadow-md">
                        <div class="mb-2 aspect-square overflow-hidden rounded bg-muted">
                            {#if related.main_image}
                                <img src={related.main_image} alt={related.name} class="h-full w-full object-cover" />
                            {/if}
                        </div>
                        <p class="text-sm">{related.name}</p>
                    </Link>
                {/each}
            </div>
        </section>
    {/if}
    {/if}
</div>
