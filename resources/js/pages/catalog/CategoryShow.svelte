<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ContactWidgets from '@/components/ContactWidgets.svelte';
    import { show as productShow } from '@/routes/products';

    type ProductCard = {
        id: number;
        name: string;
        slug: string;
        main_image: string | null;
        regular_price: string;
        sale_price: string | null;
        currency: string;
    };

    type Paginated<T> = {
        data: T[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };

    let {
        category,
        products,
    }: {
        category: { name: string; description: string | null };
        products: Paginated<ProductCard>;
    } = $props();

    const auth = $derived(page.props.auth);
</script>

<AppHead title={category.name} />

<div class="mx-auto max-w-6xl px-4 py-8">
    <header class="mb-8 flex items-center justify-between">
        <Link href="/" class="text-lg font-semibold">DAS Dental</Link>
        <nav class="flex items-center gap-4 text-sm">
            <Link href="/categories">Categories</Link>
            {#if auth.user}
                <Link href="/dashboard">Dashboard</Link>
            {/if}
        </nav>
    </header>

    <h1 class="mb-2 text-2xl font-semibold">{category.name}</h1>
    {#if category.description}
        <p class="mb-6 text-muted-foreground">{category.description}</p>
    {/if}

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        {#each products.data as product (product.id)}
            <Link
                href={productShow.url(product.slug)}
                class="rounded-lg border p-3 hover:shadow-md"
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
                <h3 class="font-medium">{product.name}</h3>
                <p class="mt-1 text-sm font-semibold">
                    {product.currency}
                    {product.sale_price ?? product.regular_price}
                </p>
            </Link>
        {:else}
            <p class="col-span-full text-muted-foreground">
                No products in this category yet.
            </p>
        {/each}
    </div>
</div>

<ContactWidgets />
