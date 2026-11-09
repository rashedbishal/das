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
    let selectedSubcategoryId = $state(untrack(() => (product?.subcategory_id ? String(product.subcategory_id) : '')));
    const availableSubcategories = $derived(
        subcategories.filter((subcategory) => String(subcategory.category_id) === selectedCategoryId),
    );

    $effect(() => {
        if (!availableSubcategories.some((subcategory) => String(subcategory.id) === selectedSubcategoryId)) {
            selectedSubcategoryId = '';
        }
    });

    // Mirrors Laravel's Str::slug(), which the server applies again on save.
    const slugify = (value: string) =>
        value
            .normalize('NFKD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

    const originalSlug = untrack(() => product?.slug ?? '');
    let name = $state(untrack(() => product?.name ?? ''));
    let slug = $state(originalSlug);
    let slugEdited = $state(untrack(() => !!product));

    // A blank slug keeps the current one on edit, or is generated from the name on create.
    const effectiveSlug = $derived(slugify(slug) || (product ? originalSlug : slugify(name)));
    const slugChanged = $derived(!!product && effectiveSlug !== originalSlug);

    function onNameInput() {
        if (!slugEdited) {
            slug = slugify(name);
        }
    }
</script>

<div class="grid gap-2">
    <Label for="name">Name</Label>
    <Input id="name" name="name" required bind:value={name} oninput={onNameInput} />
    <InputError message={errors.name} />
</div>

<div class="grid gap-2">
    <Label for="slug">URL slug</Label>
    <Input
        id="slug"
        name="slug"
        bind:value={slug}
        oninput={() => (slugEdited = true)}
        onblur={() => (slug = slugify(slug))}
        placeholder={product ? originalSlug : 'generated-from-the-name'}
    />
    <p class="text-xs break-all text-muted-foreground">
        Page address: /products/<span class="font-medium text-foreground">{effectiveSlug || '…'}</span>
    </p>
    {#if !product}
        <p class="text-xs text-muted-foreground">
            This fills in automatically from the name, and it's best to leave it as is. It becomes the
            product's web address, so pick something you're happy to keep.
        </p>
    {:else if slugChanged}
        <p class="rounded-md border border-destructive/40 bg-destructive/5 p-3 text-xs text-foreground">
            Heads up: once you save, the old address <span class="font-medium break-all">/products/{originalSlug}</span>
            will stop working. Anyone using a shared link, a bookmark or a search result will see a "page not found"
            message instead. If that's not what you want, change it back to <span class="font-medium">{originalSlug}</span>.
        </p>
    {:else}
        <p class="text-xs text-muted-foreground">
            This is the product's web address. We'd recommend keeping it the same — if you change it, links people
            have already shared or bookmarked will no longer find this product.
        </p>
    {/if}
    <InputError message={errors.slug} />
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
        <Input id="regular_price" name="regular_price" type="number" step="0.01" value={product?.regular_price ?? ''} />
        <p class="text-xs text-muted-foreground">Leave blank to show “Contact for price”.</p>
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
            options={labelled(['in_stock', 'out_of_stock', 'backorder', 'on_order'])}
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
            disabled={!selectedCategoryId || availableSubcategories.length === 0}
            bind:value={selectedSubcategoryId}
            placeholder={availableSubcategories.length === 0
                ? 'No subcategories (directly in category)'
                : 'Select a subcategory'}
            options={toOptions(availableSubcategories)}
        />
        <p class="text-xs text-muted-foreground">Optional. Leave blank to assign the product directly to its category.</p>
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
