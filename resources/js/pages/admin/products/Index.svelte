<script module lang="ts">
    import { index } from '@/routes/admin/products';

    export const layout = {
        breadcrumbs: [{ title: 'Products', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, router } from '@inertiajs/svelte';
    import { can } from '@/lib/permissions.svelte';
    import Pencil from '@lucide/svelte/icons/pencil';
    import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
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
    import {
        bulk as bulkProducts,
        destroy,
        forceDestroy,
        index as productsIndex,
        restore,
        store,
        update,
    } from '@/routes/admin/products';
    import FormFields from './FormFields.svelte';
    import FormErrorSummary from '@/components/dashboard/FormErrorSummary.svelte';
    import CreateImagePicker from './CreateImagePicker.svelte';
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
            targetSizeKb: number;
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
        show_price: boolean;
        show_stock: boolean;
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
    let savingEdit = $state(false);
    const editTarget = $derived(products.data.find((p) => p.id === editingProductId) ?? null);

    let deleteTarget = $state<ProductRow | null>(null);
    let deleteOpen = $state(false);
    let forceDeleteTarget = $state<ProductRow | null>(null);
    let forceDeleteOpen = $state(false);
    const viewingTrash = $derived(Boolean(filters.trashed));
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

    function restoreProduct(product: ProductRow) {
        router.post(restore.url(product.id));
    }

    function askForceDeleteProduct(product: ProductRow) {
        forceDeleteTarget = product;
        forceDeleteOpen = true;
    }

    function confirmForceDeleteProduct() {
        if (forceDeleteTarget) {
            router.delete(forceDestroy.url(forceDeleteTarget.id));
        }
    }

    function runBulkAction(
        action: 'delete' | 'publish' | 'draft' | 'show_price' | 'hide_price' | 'show_stock' | 'hide_stock',
    ) {
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

    const canCreate = $derived(can('products.create'));
    const canUpdate = $derived(can('products.update'));
    const canDelete = $derived(can('products.delete'));
    const canPublish = $derived(can('products.publish'));
    const canBulk = $derived(!viewingTrash && (canUpdate || canDelete || canPublish));
    const hasRowActions = $derived(viewingTrash ? canDelete : canUpdate || canDelete);
</script>

<AppHead title="Products" />

{#snippet rowActions(product: ProductRow)}
    {#if viewingTrash}
        <Button variant="ghost" size="sm" aria-label="Restore {product.name}" onclick={() => restoreProduct(product)}>
            <RotateCcw class="size-4" />
        </Button>
        <Button
            variant="ghost"
            size="sm"
            class="text-destructive hover:text-destructive"
            aria-label="Delete {product.name} permanently"
            onclick={() => askForceDeleteProduct(product)}
        >
            <Trash2 class="size-4" />
        </Button>
    {:else}
        {#if canUpdate}
            <Button variant="ghost" size="sm" aria-label="Edit {product.name}" onclick={() => askEditProduct(product)}>
                <Pencil class="size-4" />
            </Button>
        {/if}
        {#if canDelete}
            <Button
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive"
                aria-label="Delete {product.name}"
                onclick={() => askDeleteProduct(product)}
            >
                <Trash2 class="size-4" />
            </Button>
        {/if}
    {/if}
{/snippet}

<div class="w-full p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">{viewingTrash ? 'Trash' : 'Products'}</h1>
        <div class="flex gap-2">
            {#if viewingTrash}
                <Link href={productsIndex.url()} class={buttonVariants({ variant: 'outline' })}>Back to products</Link>
            {:else}
                <Link href={productsIndex.url({ query: { trashed: 1 } })} class={buttonVariants({ variant: 'outline' })}>
                    <Trash2 class="size-4" />
                    Trash
                </Link>
                {#if canCreate}
                    <Button onclick={() => (createOpen = true)}>New product</Button>
                {/if}
            {/if}
        </div>
    </div>

    <form onsubmit={applySearch} class="mb-4 flex gap-2">
        <Input
            bind:value={search}
            placeholder="Search by name, SKU, model..."
            class="min-w-0 flex-1 sm:max-w-sm"
        />
        <Button type="submit">Search</Button>
    </form>

    {#if viewingTrash}
        <p class="mb-4 text-sm text-muted-foreground">
            Deleted products stay here until you restore them or delete them permanently. Permanent deletion also
            removes their uploaded images and frees their SKU.
        </p>
    {/if}

    {#if canBulk}
        <BulkActions
            count={viewingTrash ? 0 : selectedIds.length}
            error={bulkError}
            onClear={() => {
                selectedIds = [];
                bulkError = null;
            }}
        >
            {#snippet children()}
                {#if canPublish}
                    <Button size="sm" onclick={() => runBulkAction('publish')}>Publish</Button>
                    <Button size="sm" variant="outline" onclick={() => runBulkAction('draft')}>Move to draft</Button>
                {/if}
                {#if canUpdate}
                    <Button size="sm" variant="outline" onclick={() => runBulkAction('show_price')}>Show price</Button>
                    <Button size="sm" variant="outline" onclick={() => runBulkAction('hide_price')}>Hide price</Button>
                    <Button size="sm" variant="outline" onclick={() => runBulkAction('show_stock')}>Show stock</Button>
                    <Button size="sm" variant="outline" onclick={() => runBulkAction('hide_stock')}>Hide stock</Button>
                {/if}
                {#if canDelete}
                    <Button size="sm" variant="destructive" onclick={() => (bulkDeleteOpen = true)}>Delete</Button>
                {/if}
            {/snippet}
        </BulkActions>
    {/if}

    <ResponsiveTable
        items={products.data}
        key={(product) => product.id}
        columns={[
            { key: 'name', label: 'Name' },
            { key: 'sku', label: 'SKU' },
            { key: 'status', label: 'Status' },
            { key: 'price', label: 'Regular price', class: 'text-right' },
            { key: 'sale_price', label: 'Selling price', class: 'text-right' },
            { key: 'stock', label: 'Stock', class: 'text-right' },
        ]}
        empty="No products found."
        selectedKeys={canBulk ? selectedIds : undefined}
        onSelectionChange={canBulk ? (keys) => (selectedIds = keys.map(Number)) : undefined}
        actions={hasRowActions ? rowActions : undefined}
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
            {:else if column.key === 'sale_price'}
                {product.sale_price ?? '—'}
            {:else}
                {product.stock_quantity}
            {/if}
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
    <DialogContent class="flex max-h-[85vh] max-w-2xl flex-col p-0">
        <div class="border-b px-6 py-4">
            <DialogTitle>New product</DialogTitle>
        </div>
        <!-- novalidate: the server names each missing field (the browser's bubble doesn't, and can be off-screen). -->
        <Form
            {...store.form()}
            class="flex min-h-0 flex-1 flex-col"
            novalidate
            enctype="multipart/form-data"
            onSuccess={() => {
                createOpen = false;
            }}
        >
            {#snippet children({ errors, processing })}
                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-6">
                    <FormErrorSummary {errors} />
                    <FormFields {brands} {categories} {subcategories} {tags} {errors} />

                    <CreateImagePicker limits={mediaLimits.image} {errors} />
                </div>

                <div class="border-t px-6 py-4">
                    <DialogFooter>
                        <Button type="button" variant="outline" onclick={() => (createOpen = false)}>Cancel</Button>
                        <Button type="submit" disabled={processing}>
                            {#if processing}<Spinner />{/if}
                            Create product
                        </Button>
                    </DialogFooter>
                </div>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<!-- Title and footer stay put; only the middle scrolls. Save sits below every section. -->
<Dialog bind:open={editOpen}>
    <DialogContent class="flex max-h-[85vh] max-w-2xl flex-col p-0">
        <div class="border-b px-6 py-4">
            <DialogTitle>Edit product</DialogTitle>
        </div>
        {#if editTarget}
            {@const product = editTarget}
            <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-6">
                <Form
                    {...update.form(product.id)}
                    id="edit-product-form"
                    class="space-y-6"
                    novalidate
                    onStart={() => (savingEdit = true)}
                    onFinish={() => (savingEdit = false)}
                >
                    {#snippet children({ errors })}
                        <FormErrorSummary {errors} />
                        <FormFields {product} {brands} {categories} {subcategories} {tags} {errors} />
                    {/snippet}
                </Form>

                <!-- Outside the form: images upload and delete on their own, without "Save changes". -->
                <div class="border-t pt-6">
                    <MediaManager productId={product.id} media={product.media} limits={mediaLimits} />
                </div>
            </div>

            <div class="border-t px-6 py-4">
                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (editOpen = false)}>Cancel</Button>
                    <Button type="submit" form="edit-product-form" disabled={savingEdit}>
                        {#if savingEdit}<Spinner />{/if}
                        Save changes
                    </Button>
                </DialogFooter>
            </div>
        {/if}
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={deleteOpen}
    title="Move product to trash?"
    description={deleteTarget ? `"${deleteTarget.name}" will be hidden from the site. You can restore it from the trash.` : ''}
    onConfirm={confirmDeleteProduct}
/>

<ConfirmDialog
    bind:open={forceDeleteOpen}
    title="Delete product permanently?"
    description={forceDeleteTarget
        ? `This permanently deletes "${forceDeleteTarget.name}" and its uploaded images. This cannot be undone.`
        : ''}
    onConfirm={confirmForceDeleteProduct}
/>

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title="Move selected products to trash?"
    description={`${selectedIds.length} selected products will be hidden from the site. You can restore them from the trash.`}
    onConfirm={() => runBulkAction('delete')}
/>
