<script module lang="ts">
    import { index } from '@/routes/admin/subcategories';

    export const layout = {
        breadcrumbs: [{ title: 'Subcategories', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import { toast } from 'svelte-sonner';
    import { can } from '@/lib/permissions.svelte';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import BulkActions from '@/components/dashboard/BulkActions.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import ImageFileInput from '@/components/dashboard/ImageFileInput.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import LimitedInput from '@/components/dashboard/LimitedInput.svelte';
    import LimitedTextarea from '@/components/dashboard/LimitedTextarea.svelte';
    import SeoFields, { previewSlug } from '@/components/dashboard/SeoFields.svelte';
    import { fieldLimits } from '@/lib/field-limits';
    import { Label } from '@/components/ui/label';
    import { bulk, destroy, store, update } from '@/routes/admin/subcategories';

    type CategoryOption = { id: number; name: string; slug: string };

    type SubcategoryRow = {
        id: number;
        category_id: number;
        category: { id: number; name: string; slug: string };
        name: string;
        slug: string;
        description: string | null;
        seo_title: string | null;
        seo_description: string | null;
        image: string | null;
        is_active: boolean;
        sort_order: number;
        products_count: number;
    };

    let {
        categories,
        subcategories,
    }: {
        categories: CategoryOption[];
        subcategories: SubcategoryRow[];
    } = $props();

    const canManage = $derived(can('categories.manage'));

    const categorySlugs = $derived(new Map(categories.map((category) => [String(category.id), category.slug])));

    function subcategoryPath(form: Record<string, string>, slug?: string): string {
        const categorySlug = categorySlugs.get(form.category_id ?? '') ?? 'category';
        return `/categories/${categorySlug}/${slug ?? previewSlug(form.name ?? '')}`;
    }

    const categoryOptions = $derived(
        categories.map((category) => ({ value: String(category.id), label: category.name })),
    );

    let search = $state('');
    let selectedIds = $state<number[]>([]);
    let bulkDeleteOpen = $state(false);
    let bulkError = $state<string | null>(null);

    const filteredSubcategories = $derived(
        subcategories.filter(
            (subcategory) =>
                subcategory.name.toLowerCase().includes(search.toLowerCase()) ||
                subcategory.category.name.toLowerCase().includes(search.toLowerCase()),
        ),
    );

    let createOpen = $state(false);
    let editingSubcategory = $state<SubcategoryRow | null>(null);
    let editOpen = $state(false);
    let deleteTarget = $state<SubcategoryRow | null>(null);
    let deleteOpen = $state(false);

    function editSubcategory(subcategory: SubcategoryRow) {
        editingSubcategory = subcategory;
        editOpen = true;
    }

    function askDeleteSubcategory(subcategory: SubcategoryRow) {
        deleteTarget = subcategory;
        deleteOpen = true;
    }

    function confirmDeleteSubcategory() {
        if (deleteTarget) {
            router.delete(destroy.url(deleteTarget.id), {
                onError: (errors) => toast.error(Object.values(errors).join(' ')),
            });
        }
    }

    function runBulkAction(action: 'delete' | 'activate' | 'deactivate') {
        router.post(
            bulk.url(),
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

<AppHead title="Subcategory Management" />

{#snippet rowActions(name: string, onEdit: () => void, onDelete: () => void)}
    <Button variant="ghost" size="sm" aria-label="Edit {name}" onclick={onEdit}>
        <Pencil class="size-4" />
    </Button>
    <Button
        variant="ghost"
        size="sm"
        class="text-destructive hover:text-destructive"
        aria-label="Delete {name}"
        onclick={onDelete}
    >
        <Trash2 class="size-4" />
    </Button>
{/snippet}

{#snippet activeBadge(isActive: boolean)}
    <Badge variant={isActive ? 'default' : 'secondary'}>
        {isActive ? 'Active' : 'Inactive'}
    </Badge>
{/snippet}

{#snippet thumbnail(image: string | null, name: string)}
    {#if image}
        <img src={image} alt={name} class="h-10 w-10 rounded-md object-cover" />
    {:else}
        <div class="h-10 w-10 rounded-md bg-muted"></div>
    {/if}
{/snippet}

{#snippet subcategoryActions(subcategory: SubcategoryRow)}
    {@render rowActions(subcategory.name, () => editSubcategory(subcategory), () => askDeleteSubcategory(subcategory))}
{/snippet}

<div class="w-full p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Subcategories</h1>
        {#if canManage}
            <Button onclick={() => (createOpen = true)}>Create subcategory</Button>
        {/if}
    </div>

    <Input bind:value={search} placeholder="Search subcategories..." class="mb-4 sm:max-w-sm" />

    {#if canManage}
        <BulkActions
            count={selectedIds.length}
            error={bulkError}
            onClear={() => {
                selectedIds = [];
                bulkError = null;
            }}
        >
            {#snippet children()}
                <Button size="sm" onclick={() => runBulkAction('activate')}>Activate</Button>
                <Button size="sm" variant="outline" onclick={() => runBulkAction('deactivate')}>Deactivate</Button>
                <Button size="sm" variant="destructive" onclick={() => (bulkDeleteOpen = true)}>Delete</Button>
            {/snippet}
        </BulkActions>
    {/if}

    <ResponsiveTable
        items={filteredSubcategories}
        key={(subcategory) => subcategory.id}
        columns={[
            { key: 'image', label: 'Image' },
            { key: 'name', label: 'Name' },
            { key: 'category', label: 'Category' },
            { key: 'active', label: 'Active' },
            { key: 'products', label: 'Products', class: 'text-right' },
        ]}
        empty="No subcategories found."
        selectedKeys={canManage ? selectedIds : undefined}
        onSelectionChange={canManage ? (keys) => (selectedIds = keys.map(Number)) : undefined}
        actions={canManage ? subcategoryActions : undefined}
    >
        {#snippet cell(subcategory, column)}
            {#if column.key === 'image'}
                {@render thumbnail(subcategory.image, subcategory.name)}
            {:else if column.key === 'name'}
                {subcategory.name}
            {:else if column.key === 'category'}
                <span class="text-muted-foreground">{subcategory.category.name}</span>
            {:else if column.key === 'active'}
                {@render activeBadge(subcategory.is_active)}
            {:else}
                {subcategory.products_count}
            {/if}
        {/snippet}
    </ResponsiveTable>
</div>

<Dialog bind:open={createOpen}>
    <DialogContent class="max-h-[85vh] overflow-y-auto">
        <DialogTitle>Create subcategory</DialogTitle>
        <Form {...store.form()} class="space-y-4" resetOnSuccess onSuccess={() => (createOpen = false)}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="subcategory_category_id">Category</Label>
                    <FormSelect
                        id="subcategory_category_id"
                        name="category_id"
                        required
                        options={categoryOptions}
                        placeholder="Select a category"
                    />
                    <InputError message={errors.category_id} />
                </div>

                <div class="grid gap-2">
                    <Label for="subcategory_name">Name</Label>
                    <LimitedInput id="subcategory_name" name="name" required limit={fieldLimits.subcategory.name} />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="create_subcategory_description">Description</Label>
                    <LimitedTextarea
                        id="create_subcategory_description"
                        name="description"
                        rows={3}
                        limit={fieldLimits.subcategory.description}
                        value={''}
                        placeholder="Shown at the top of the page. A few sentences about what's in it help it rank."
                    />
                    <InputError message={errors.description} />
                </div>

                <div class="grid gap-2">
                    <Label for="subcategory_image_file">Image</Label>
                    <ImageFileInput id="subcategory_image_file" />
                    <InputError message={errors.image_file} />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" checked />
                    Active
                </label>

                <SeoFields
                    idPrefix="create_subcategory"
                    titleLimit={fieldLimits.subcategory.seo_title}
                    descriptionLimit={fieldLimits.subcategory.seo_description}
                    {errors}
                    pathFor={(form) => subcategoryPath(form)}
                    content={{ field: 'description', min: 120, label: 'Description of at least 120 characters' }}
                />

                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (createOpen = false)}>Cancel</Button>
                    <Button type="submit" disabled={processing}>Create subcategory</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<Dialog bind:open={editOpen}>
    <DialogContent class="max-h-[85vh] overflow-y-auto">
        <DialogTitle>Edit subcategory</DialogTitle>
        {#if editingSubcategory}
            {@const subcategory = editingSubcategory}
            <Form
                {...update.form(subcategory.id)}
                class="space-y-4"
                onSuccess={() => (editOpen = false)}
            >
                {#snippet children({ errors, processing })}
                    <div class="grid gap-2">
                        <Label for="edit_subcategory_category_id">Category</Label>
                        <FormSelect
                            id="edit_subcategory_category_id"
                            name="category_id"
                            required
                            options={categoryOptions}
                            value={String(subcategory.category_id)}
                        />
                        <InputError message={errors.category_id} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_subcategory_name">Name</Label>
                        <LimitedInput
                            id="edit_subcategory_name"
                            name="name"
                            value={subcategory.name}
                            required
                            limit={fieldLimits.subcategory.name}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_subcategory_description">Description</Label>
                        <LimitedTextarea
                            id="edit_subcategory_description"
                            name="description"
                            rows={3}
                            limit={fieldLimits.subcategory.description}
                            value={subcategory.description ?? ''}
                            placeholder="Shown at the top of the page. A few sentences about what's in it help it rank."
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_subcategory_image_file">Image</Label>
                        <ImageFileInput id="edit_subcategory_image_file" currentImage={subcategory.image} alt={subcategory.name} />
                        <p class="text-xs text-muted-foreground">Leave blank to keep the current image.</p>
                        <InputError message={errors.image_file} />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" value="1" checked={subcategory.is_active} />
                        Active
                    </label>

                    <div class="grid gap-2">
                        <Label for="edit_subcategory_sort_order">Sort order</Label>
                        <Input
                            id="edit_subcategory_sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            max={fieldLimits.number.sort_order}
                            value={subcategory.sort_order}
                        />
                        <InputError message={errors.sort_order} />
                    </div>

                    <SeoFields
                        idPrefix="edit_subcategory"
                        titleLimit={fieldLimits.subcategory.seo_title}
                        descriptionLimit={fieldLimits.subcategory.seo_description}
                        values={subcategory}
                        {errors}
                        pathFor={(form) => subcategoryPath(form, subcategory.slug)}
                        content={{ field: 'description', min: 120, label: 'Description of at least 120 characters' }}
                        checks={[{ label: 'Has an image', ok: !!subcategory.image, hint: 'Used when the page is shared.' }]}
                    />

                    <DialogFooter>
                        <Button type="button" variant="outline" onclick={() => (editOpen = false)}>Cancel</Button>
                        <Button type="submit" disabled={processing}>Save</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={deleteOpen}
    title="Delete subcategory?"
    description={deleteTarget ? `This will permanently delete "${deleteTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteSubcategory}
/>

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title="Delete selected subcategories?"
    description={`This will permanently delete ${selectedIds.length} selected subcategories. Subcategories with products cannot be deleted.`}
    onConfirm={() => runBulkAction('delete')}
/>
