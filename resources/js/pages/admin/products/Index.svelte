<script module lang="ts">
    import { index } from '@/routes/admin/products';

    export const layout = {
        breadcrumbs: [{ title: 'Products', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { create, destroy, edit, index as productsIndex } from '@/routes/admin/products';

    type ProductRow = {
        id: number;
        name: string;
        sku: string;
        status: string;
        regular_price: string;
        stock_quantity: number;
    };

    type Paginated<T> = {
        data: T[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };

    let {
        products,
        filters,
    }: {
        products: Paginated<ProductRow>;
        filters: Record<string, string | undefined>;
    } = $props();

    let search = $state(filters.search ?? '');

    function applySearch(event: SubmitEvent) {
        event.preventDefault();
        router.get(productsIndex.url({ query: { ...filters, search } }), {}, { preserveState: true, replace: true });
    }

    function removeProduct(productId: number) {
        if (confirm('Delete this product?')) {
            router.delete(destroy.url(productId));
        }
    }
</script>

<AppHead title="Products" />

<div class="p-4">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Products</h1>
        <Link
            href={create.url()}
            class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
        >
            New product
        </Link>
    </div>

    <form onsubmit={applySearch} class="mb-4 flex gap-2">
        <Input bind:value={search} placeholder="Search by name, SKU, model..." class="max-w-sm" />
        <Button type="submit">Search</Button>
    </form>

    <div class="overflow-x-auto rounded-lg border">
        <table class="w-full text-sm">
            <thead class="bg-muted/50 text-left">
                <tr>
                    <th class="p-2">Name</th>
                    <th class="p-2">SKU</th>
                    <th class="p-2">Status</th>
                    <th class="p-2">Price</th>
                    <th class="p-2">Stock</th>
                    <th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
                {#each products.data as product (product.id)}
                    <tr class="border-t">
                        <td class="p-2">{product.name}</td>
                        <td class="p-2">{product.sku}</td>
                        <td class="p-2"><Badge variant="outline">{product.status}</Badge></td>
                        <td class="p-2">{product.regular_price}</td>
                        <td class="p-2">{product.stock_quantity}</td>
                        <td class="p-2 text-right">
                            <Link href={edit.url(product.id)} class="mr-3 text-primary underline">Edit</Link>
                            <button class="text-red-600 underline" onclick={() => removeProduct(product.id)}>
                                Delete
                            </button>
                        </td>
                    </tr>
                {:else}
                    <tr><td class="p-4 text-muted-foreground" colspan="6">No products found.</td></tr>
                {/each}
            </tbody>
        </table>
    </div>

    {#if products.last_page > 1}
        <div class="mt-4 flex items-center justify-center gap-4 text-sm">
            {#if products.prev_page_url}
                <Link href={products.prev_page_url}>Previous</Link>
            {/if}
            <span>Page {products.current_page} of {products.last_page}</span>
            {#if products.next_page_url}
                <Link href={products.next_page_url}>Next</Link>
            {/if}
        </div>
    {/if}
</div>
