<script lang="ts">
    import { untrack } from 'svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import InputError from '@/components/InputError.svelte';
    import RequiredMark from '@/components/dashboard/RequiredMark.svelte';
    import LimitedInput from '@/components/dashboard/LimitedInput.svelte';
    import LimitedTextarea from '@/components/dashboard/LimitedTextarea.svelte';
    import SeoFields, { previewSlug, type SeoCheck } from '@/components/dashboard/SeoFields.svelte';
    import { Input } from '@/components/ui/input';
    import { fieldLimits } from '@/lib/field-limits';
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
        media?: { type: string; alt_text: string | null }[];
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

    // Image checks only apply when editing (new products add images below the form).
    const images = $derived((product?.media ?? []).filter((item) => item.type === 'image'));
    const imageChecks = $derived<SeoCheck[]>(
        product
            ? [
                  { label: 'Has at least one image', ok: images.length > 0, hint: 'Add images in the media section.' },
                  {
                      label: 'Every image has alt text',
                      ok: images.length > 0 && images.every((item) => (item.alt_text ?? '').trim() !== ''),
                      hint: 'Describe each image in the media section; Google Images uses it.',
                  },
              ]
            : [],
    );

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
    let showPrice = $state(untrack(() => product?.show_price ?? true));
    let showStock = $state(untrack(() => product?.show_stock ?? true));
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

<p class="text-xs text-muted-foreground">Fields marked <span class="text-destructive">*</span> are required.</p>

<div class="grid gap-2">
    <Label for="name">Name<RequiredMark /></Label>
    <LimitedInput
        id="name"
        name="name"
        required
        limit={fieldLimits.product.name}
        bind:value={name}
        oninput={onNameInput}
    />
    <InputError message={errors.name} />
</div>

<div class="grid gap-2">
    <Label for="slug">URL slug</Label>
    <LimitedInput
        id="slug"
        name="slug"
        limit={fieldLimits.product.slug}
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

<div class="grid items-start gap-4 sm:grid-cols-2">
    <div class="grid gap-2">
        <Label for="sku">SKU<RequiredMark /></Label>
        <LimitedInput id="sku" name="sku" required limit={fieldLimits.product.sku} value={product?.sku ?? ''} />
        <InputError message={errors.sku} />
    </div>
    <div class="grid gap-2">
        <Label for="model_number">Model number</Label>
        <LimitedInput
            id="model_number"
            name="model_number"
            limit={fieldLimits.product.model_number}
            value={product?.model_number ?? ''}
        />
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
    <LimitedTextarea
        id="short_description"
        name="short_description"
        rows={3}
        limit={fieldLimits.product.short_description}
        value={product?.short_description ?? ''}
    />
    <p class="text-xs text-muted-foreground">Shown under the name on product cards (first two lines) and on the product page.</p>
    <InputError message={errors.short_description} />
</div>

<div class="grid gap-2">
    <Label for="full_description">Full description</Label>
    <LimitedTextarea
        id="full_description"
        name="full_description"
        rows={6}
        limit={fieldLimits.product.full_description}
        value={product?.full_description ?? ''}
    />
    <InputError message={errors.full_description} />
</div>

<div class="grid items-start gap-4 sm:grid-cols-3">
    <div class="grid gap-2">
        <Label for="regular_price">Regular price</Label>
        <Input
            id="regular_price"
            name="regular_price"
            type="number"
            step="0.01"
            min="0"
            max={fieldLimits.number.price}
            value={product?.regular_price ?? ''}
        />
        <p class="text-xs text-muted-foreground">Leave blank to show “Contact for price”.</p>
        <InputError message={errors.regular_price} />
    </div>
    <div class="grid gap-2">
        <Label for="sale_price">Sale price</Label>
        <Input
            id="sale_price"
            name="sale_price"
            type="number"
            step="0.01"
            min="0"
            max={fieldLimits.number.price}
            value={product?.sale_price ?? ''}
        />
        <InputError message={errors.sale_price} />
    </div>
    <div class="grid gap-2">
        <Label for="currency">Currency</Label>
        <Input
            id="currency"
            name="currency"
            maxlength={3}
            minlength={3}
            class="uppercase"
            placeholder="BDT"
            value={product?.currency ?? 'BDT'}
        />
        <p class="text-xs text-muted-foreground">3-letter code, e.g. BDT or USD.</p>
        <InputError message={errors.currency} />
    </div>
</div>

<div class="grid items-start gap-4 sm:grid-cols-2">
    <div class="grid gap-2">
        <Label for="stock_status">Stock status<RequiredMark /></Label>
        <FormSelect
            id="stock_status"
            name="stock_status"
            value={product?.stock_status ?? 'in_stock'}
            options={labelled(['in_stock', 'out_of_stock', 'backorder', 'on_order'])}
        />
        <p class="text-xs text-muted-foreground">Stock quantity is managed in Stock Management.</p>
        <InputError message={errors.stock_status} />
    </div>
    <div class="grid gap-2">
        <Label for="status">Status<RequiredMark /></Label>
        <FormSelect
            id="status"
            name="status"
            value={product?.status ?? 'draft'}
            options={labelled(['draft', 'published', 'archived'])}
        />
        <InputError message={errors.status} />
    </div>
</div>

<fieldset class="grid gap-3 rounded-md border p-4">
    <legend class="px-1 text-sm font-medium">Storefront visibility</legend>
    <input type="hidden" name="show_price" value={showPrice ? '1' : '0'} />
    <label class="flex items-center gap-2 text-sm">
        <input
            type="checkbox"
            class="accent-primary"
            checked={showPrice}
            onchange={(event) => (showPrice = event.currentTarget.checked)}
        />
        Show price to customers
    </label>
    <input type="hidden" name="show_stock" value={showStock ? '1' : '0'} />
    <label class="flex items-center gap-2 text-sm">
        <input
            type="checkbox"
            class="accent-primary"
            checked={showStock}
            onchange={(event) => (showStock = event.currentTarget.checked)}
        />
        Show stock status and quantity to customers
    </label>
</fieldset>

<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_featured" value="1" checked={product?.is_featured ?? false} />
    Featured product
</label>

<div class="grid items-start gap-4 sm:grid-cols-2">
    <div class="grid gap-2">
        <Label for="category_id">Category<RequiredMark /></Label>
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
    <LimitedInput id="seo_title" name="seo_title" limit={fieldLimits.product.seo_title} value={product?.seo_title ?? ''} />
    <InputError message={errors.seo_title} />
</div>
<div class="grid gap-2">
    <Label for="seo_description">SEO description</Label>
    <LimitedTextarea
        id="seo_description"
        name="seo_description"
        rows={2}
        class="min-h-16"
        limit={fieldLimits.product.seo_description}
        value={product?.seo_description ?? ''}
    />
    <InputError message={errors.seo_description} />
</div>
<SeoFields
    withInputs={false}
    values={product ?? {}}
    pathFor={(form) => `/products/${(form.slug ?? '').trim() || product?.slug || previewSlug(form.name ?? '')}`}
    fallbackDescriptionFields={['short_description', 'full_description']}
    content={{ field: 'full_description', min: 300, label: 'Full description of at least 300 characters' }}
    checks={imageChecks}
/>
