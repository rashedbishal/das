<script lang="ts">
    import { untrack } from 'svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    type Option = { id: number; name: string };
    type SubcategoryOption = { id: number; name: string; category_id: number };

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
        category_id: number;
        subcategory_id: number;
        tags: { id: number }[];
    };

    let {
        product,
        brands,
        categories,
        subcategories,
        tags,
        errors,
    }: {
        product?: ProductDetail;
        brands: Option[];
        categories: Option[];
        subcategories: SubcategoryOption[];
        tags: Option[];
        errors: Record<string, string>;
    } = $props();

    const selectedTagIds = untrack(() => new Set(product?.tags.map((t) => t.id) ?? []));

    const toOptions = (items: Option[]) =>
        items.map((item) => ({ value: String(item.id), label: item.name }));
    const labelled = (values: string[]) =>
        values.map((value) => ({
            value,
            label: value.charAt(0).toUpperCase() + value.slice(1).replaceAll('_', ' '),
        }));

    let selectedCategoryId = $state(untrack(() => (product ? String(product.category_id) : '')));
    let selectedSubcategoryId = $state(untrack(() => (product ? String(product.subcategory_id) : '')));
    const availableSubcategories = $derived(
        subcategories.filter((subcategory) => String(subcategory.category_id) === selectedCategoryId),
    );

    $effect(() => {
        if (!availableSubcategories.some((subcategory) => String(subcategory.id) === selectedSubcategoryId)) {
            selectedSubcategoryId = '';
        }
    });
</script>

<div class="grid gap-2">
    <Label for="name">Name</Label>
    <Input id="name" name="name" required value={product?.name ?? ''} />
    <InputError message={errors.name} />
</div>

<div class="grid gap-4 sm:grid-cols-2">
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
    <FormSelect
        id="brand_id"
        name="brand_id"
        value={product?.brand_id ? String(product.brand_id) : ''}
        placeholder="No brand"
        options={[{ value: '', label: 'No brand' }, ...toOptions(brands)]}
    />
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

<div class="grid gap-4 sm:grid-cols-3">
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

<div class="grid gap-4 sm:grid-cols-3">
    <div class="grid gap-2">
        <Label for="stock_quantity">Stock quantity</Label>
        <Input id="stock_quantity" name="stock_quantity" type="number" required value={product?.stock_quantity ?? 0} />
        <InputError message={errors.stock_quantity} />
    </div>
    <div class="grid gap-2">
        <Label for="stock_status">Stock status</Label>
        <FormSelect
            id="stock_status"
            name="stock_status"
            value={product?.stock_status ?? 'in_stock'}
            options={labelled(['in_stock', 'out_of_stock', 'backorder'])}
        />
        <InputError message={errors.stock_status} />
    </div>
    <div class="grid gap-2">
        <Label for="status">Status</Label>
        <FormSelect
            id="status"
            name="status"
            value={product?.status ?? 'draft'}
            options={labelled(['draft', 'published', 'archived'])}
        />
        <InputError message={errors.status} />
    </div>
</div>

<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_featured" value="1" checked={product?.is_featured ?? false} />
    Featured product
</label>

<div class="grid gap-4 sm:grid-cols-2">
    <div class="grid gap-2">
        <Label for="category_id">Category</Label>
        <FormSelect
            id="category_id"
            name="category_id"
            required
            bind:value={selectedCategoryId}
            placeholder="Select a category"
            options={toOptions(categories)}
        />
        <InputError message={errors.category_id} />
    </div>
    <div class="grid gap-2">
        <Label for="subcategory_id">Subcategory</Label>
        <FormSelect
            id="subcategory_id"
            name="subcategory_id"
            required
            disabled={!selectedCategoryId}
            bind:value={selectedSubcategoryId}
            placeholder="Select a subcategory"
            options={toOptions(availableSubcategories)}
        />
        <InputError message={errors.subcategory_id} />
    </div>
</div>

<fieldset class="grid gap-2">
    <legend class="mb-1 text-sm font-medium">Tags</legend>
    <div class="grid max-h-48 gap-1 overflow-y-auto rounded-md border p-3 text-sm sm:grid-cols-2">
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
