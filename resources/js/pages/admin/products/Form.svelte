<script module lang="ts">
    import { create, edit, index } from '@/routes/admin/products';

    export const layout = (props: { product?: { id: number; name: string } }) => ({
        breadcrumbs: [
            { title: 'Products', href: index() },
            props.product
                ? { title: `Edit ${props.product.name}`, href: edit(props.product.id) }
                : { title: 'New product', href: create() },
        ],
    });
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { store, update } from '@/routes/admin/products';
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
        show_price: boolean;
        show_stock: boolean;
        regular_price: string | null;
        sale_price: string | null;
        currency: string;
        stock_quantity: number;
        stock_status: string;
        seo_title: string | null;
        seo_description: string | null;
        seo_keywords: string | null;
        canonical_url: string | null;
        category_id: number;
        subcategory_id: number | null;
        tags: { id: number }[];
        media: MediaItem[];
    };

    let {
        product,
        brands,
        categories,
        subcategories,
        tags,
        mediaLimits,
    }: {
        product?: ProductDetail;
        brands: Option[];
        categories: Option[];
        subcategories: SubcategoryOption[];
        tags: Option[];
        mediaLimits?: MediaLimits;
    } = $props();

    const isEdit = $derived(Boolean(product));
</script>

<AppHead title={isEdit ? 'Edit product' : 'New product'} />

<div class="mx-auto w-full max-w-3xl space-y-8 p-4">
    <h1 class="text-xl font-semibold">{isEdit ? 'Edit product' : 'New product'}</h1>

    <Form
        {...isEdit ? update.form(product!.id) : store.form()}
        class="space-y-6"
    >
        {#snippet children({ errors, processing })}
            <FormFields {product} {brands} {categories} {subcategories} {tags} {errors} />

            <Button type="submit" disabled={processing}>
                {#if processing}<Spinner />{/if}
                {isEdit ? 'Save changes' : 'Create product'}
            </Button>
        {/snippet}
    </Form>

    {#if isEdit && product && mediaLimits}
        <div class="border-t pt-6">
            <MediaManager productId={product.id} media={product.media} limits={mediaLimits} />
        </div>
    {/if}
</div>
