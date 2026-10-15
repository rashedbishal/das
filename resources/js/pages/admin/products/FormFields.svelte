<script lang="ts">
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

    const selectedTagIds = new Set(product?.tags.map((t) => t.id) ?? []);

    let selectedCategoryId = $state<number | ''>(product?.category_id ?? '');
    let selectedSubcategoryId = $state<number | ''>(product?.subcategory_id ?? '');
    const availableSubcategories = $derived(
        subcategories.filter((subcategory) => subcategory.category_id === selectedCategoryId),
    );

    $effect(() => {
        if (!availableSubcategories.some((subcategory) => subcategory.id === selectedSubcategoryId)) {
            selectedSubcategoryId = '';
        }
    });
</script>

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

<div class="grid grid-cols-2 gap-4">
    <div class="grid gap-2">
        <Label for="category_id">Category</Label>
        <select
            id="category_id"
            name="category_id"
            required
            bind:value={selectedCategoryId}
            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
        >
            <option value="" disabled>Select a category</option>
            {#each categories as category (category.id)}
                <option value={category.id}>{category.name}</option>
            {/each}
        </select>
        <InputError message={errors.category_id} />
    </div>
    <div class="grid gap-2">
        <Label for="subcategory_id">Subcategory</Label>
        <select
            id="subcategory_id"
            name="subcategory_id"
            required
            disabled={!selectedCategoryId}
            bind:value={selectedSubcategoryId}
            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
        >
            <option value="" disabled>Select a subcategory</option>
            {#each availableSubcategories as subcategory (subcategory.id)}
                <option value={subcategory.id}>{subcategory.name}</option>
            {/each}
        </select>
        <InputError message={errors.subcategory_id} />
    </div>
</div>

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
