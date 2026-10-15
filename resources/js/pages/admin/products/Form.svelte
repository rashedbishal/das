<script module lang="ts">
    import { index } from '@/routes/admin/products';

    export const layout = {
        breadcrumbs: [{ title: 'Products', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { store, update } from '@/routes/admin/products';
    import { store as storeMedia, destroy as destroyMedia } from '@/routes/admin/products/media';

    type Option = { id: number; name: string; parent_id?: number | null };
    type MediaItem = {
        id: number;
        type: string;
        is_main: boolean;
        alt_text: string | null;
        url?: string | null;
    };

    type ProductDetail = {
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
        seo_keywords: string | null;
        canonical_url: string | null;
        categories: { id: number }[];
        tags: { id: number }[];
        media: MediaItem[];
    };

    let {
        product,
        brands,
        categories,
        tags,
    }: {
        product?: ProductDetail;
        brands: Option[];
        categories: Option[];
        tags: Option[];
    } = $props();

    const isEdit = Boolean(product);
    const selectedCategoryIds = new Set(product?.categories.map((c) => c.id) ?? []);
    const selectedTagIds = new Set(product?.tags.map((t) => t.id) ?? []);

    let uploadType = $state<'image' | 'video'>('image');
    let uploadSource = $state<'upload' | 'url'>('upload');
    let uploadUrl = $state('');
    let uploadFile: FileList | undefined = $state(undefined);
    let uploadIsMain = $state(false);

    function submitMedia(event: SubmitEvent) {
        event.preventDefault();
        if (!product) return;

        const data = new FormData();
        data.append('source', uploadSource);
        data.append('type', uploadType);
        data.append('is_main', uploadIsMain ? '1' : '0');
        if (uploadSource === 'upload' && uploadFile?.[0]) {
            data.append('file', uploadFile[0]);
        } else {
            data.append('url', uploadUrl);
        }

        router.post(storeMedia.url(product.id), data, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                uploadUrl = '';
                uploadFile = undefined;
                uploadIsMain = false;
            },
        });
    }

    function removeMedia(mediaId: number) {
        if (!product) return;
        router.delete(destroyMedia.url({ product: product.id, media: mediaId }), {
            preserveScroll: true,
        });
    }
</script>

<AppHead title={isEdit ? 'Edit product' : 'New product'} />

<div class="mx-auto max-w-3xl space-y-8 p-4">
    <h1 class="text-xl font-semibold">{isEdit ? 'Edit product' : 'New product'}</h1>

    <Form
        {...isEdit ? update.form(product!.id) : store.form()}
        class="space-y-6"
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required value={product?.name ?? ''} />
                <InputError message={errors.name} />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="sku">SKU</Label>
                    <Input id="sku" name="sku" required value={product?.sku ?? ''} />
                    <InputError message={errors.sku} />
                </div>
                <div class="grid gap-2">
                    <Label for="model_number">Model number</Label>
                    <Input id="model_number" name="model_number" value={product?.model_number ?? ''} />
                    <InputError message={errors.model_number} />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="brand_id">Brand</Label>
                <select
                    id="brand_id"
                    name="brand_id"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                >
                    <option value="">No brand</option>
                    {#each brands as brand (brand.id)}
                        <option value={brand.id} selected={product?.brand_id === brand.id}>
                            {brand.name}
                        </option>
                    {/each}
                </select>
                <InputError message={errors.brand_id} />
            </div>

            <div class="grid gap-2">
                <Label for="short_description">Short description</Label>
                <textarea
                    id="short_description"
                    name="short_description"
                    rows="2"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                >{product?.short_description ?? ''}</textarea>
                <InputError message={errors.short_description} />
            </div>

            <div class="grid gap-2">
                <Label for="full_description">Full description</Label>
                <textarea
                    id="full_description"
                    name="full_description"
                    rows="6"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                >{product?.full_description ?? ''}</textarea>
                <InputError message={errors.full_description} />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="regular_price">Regular price</Label>
                    <Input id="regular_price" name="regular_price" type="number" step="0.01" required value={product?.regular_price ?? ''} />
                    <InputError message={errors.regular_price} />
                </div>
                <div class="grid gap-2">
                    <Label for="sale_price">Sale price</Label>
                    <Input id="sale_price" name="sale_price" type="number" step="0.01" value={product?.sale_price ?? ''} />
                    <InputError message={errors.sale_price} />
                </div>
                <div class="grid gap-2">
                    <Label for="currency">Currency</Label>
                    <Input id="currency" name="currency" value={product?.currency ?? 'BDT'} />
                    <InputError message={errors.currency} />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="grid gap-2">
                    <Label for="stock_quantity">Stock quantity</Label>
                    <Input id="stock_quantity" name="stock_quantity" type="number" required value={product?.stock_quantity ?? 0} />
                    <InputError message={errors.stock_quantity} />
                </div>
                <div class="grid gap-2">
                    <Label for="stock_status">Stock status</Label>
                    <select
                        id="stock_status"
                        name="stock_status"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                    >
                        {#each ['in_stock', 'out_of_stock', 'backorder'] as value}
                            <option {value} selected={(product?.stock_status ?? 'in_stock') === value}>
                                {value.replace('_', ' ')}
                            </option>
                        {/each}
                    </select>
                    <InputError message={errors.stock_status} />
                </div>
                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        name="status"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                    >
                        {#each ['draft', 'published', 'archived'] as value}
                            <option {value} selected={(product?.status ?? 'draft') === value}>
                                {value}
                            </option>
                        {/each}
                    </select>
                    <InputError message={errors.status} />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_featured" value="1" checked={product?.is_featured ?? false} />
                Featured product
            </label>

            <fieldset class="grid gap-2">
                <legend class="mb-1 text-sm font-medium">Categories</legend>
                <div class="grid max-h-48 grid-cols-2 gap-1 overflow-y-auto rounded-md border p-3 text-sm">
                    {#each categories as category (category.id)}
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="category_ids[]" value={category.id} checked={selectedCategoryIds.has(category.id)} />
                            {category.name}
                        </label>
                    {/each}
                </div>
            </fieldset>

            <fieldset class="grid gap-2">
                <legend class="mb-1 text-sm font-medium">Tags</legend>
                <div class="grid max-h-48 grid-cols-2 gap-1 overflow-y-auto rounded-md border p-3 text-sm">
                    {#each tags as tag (tag.id)}
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="tag_ids[]" value={tag.id} checked={selectedTagIds.has(tag.id)} />
                            {tag.name}
                        </label>
                    {/each}
                </div>
            </fieldset>

            <div class="grid gap-2">
                <Label for="seo_title">SEO title</Label>
                <Input id="seo_title" name="seo_title" value={product?.seo_title ?? ''} />
            </div>
            <div class="grid gap-2">
                <Label for="seo_description">SEO description</Label>
                <Input id="seo_description" name="seo_description" value={product?.seo_description ?? ''} />
            </div>

            <Button type="submit" disabled={processing}>
                {#if processing}<Spinner />{/if}
                {isEdit ? 'Save changes' : 'Create product'}
            </Button>
        {/snippet}
    </Form>

    {#if isEdit && product}
        <section class="space-y-4 border-t pt-6">
            <h2 class="text-lg font-semibold">Media</h2>

            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                {#each product.media as media (media.id)}
                    <div class="relative rounded-md border p-2 text-xs">
                        {#if media.type === 'image' && media.url}
                            <img src={media.url} alt={media.alt_text ?? ''} class="mb-1 aspect-square w-full rounded object-cover" />
                        {:else}
                            <div class="mb-1 flex aspect-square w-full items-center justify-center rounded bg-muted">
                                {media.type}
                            </div>
                        {/if}
                        {#if media.is_main}<span class="font-medium">Main</span>{/if}
                        <button
                            type="button"
                            class="mt-1 block w-full text-red-600 hover:underline"
                            onclick={() => removeMedia(media.id)}
                        >
                            Remove
                        </button>
                    </div>
                {/each}
            </div>

            <form onsubmit={submitMedia} class="grid gap-3 rounded-md border p-4 text-sm">
                <div class="flex gap-4">
                    <label class="flex items-center gap-1">
                        <input type="radio" bind:group={uploadSource} value="upload" /> Upload file
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="radio" bind:group={uploadSource} value="url" /> External URL
                    </label>
                </div>

                <div class="flex gap-4">
                    <label class="flex items-center gap-1">
                        <input type="radio" bind:group={uploadType} value="image" /> Image
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="radio" bind:group={uploadType} value="video" /> Video
                    </label>
                </div>

                {#if uploadSource === 'upload'}
                    <input type="file" bind:files={uploadFile} accept={uploadType === 'image' ? 'image/*' : 'video/*'} />
                {:else}
                    <Input placeholder="https://..." bind:value={uploadUrl} />
                {/if}

                <label class="flex items-center gap-2">
                    <input type="checkbox" bind:checked={uploadIsMain} /> Set as main image
                </label>

                <Button type="submit">Add media</Button>
            </form>
        </section>
    {/if}
</div>
