<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ContactWidgets from '@/components/ContactWidgets.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { login } from '@/routes';
    import { index as productsIndex, show as productShow } from '@/routes/products';

    type ProductCard = {
        id: number;
        name: string;
        slug: string;
        short_description: string | null;
        brand: { name: string; slug: string } | null;
        main_image: string | null;
        regular_price: string;
        sale_price: string | null;
        currency: string;
        stock_status: string;
        is_featured: boolean;
    };

    type Paginated<T> = {
        data: T[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };

    let {
        products,
        filters,
    }: {
        products: Paginated<ProductCard>;
        filters: Record<string, string | undefined>;
    } = $props();

    let search = $state(filters.search ?? '');

    function applySearch(event: SubmitEvent) {
        event.preventDefault();
        router.get(
            productsIndex.url({ query: { ...filters, search } }),
            {},
            { preserveState: true, replace: true },
        );
    }

    const auth = $derived(page.props.auth);
</script>

<AppHead title="Products" />

<div class="mx-auto max-w-6xl px-4 py-8">
    <header class="mb-8 flex items-center justify-between">
        <Link href="/" class="text-lg font-semibold">DAS Dental</Link>
        <nav class="flex items-center gap-4 text-sm">
            <Link href="/categories">Categories</Link>
            {#if auth.user}
                <Link href="/dashboard">Dashboard</Link>
            {:else}
                <Link href={login()}>Log in</Link>
            {/if}
        </nav>
    </header>

    <form onsubmit={applySearch} class="mb-6 flex gap-2">
        <Input
            bind:value={search}
            placeholder="Search products..."
            class="max-w-sm"
        />
        <Button type="submit">Search</Button>
    </form>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        {#each products.data as product (product.id)}
            <Link
                href={productShow.url(product.slug)}
                class="group rounded-lg border p-3 transition-shadow hover:shadow-md"
            >
                <div
                    class="mb-3 aspect-square overflow-hidden rounded bg-muted"
                >
                    {#if product.main_image}
                        <img
                            src={product.main_image}
                            alt={product.name}
                            class="h-full w-full object-cover"
                        />
                    {/if}
                </div>
                <p class="text-xs text-muted-foreground">
                    {product.brand?.name ?? ''}
                </p>
                <h3 class="font-medium group-hover:underline">
                    {product.name}
                </h3>
                <p class="mt-1 text-sm">
                    {#if product.sale_price}
                        <span class="font-semibold"
                            >{product.currency} {product.sale_price}</span
                        >
                        <span class="ml-1 text-muted-foreground line-through"
                            >{product.currency} {product.regular_price}</span
                        >
                    {:else}
                        <span class="font-semibold"
                            >{product.currency} {product.regular_price}</span
                        >
                    {/if}
                </p>
            </Link>
        {:else}
            <p class="col-span-full text-muted-foreground">
                No products found.
            </p>
        {/each}
    </div>

    {#if products.last_page > 1}
        <div class="mt-8 flex items-center justify-center gap-4">
            {#if products.prev_page_url}
                <Link href={products.prev_page_url} preserveScroll
                    >Previous</Link
                >
            {/if}
            <span class="text-sm text-muted-foreground">
                Page {products.current_page} of {products.last_page}
            </span>
            {#if products.next_page_url}
                <Link href={products.next_page_url} preserveScroll>Next</Link>
            {/if}
        </div>
    {/if}
</div>

<ContactWidgets />
