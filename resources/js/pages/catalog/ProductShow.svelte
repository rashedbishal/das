<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ContactWidgets from '@/components/ContactWidgets.svelte';
    import { Badge } from '@/components/ui/badge';
    import {
        index as productsIndex,
        show as productShow,
    } from '@/routes/catalog/products';

    type ProductDetail = {
        name: string;
        sku: string;
        model_number: string | null;
        status: string;
        brand: { name: string; slug: string } | null;
        categories: { name: string; slug: string }[];
        tags: string[];
        short_description: string | null;
        full_description: string | null;
        highlights: string[] | null;
        specifications: { name: string; value: string }[];
        media: { type: string; url: string | null; is_main: boolean; alt_text: string | null }[];
        regular_price: string;
        sale_price: string | null;
        currency: string;
        stock_quantity: number;
        stock_status: string;
        seo: { title: string | null; description: string | null };
        related_products: { id: number; name: string; slug: string; main_image: string | null }[];
    };

    let { product }: { product: { data: ProductDetail } } = $props();
    const detail = $derived(product.data);
    const mainImage = $derived(
        detail.media.find((m) => m.is_main && m.type === 'image') ??
            detail.media.find((m) => m.type === 'image'),
    );

    const auth = $derived(page.props.auth);
</script>

<AppHead title={detail.name} />

<div class="mx-auto max-w-5xl px-4 py-8">
    <header class="mb-8 flex items-center justify-between">
        <Link href="/" class="text-lg font-semibold">DAS Dental</Link>
        <nav class="flex items-center gap-4 text-sm">
            <Link href={productsIndex.url()}>Products</Link>
            {#if auth.user}
                <Link href="/dashboard">Dashboard</Link>
            {/if}
        </nav>
    </header>

    <div class="grid gap-8 md:grid-cols-2">
        <div class="aspect-square overflow-hidden rounded-lg bg-muted">
            {#if mainImage?.url}
                <img
                    src={mainImage.url}
                    alt={mainImage.alt_text ?? detail.name}
                    class="h-full w-full object-cover"
                />
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
                {#each detail.categories as category (category.slug)}
                    <Badge variant="outline">{category.name}</Badge>
                {/each}
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
</div>

<ContactWidgets />
