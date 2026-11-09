<script module lang="ts">
    import { index } from '@/routes/admin/categories';

    export const layout = {
        breadcrumbs: [{ title: 'Category Management', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import BulkActions from '@/components/dashboard/BulkActions.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { bulk as bulkCategories, destroy, store, update } from '@/routes/admin/categories';
    import {
        bulk as bulkSubcategories,
        destroy as destroySubcategory,
        store as storeSubcategory,
        update as updateSubcategory,
    } from '@/routes/admin/subcategories';

    type CategoryRow = {
        id: number;
        name: string;
        image: string | null;
        is_active: boolean;
        sort_order: number;
        subcategories_count: number;
        products_count: number;
    };

    type SubcategoryRow = {
        id: number;
        category_id: number;
        category: { id: number; name: string };
        name: string;
        image: string | null;
        is_active: boolean;
        sort_order: number;
        products_count: number;
    };

    let { categories, subcategories }: { categories: CategoryRow[]; subcategories: SubcategoryRow[] } = $props();

    const categoryOptions = $derived(
        categories.map((category) => ({ value: String(category.id), label: category.name })),
    );

    let tab = $state<'categories' | 'subcategories'>('categories');

    let categorySearch = $state('');
    let subcategorySearch = $state('');
    let selectedCategoryIds = $state<number[]>([]);
    let selectedSubcategoryIds = $state<number[]>([]);
    let bulkDeleteOpen = $state(false);
    let bulkError = $state<string | null>(null);

    const filteredCategories = $derived(
        categories.filter((category) => category.name.toLowerCase().includes(categorySearch.toLowerCase())),
    );
    const filteredSubcategories = $derived(
        subcategories.filter(
            (subcategory) =>
                subcategory.name.toLowerCase().includes(subcategorySearch.toLowerCase()) ||
                subcategory.category.name.toLowerCase().includes(subcategorySearch.toLowerCase()),
        ),
    );

    let createCategoryOpen = $state(false);
    let editingCategory = $state<CategoryRow | null>(null);
    let categoryDialogOpen = $state(false);
    let deleteCategoryTarget = $state<CategoryRow | null>(null);
    let deleteCategoryOpen = $state(false);

    let createSubcategoryOpen = $state(false);
    let editingSubcategory = $state<SubcategoryRow | null>(null);
    let subcategoryDialogOpen = $state(false);
    let deleteSubcategoryTarget = $state<SubcategoryRow | null>(null);
    let deleteSubcategoryOpen = $state(false);

    function editCategory(category: CategoryRow) {
        editingCategory = category;
        categoryDialogOpen = true;
    }

    function askDeleteCategory(category: CategoryRow) {
        deleteCategoryTarget = category;
        deleteCategoryOpen = true;
    }

    function confirmDeleteCategory() {
        if (deleteCategoryTarget) {
            router.delete(destroy.url(deleteCategoryTarget.id));
        }
    }

    function editSubcategory(subcategory: SubcategoryRow) {
        editingSubcategory = subcategory;
        subcategoryDialogOpen = true;
    }

    function askDeleteSubcategory(subcategory: SubcategoryRow) {
        deleteSubcategoryTarget = subcategory;
        deleteSubcategoryOpen = true;
    }

    function confirmDeleteSubcategory() {
        if (deleteSubcategoryTarget) {
            router.delete(destroySubcategory.url(deleteSubcategoryTarget.id));
        }
    }

    function runBulkAction(action: 'delete' | 'activate' | 'deactivate') {
        const selectedIds = tab === 'categories' ? selectedCategoryIds : selectedSubcategoryIds;
        const route = tab === 'categories' ? bulkCategories : bulkSubcategories;
        router.post(
            route.url(),
            { ids: selectedIds, action },
            {
                onSuccess: () => {
                    if (tab === 'categories') selectedCategoryIds = [];
                    else selectedSubcategoryIds = [];
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
        <img src={image} alt={name} class="h-10 w-10 rounded object-cover" />
    {:else}
        <div class="h-10 w-10 rounded bg-muted"></div>
    {/if}
{/snippet}

<div class="w-full p-4">
    <div class="mb-6 flex gap-2 overflow-x-auto border-b">
        {#each [['categories', 'Categories'], ['subcategories', 'Subcategories']] as [value, label] (value)}
            <button
                class="shrink-0 border-b-2 px-3 py-2 text-sm {tab === value ? 'border-primary font-medium' : 'border-transparent text-muted-foreground'}"
                onclick={() => {
                    tab = value as typeof tab;
                    selectedCategoryIds = [];
                    selectedSubcategoryIds = [];
                }}
            >
                {label}
            </button>
        {/each}
    </div>

    {#if tab === 'categories'}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Categories</h1>
            <Button onclick={() => (createCategoryOpen = true)}>Create category</Button>
        </div>

        <Input bind:value={categorySearch} placeholder="Search categories..." class="mb-4 sm:max-w-sm" />

        <BulkActions
            count={selectedCategoryIds.length}
            error={bulkError}
            onClear={() => {
                selectedCategoryIds = [];
                bulkError = null;
            }}
        >
            {#snippet children()}
                <Button size="sm" onclick={() => runBulkAction('activate')}>Activate</Button>
                <Button size="sm" variant="outline" onclick={() => runBulkAction('deactivate')}>Deactivate</Button>
                <Button size="sm" variant="destructive" onclick={() => (bulkDeleteOpen = true)}>Delete</Button>
            {/snippet}
        </BulkActions>

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
            selectedKeys={selectedCategoryIds}
            onSelectionChange={(keys) => (selectedCategoryIds = keys.map(Number))}
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
            {#snippet actions(category)}
                {@render rowActions(
                    category.name,
                    () => editCategory(category),
                    () => askDeleteCategory(category),
                )}
            {/snippet}
        </ResponsiveTable>
    {:else if tab === 'subcategories'}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Subcategories</h1>
            <Button onclick={() => (createSubcategoryOpen = true)}>Create subcategory</Button>
        </div>

        <Input bind:value={subcategorySearch} placeholder="Search subcategories..." class="mb-4 sm:max-w-sm" />

        <BulkActions
            count={selectedSubcategoryIds.length}
            error={bulkError}
            onClear={() => {
                selectedSubcategoryIds = [];
                bulkError = null;
            }}
        >
            {#snippet children()}
                <Button size="sm" onclick={() => runBulkAction('activate')}>Activate</Button>
                <Button size="sm" variant="outline" onclick={() => runBulkAction('deactivate')}>Deactivate</Button>
                <Button size="sm" variant="destructive" onclick={() => (bulkDeleteOpen = true)}>Delete</Button>
            {/snippet}
        </BulkActions>

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
            selectedKeys={selectedSubcategoryIds}
            onSelectionChange={(keys) => (selectedSubcategoryIds = keys.map(Number))}
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
            {#snippet actions(subcategory)}
                {@render rowActions(
                    subcategory.name,
                    () => editSubcategory(subcategory),
                    () => askDeleteSubcategory(subcategory),
                )}
            {/snippet}
        </ResponsiveTable>
    {/if}
</div>

<Dialog bind:open={createCategoryOpen}>
    <DialogContent>
        <DialogTitle>Create category</DialogTitle>
        <Form {...store.form()} class="space-y-4" resetOnSuccess onSuccess={() => (createCategoryOpen = false)}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" required />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="image_file">Image</Label>
                    <input
                        id="image_file"
                        name="image_file"
                        type="file"
                        accept="image/*"
                        class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium"
                    />
                    <InputError message={errors.image_file} />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" checked />
                    Active
                </label>

                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (createCategoryOpen = false)}>Cancel</Button>
                    <Button type="submit" disabled={processing}>Create category</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<Dialog bind:open={categoryDialogOpen}>
    <DialogContent>
        <DialogTitle>Edit category</DialogTitle>
        {#if editingCategory}
            {@const category = editingCategory}
            <Form
                {...update.form(category.id)}
                class="space-y-4"
                onSuccess={() => (categoryDialogOpen = false)}
            >
                {#snippet children({ errors, processing })}
                    <div class="grid gap-2">
                        <Label for="edit_category_name">Name</Label>
                        <Input id="edit_category_name" name="name" value={category.name} required />
                        <InputError message={errors.name} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_category_image_file">Image</Label>
                        {#if category.image}
                            <img src={category.image} alt={category.name} class="h-16 w-16 rounded object-cover" />
                        {/if}
                        <input
                            id="edit_category_image_file"
                            name="image_file"
                            type="file"
                            accept="image/*"
                            class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium"
                        />
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
                        <Input id="edit_category_sort_order" name="sort_order" type="number" value={category.sort_order} />
                        <InputError message={errors.sort_order} />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onclick={() => (categoryDialogOpen = false)}>Cancel</Button>
                        <Button type="submit" disabled={processing}>Save</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>

<Dialog bind:open={createSubcategoryOpen}>
    <DialogContent>
        <DialogTitle>Create subcategory</DialogTitle>
        <Form {...storeSubcategory.form()} class="space-y-4" resetOnSuccess onSuccess={() => (createSubcategoryOpen = false)}>
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
                    <Input id="subcategory_name" name="name" required />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="subcategory_image_file">Image</Label>
                    <input
                        id="subcategory_image_file"
                        name="image_file"
                        type="file"
                        accept="image/*"
                        class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium"
                    />
                    <InputError message={errors.image_file} />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" checked />
                    Active
                </label>

                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (createSubcategoryOpen = false)}>Cancel</Button>
                    <Button type="submit" disabled={processing}>Create subcategory</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<Dialog bind:open={subcategoryDialogOpen}>
    <DialogContent>
        <DialogTitle>Edit subcategory</DialogTitle>
        {#if editingSubcategory}
            {@const subcategory = editingSubcategory}
            <Form
                {...updateSubcategory.form(subcategory.id)}
                class="space-y-4"
                onSuccess={() => (subcategoryDialogOpen = false)}
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
                        <Input id="edit_subcategory_name" name="name" value={subcategory.name} required />
                        <InputError message={errors.name} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_subcategory_image_file">Image</Label>
                        {#if subcategory.image}
                            <img src={subcategory.image} alt={subcategory.name} class="h-16 w-16 rounded object-cover" />
                        {/if}
                        <input
                            id="edit_subcategory_image_file"
                            name="image_file"
                            type="file"
                            accept="image/*"
                            class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium"
                        />
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
                        <Input id="edit_subcategory_sort_order" name="sort_order" type="number" value={subcategory.sort_order} />
                        <InputError message={errors.sort_order} />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onclick={() => (subcategoryDialogOpen = false)}>Cancel</Button>
                        <Button type="submit" disabled={processing}>Save</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={deleteCategoryOpen}
    title="Delete category?"
    description={deleteCategoryTarget ? `This will permanently delete "${deleteCategoryTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteCategory}
/>

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title={tab === 'categories' ? 'Delete selected categories?' : 'Delete selected subcategories?'}
    description={tab === 'categories'
        ? `This will permanently delete ${selectedCategoryIds.length} selected categories. Categories with subcategories or products cannot be deleted.`
        : `This will permanently delete ${selectedSubcategoryIds.length} selected subcategories. Subcategories with products cannot be deleted.`}
    onConfirm={() => runBulkAction('delete')}
/>

<ConfirmDialog
    bind:open={deleteSubcategoryOpen}
    title="Delete subcategory?"
    description={deleteSubcategoryTarget ? `This will permanently delete "${deleteSubcategoryTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteSubcategory}
/>
