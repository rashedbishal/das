<script module lang="ts">
    import { index } from '@/routes/admin/products';

    export const layout = {
        breadcrumbs: [{ title: 'Products', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, router } from '@inertiajs/svelte';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Spinner } from '@/components/ui/spinner';
    import { destroy, index as productsIndex, store, update } from '@/routes/admin/products';
    import FormFields from './FormFields.svelte';
    import MediaManager from './MediaManager.svelte';

    type Option = { id: number; name: string };
    type SubcategoryOption = { id: number; name: string; category_id: number };
    type MediaItem = { id: number; type: string; is_main: boolean; alt_text: string | null; url?: string | null };

    type ProductRow = {
        id: number;
        name: string;
        slug: string;
        sku: string;
        model_number: string | null;
        brand_id: number | null;
        short_description: string | null;
        full_description: string | null;
        status: string;
        is_featured: boolean;
        regular_price: string;
        sale_price: string | null;
        currency: string;
        stock_quantity: number;
        stock_status: string;
        seo_title: string | null;
        seo_description: string | null;
        category_id: number;
        subcategory_id: number;
        tags: { id: number }[];
        media: MediaItem[];
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
        brands,
        categories,
        subcategories,
        tags,
    }: {
        products: Paginated<ProductRow>;
        filters: Record<string, string | undefined>;
        brands: Option[];
        categories: Option[];
        subcategories: SubcategoryOption[];
        tags: Option[];
    } = $props();

    let search = $state(filters.search ?? '');
    let createOpen = $state(false);

    let editingProductId = $state<number | null>(null);
    let editOpen = $state(false);
    const editTarget = $derived(products.data.find((p) => p.id === editingProductId) ?? null);

    let deleteTarget = $state<ProductRow | null>(null);
    let deleteOpen = $state(false);

    function applySearch(event: SubmitEvent) {
        event.preventDefault();
        router.get(productsIndex.url({ query: { ...filters, search } }), {}, { preserveState: true, replace: true });
    }

    function askEditProduct(product: ProductRow) {
        editingProductId = product.id;
        editOpen = true;
    }

    function askDeleteProduct(product: ProductRow) {
        deleteTarget = product;
        deleteOpen = true;
    }

    function confirmDeleteProduct() {
        if (deleteTarget) {
            router.delete(destroy.url(deleteTarget.id));
        }
    }
</script>

<AppHead title="Products" />

<div class="p-4">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Products</h1>
        <Button onclick={() => (createOpen = true)}>New product</Button>
    </div>

    <form onsubmit={applySearch} class="mb-4 flex gap-2">
        <Input bind:value={search} placeholder="Search by name, SKU, model..." class="max-w-sm" />
        <Button type="submit">Search</Button>
    </form>

    <div class="hidden overflow-x-auto rounded-lg border md:block">
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
                            <button
                                class="mr-3 text-muted-foreground hover:text-primary"
                                aria-label="Edit {product.name}"
                                onclick={() => askEditProduct(product)}
                            >
                                <Pencil class="size-4" />
                            </button>
                            <button
                                class="text-muted-foreground hover:text-red-600"
                                aria-label="Delete {product.name}"
                                onclick={() => askDeleteProduct(product)}
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </td>
                    </tr>
                {:else}
                    <tr><td class="p-4 text-muted-foreground" colspan="6">No products found.</td></tr>
                {/each}
            </tbody>
        </table>
    </div>

    <div class="grid gap-3 md:hidden">
        {#each products.data as product (product.id)}
            <div class="rounded-lg border p-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium">{product.name}</p>
                    <Badge variant="outline">{product.status}</Badge>
                </div>
                <dl class="mt-2 grid grid-cols-2 gap-1 text-sm text-muted-foreground">
                    <dt>SKU</dt>
                    <dd class="text-right text-foreground">{product.sku}</dd>
                    <dt>Price</dt>
                    <dd class="text-right text-foreground">{product.regular_price}</dd>
                    <dt>Stock</dt>
                    <dd class="text-right text-foreground">{product.stock_quantity}</dd>
                </dl>
                <div class="mt-3 flex justify-end gap-4">
                    <button
                        class="text-muted-foreground hover:text-primary"
                        aria-label="Edit {product.name}"
                        onclick={() => askEditProduct(product)}
                    >
                        <Pencil class="size-4" />
                    </button>
                    <button
                        class="text-muted-foreground hover:text-red-600"
                        aria-label="Delete {product.name}"
                        onclick={() => askDeleteProduct(product)}
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>
        {:else}
            <p class="p-4 text-center text-muted-foreground">No products found.</p>
        {/each}
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

<Dialog bind:open={createOpen}>
    <DialogContent class="max-h-[85vh] max-w-2xl overflow-y-auto">
        <DialogTitle>New product</DialogTitle>
        <Form {...store.form()} class="space-y-6" onSuccess={() => (createOpen = false)}>
            {#snippet children({ errors, processing })}
                <FormFields {brands} {categories} {subcategories} {tags} {errors} />

                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (createOpen = false)}>Cancel</Button>
                    <Button type="submit" disabled={processing}>
                        {#if processing}<Spinner />{/if}
                        Create product
                    </Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<Dialog bind:open={editOpen}>
    <DialogContent class="max-h-[85vh] max-w-2xl overflow-y-auto">
        <DialogTitle>Edit product</DialogTitle>
        {#if editTarget}
            {@const product = editTarget}
            <Form {...update.form(product.id)} class="space-y-6">
                {#snippet children({ errors, processing })}
                    <FormFields {product} {brands} {categories} {subcategories} {tags} {errors} />

                    <DialogFooter>
                        <Button type="button" variant="outline" onclick={() => (editOpen = false)}>Cancel</Button>
                        <Button type="submit" disabled={processing}>
                            {#if processing}<Spinner />{/if}
                            Save changes
                        </Button>
                    </DialogFooter>
                {/snippet}
            </Form>

            <div class="border-t pt-6">
                <MediaManager productId={product.id} media={product.media} />
            </div>
        {/if}
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={deleteOpen}
    title="Delete product?"
    description={deleteTarget ? `This will permanently delete "${deleteTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteProduct}
/>
