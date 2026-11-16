<script module lang="ts">
    import { index } from '@/routes/admin/categories';

    export const layout = {
        breadcrumbs: [{ title: 'Category Management', href: index() }],
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
    import { bulk, destroy, store, update } from '@/routes/admin/categories';

    type CategoryRow = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        seo_title: string | null;
        seo_description: string | null;
        image: string | null;
        is_active: boolean;
        sort_order: number;
        subcategories_count: number;
        products_count: number;
    };

    let { categories }: { categories: CategoryRow[] } = $props();

    const canManage = $derived(can('categories.manage'));

    let search = $state('');
    let selectedIds = $state<number[]>([]);
    let bulkDeleteOpen = $state(false);
    let bulkError = $state<string | null>(null);

    const filteredCategories = $derived(
        categories.filter((category) => category.name.toLowerCase().includes(search.toLowerCase())),
    );

    let createOpen = $state(false);
    let editingCategory = $state<CategoryRow | null>(null);
    let editOpen = $state(false);
    let deleteTarget = $state<CategoryRow | null>(null);
    let deleteOpen = $state(false);

    function editCategory(category: CategoryRow) {
        editingCategory = category;
        editOpen = true;
    }

    function askDeleteCategory(category: CategoryRow) {
        deleteTarget = category;
        deleteOpen = true;
    }

    function confirmDeleteCategory() {
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

<AppHead title="Category Management" />

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

{#snippet categoryActions(category: CategoryRow)}
    {@render rowActions(category.name, () => editCategory(category), () => askDeleteCategory(category))}
{/snippet}

<div class="w-full p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Categories</h1>
        {#if canManage}
            <Button onclick={() => (createOpen = true)}>Create category</Button>
        {/if}
    </div>

    <Input bind:value={search} placeholder="Search categories..." class="mb-4 sm:max-w-sm" />

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
        items={filteredCategories}
        key={(category) => category.id}
        columns={[
            { key: 'image', label: 'Image' },
            { key: 'name', label: 'Name' },
            { key: 'active', label: 'Active' },
            { key: 'subcategories', label: 'Subcategories', class: 'text-right' },
            { key: 'products', label: 'Products', class: 'text-right' },
        ]}
        empty="No categories found."
        selectedKeys={canManage ? selectedIds : undefined}
        onSelectionChange={canManage ? (keys) => (selectedIds = keys.map(Number)) : undefined}
        actions={canManage ? categoryActions : undefined}
    >
        {#snippet cell(category, column)}
            {#if column.key === 'image'}
                {@render thumbnail(category.image, category.name)}
            {:else if column.key === 'name'}
                {category.name}
            {:else if column.key === 'active'}
                {@render activeBadge(category.is_active)}
            {:else if column.key === 'subcategories'}
                {category.subcategories_count}
            {:else}
                {category.products_count}
            {/if}
        {/snippet}
    </ResponsiveTable>
</div>

<Dialog bind:open={createOpen}>
    <DialogContent class="max-h-[85vh] overflow-y-auto">
        <DialogTitle>Create category</DialogTitle>
        <Form {...store.form()} class="space-y-4" resetOnSuccess onSuccess={() => (createOpen = false)}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <LimitedInput id="name" name="name" required limit={fieldLimits.category.name} />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="create_category_description">Description</Label>
                    <LimitedTextarea
                        id="create_category_description"
                        name="description"
                        rows={3}
                        limit={fieldLimits.category.description}
                        value={''}
                        placeholder="Shown at the top of the page. A few sentences about what's in it help it rank."
                    />
                    <InputError message={errors.description} />
                </div>

                <div class="grid gap-2">
                    <Label for="image_file">Image</Label>
                    <ImageFileInput id="image_file" />
                    <InputError message={errors.image_file} />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" checked />
                    Active
                </label>

                <SeoFields
                    idPrefix="create_category"
                    titleLimit={fieldLimits.category.seo_title}
                    descriptionLimit={fieldLimits.category.seo_description}
                    {errors}
                    pathFor={(form) => `/categories/${previewSlug(form.name ?? '')}`}
                    content={{ field: 'description', min: 120, label: 'Description of at least 120 characters' }}
                />

                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (createOpen = false)}>Cancel</Button>
                    <Button type="submit" disabled={processing}>Create category</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<Dialog bind:open={editOpen}>
    <DialogContent class="max-h-[85vh] overflow-y-auto">
        <DialogTitle>Edit category</DialogTitle>
        {#if editingCategory}
            {@const category = editingCategory}
            <Form
                {...update.form(category.id)}
                class="space-y-4"
                onSuccess={() => (editOpen = false)}
            >
                {#snippet children({ errors, processing })}
                    <div class="grid gap-2">
                        <Label for="edit_category_name">Name</Label>
                        <LimitedInput
                            id="edit_category_name"
                            name="name"
                            value={category.name}
                            required
                            limit={fieldLimits.category.name}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_category_description">Description</Label>
                        <LimitedTextarea
                            id="edit_category_description"
                            name="description"
                            rows={3}
                            limit={fieldLimits.category.description}
                            value={category.description ?? ''}
                            placeholder="Shown at the top of the page. A few sentences about what's in it help it rank."
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_category_image_file">Image</Label>
                        <ImageFileInput id="edit_category_image_file" currentImage={category.image} alt={category.name} />
                        <p class="text-xs text-muted-foreground">Leave blank to keep the current image.</p>
                        <InputError message={errors.image_file} />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" value="1" checked={category.is_active} />
                        Active
                    </label>

                    <div class="grid gap-2">
                        <Label for="edit_category_sort_order">Sort order</Label>
                        <Input
                            id="edit_category_sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            max={fieldLimits.number.sort_order}
                            value={category.sort_order}
                        />
                        <InputError message={errors.sort_order} />
                    </div>

                    <SeoFields
                        idPrefix="edit_category"
                        titleLimit={fieldLimits.category.seo_title}
                        descriptionLimit={fieldLimits.category.seo_description}
                        values={category}
                        {errors}
                        pathFor={() => `/categories/${category.slug}`}
                        content={{ field: 'description', min: 120, label: 'Description of at least 120 characters' }}
                        checks={[{ label: 'Has an image', ok: !!category.image, hint: 'Used when the page is shared.' }]}
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
    title="Delete category?"
    description={deleteTarget ? `This will permanently delete "${deleteTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteCategory}
/>

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title="Delete selected categories?"
    description={`This will permanently delete ${selectedIds.length} selected categories. Categories with subcategories or products cannot be deleted.`}
    onConfirm={() => runBulkAction('delete')}
/>
