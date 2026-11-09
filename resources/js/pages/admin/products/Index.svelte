<script module lang="ts">
    import { index } from '@/routes/admin/products';

    export const layout = {
        breadcrumbs: [{ title: 'Products', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, router } from '@inertiajs/svelte';
    import Pencil from '@lucide/svelte/icons/pencil';
    import { untrack } from 'svelte';
    import ImageIcon from '@lucide/svelte/icons/image';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import BulkActions from '@/components/dashboard/BulkActions.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button, buttonVariants } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Spinner } from '@/components/ui/spinner';
    import { bulk as bulkProducts, destroy, index as productsIndex, store, update } from '@/routes/admin/products';
    import FormFields from './FormFields.svelte';
    import MediaManager from './MediaManager.svelte';

    type Option = { id: number; name: string };
    type SubcategoryOption = { id: number; name: string; category_id: number };
    type MediaItem = {
        id: number;
        type: 'image' | 'video';
        source: 'upload' | 'url';
        is_main: boolean;
        alt_text: string | null;
        sort_order: number;
        url: string | null;
    };
    type MediaLimits = {
        image: {
            max: number;
            maxSizeKb: number;
            requiredDimension: number;
            formats: string[];
        };
        video: {
            max: number;
            maxSizeKb: number;
            maxSeconds: number;
            formats: string[];
        };
    };

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
        regular_price: string | null;
        sale_price: string | null;
        currency: string;
        stock_quantity: number;
        stock_status: string;
        seo_title: string | null;
        seo_description: string | null;
        category_id: number;
        subcategory_id: number | null;
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
        mediaLimits,
    }: {
        products: Paginated<ProductRow>;
        filters: Record<string, string | undefined>;
        brands: Option[];
        categories: Option[];
        subcategories: SubcategoryOption[];
        tags: Option[];
        mediaLimits: MediaLimits;
    } = $props();

    let search = $state(untrack(() => filters.search ?? ''));
    let createOpen = $state(false);

    let editingProductId = $state<number | null>(null);
    let editOpen = $state(false);
    const editTarget = $derived(products.data.find((p) => p.id === editingProductId) ?? null);

    let deleteTarget = $state<ProductRow | null>(null);
    let deleteOpen = $state(false);
    let selectedIds = $state<number[]>([]);
    let bulkDeleteOpen = $state(false);
    let bulkError = $state<string | null>(null);

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

    function runBulkAction(action: 'delete' | 'publish' | 'draft') {
        router.post(
            bulkProducts.url(),
            { ids: selectedIds, action },
            {
                onSuccess: () => {
                    selectedIds = [];
                    bulkError = null;
                },
                onError: (errors) => {
                    bulkError = Object.values(errors).join(' ');
                    bulkDeleteOpen = false;
                },
            },
        );
    }
</script>

<AppHead title="Products" />

<div class="w-full p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Products</h1>
        <Button onclick={() => (createOpen = true)}>New product</Button>
    </div>

    <form onsubmit={applySearch} class="mb-4 flex gap-2">
        <Input
            bind:value={search}
            placeholder="Search by name, SKU, model..."
            class="min-w-0 flex-1 sm:max-w-sm"
        />
        <Button type="submit">Search</Button>
    </form>

    <BulkActions
        count={selectedIds.length}
        error={bulkError}
        onClear={() => {
            selectedIds = [];
            bulkError = null;
        }}
    >
        {#snippet children()}
            <Button size="sm" onclick={() => runBulkAction('publish')}>Publish</Button>
            <Button size="sm" variant="outline" onclick={() => runBulkAction('draft')}>Move to draft</Button>
            <Button
                size="sm"
                variant="destructive"
                onclick={() => (bulkDeleteOpen = true)}
            >Delete</Button>
        {/snippet}
    </BulkActions>

    <ResponsiveTable
        items={products.data}
        key={(product) => product.id}
        columns={[
            { key: 'name', label: 'Name' },
            { key: 'sku', label: 'SKU' },
            { key: 'status', label: 'Status' },
            { key: 'price', label: 'Price', class: 'text-right' },
            { key: 'stock', label: 'Stock', class: 'text-right' },
        ]}
        empty="No products found."
        selectedKeys={selectedIds}
        onSelectionChange={(keys) => (selectedIds = keys.map(Number))}
    >
        {#snippet cell(product, column)}
            {#if column.key === 'name'}
                {@const image =
                    product.media.find((item) => item.type === 'image' && item.is_main) ??
                    product.media.find((item) => item.type === 'image')}
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted">
                        {#if image?.url}
                            <img src={image.url} alt={product.name} class="size-full object-cover" />
                        {:else}
                            <ImageIcon class="size-4 text-muted-foreground" />
                        {/if}
                    </div>
                    <span class="min-w-0 break-words">{product.name}</span>
                </div>
            {:else if column.key === 'sku'}
                <span class="font-mono text-xs">{product.sku}</span>
            {:else if column.key === 'status'}
                <Badge variant="outline" class="capitalize">{product.status}</Badge>
            {:else if column.key === 'price'}
                {product.regular_price ?? 'Contact for price'}
            {:else}
                {product.stock_quantity}
            {/if}
        {/snippet}
        {#snippet actions(product)}
            <Button variant="ghost" size="sm" aria-label="Edit {product.name}" onclick={() => askEditProduct(product)}>
                <Pencil class="size-4" />
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive"
                aria-label="Delete {product.name}"
                onclick={() => askDeleteProduct(product)}
            >
                <Trash2 class="size-4" />
            </Button>
        {/snippet}
    </ResponsiveTable>

    {#if products.last_page > 1}
        <div class="mt-4 flex flex-wrap items-center justify-center gap-4 text-sm">
            {#if products.prev_page_url}
                <Link href={products.prev_page_url} class={buttonVariants({ variant: 'ghost', size: 'sm' })}>Previous</Link>
            {/if}
            <span>Page {products.current_page} of {products.last_page}</span>
            {#if products.next_page_url}
                <Link href={products.next_page_url} class={buttonVariants({ variant: 'ghost', size: 'sm' })}>Next</Link>
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
                <MediaManager productId={product.id} media={product.media} limits={mediaLimits} />
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

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title="Delete selected products?"
    description={`This will permanently delete ${selectedIds.length} selected products. This cannot be undone.`}
    onConfirm={() => runBulkAction('delete')}
/>
