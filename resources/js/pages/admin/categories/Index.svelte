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
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { destroy, store, update } from '@/routes/admin/categories';
    import { destroy as destroySubcategory, store as storeSubcategory, update as updateSubcategory } from '@/routes/admin/subcategories';

    type CategoryRow = {
        id: number;
        name: string;
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
        is_active: boolean;
        sort_order: number;
        products_count: number;
    };

    let { categories, subcategories }: { categories: CategoryRow[]; subcategories: SubcategoryRow[] } = $props();

    let tab = $state<'categories' | 'subcategories'>('categories');

    let categorySearch = $state('');
    let subcategorySearch = $state('');

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
</script>

<AppHead title="Category Management" />

<div class="p-4">
    <div class="mb-6 flex gap-2 border-b">
        {#each [['categories', 'Categories'], ['subcategories', 'Subcategories']] as [value, label] (value)}
            <button
                class="border-b-2 px-3 py-2 text-sm {tab === value ? 'border-primary font-medium' : 'border-transparent text-muted-foreground'}"
                onclick={() => (tab = value as typeof tab)}
            >
                {label}
            </button>
        {/each}
    </div>

    {#if tab === 'categories'}
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Categories</h1>
            <Button onclick={() => (createCategoryOpen = true)}>Create category</Button>
        </div>

        <Input bind:value={categorySearch} placeholder="Search categories..." class="mb-4 max-w-sm" />

        <div class="hidden overflow-x-auto rounded-lg border md:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-2">Name</th>
                        <th class="p-2">Active</th>
                        <th class="p-2">Subcategories</th>
                        <th class="p-2">Products</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    {#each filteredCategories as category (category.id)}
                        <tr class="border-t">
                            <td class="p-2">{category.name}</td>
                            <td class="p-2">
                                <Badge variant={category.is_active ? 'default' : 'secondary'}>
                                    {category.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </td>
                            <td class="p-2">{category.subcategories_count}</td>
                            <td class="p-2">{category.products_count}</td>
                            <td class="p-2 text-right whitespace-nowrap">
                                <button
                                    class="mr-3 text-muted-foreground hover:text-primary"
                                    aria-label="Edit {category.name}"
                                    onclick={() => editCategory(category)}
                                >
                                    <Pencil class="size-4" />
                                </button>
                                <button
                                    class="text-muted-foreground hover:text-red-600"
                                    aria-label="Delete {category.name}"
                                    onclick={() => askDeleteCategory(category)}
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </td>
                        </tr>
                    {:else}
                        <tr><td class="p-4 text-muted-foreground" colspan="5">No categories found.</td></tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            {#each filteredCategories as category (category.id)}
                <div class="rounded-lg border p-3">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-medium">{category.name}</p>
                        <Badge variant={category.is_active ? 'default' : 'secondary'}>
                            {category.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    </div>
                    <dl class="mt-2 grid grid-cols-2 gap-1 text-sm text-muted-foreground">
                        <dt>Subcategories</dt>
                        <dd class="text-right text-foreground">{category.subcategories_count}</dd>
                        <dt>Products</dt>
                        <dd class="text-right text-foreground">{category.products_count}</dd>
                    </dl>
                    <div class="mt-3 flex justify-end gap-4">
                        <button
                            class="text-muted-foreground hover:text-primary"
                            aria-label="Edit {category.name}"
                            onclick={() => editCategory(category)}
                        >
                            <Pencil class="size-4" />
                        </button>
                        <button
                            class="text-muted-foreground hover:text-red-600"
                            aria-label="Delete {category.name}"
                            onclick={() => askDeleteCategory(category)}
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </div>
            {:else}
                <p class="p-4 text-center text-muted-foreground">No categories found.</p>
            {/each}
        </div>
    {:else if tab === 'subcategories'}
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Subcategories</h1>
            <Button onclick={() => (createSubcategoryOpen = true)}>Create subcategory</Button>
        </div>

        <Input bind:value={subcategorySearch} placeholder="Search subcategories..." class="mb-4 max-w-sm" />

        <div class="hidden overflow-x-auto rounded-lg border md:block">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-2">Name</th>
                        <th class="p-2">Category</th>
                        <th class="p-2">Active</th>
                        <th class="p-2">Products</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    {#each filteredSubcategories as subcategory (subcategory.id)}
                        <tr class="border-t">
                            <td class="p-2">{subcategory.name}</td>
                            <td class="p-2 text-muted-foreground">{subcategory.category.name}</td>
                            <td class="p-2">
                                <Badge variant={subcategory.is_active ? 'default' : 'secondary'}>
                                    {subcategory.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </td>
                            <td class="p-2">{subcategory.products_count}</td>
                            <td class="p-2 text-right whitespace-nowrap">
                                <button
                                    class="mr-3 text-muted-foreground hover:text-primary"
                                    aria-label="Edit {subcategory.name}"
                                    onclick={() => editSubcategory(subcategory)}
                                >
                                    <Pencil class="size-4" />
                                </button>
                                <button
                                    class="text-muted-foreground hover:text-red-600"
                                    aria-label="Delete {subcategory.name}"
                                    onclick={() => askDeleteSubcategory(subcategory)}
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </td>
                        </tr>
                    {:else}
                        <tr><td class="p-4 text-muted-foreground" colspan="5">No subcategories found.</td></tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            {#each filteredSubcategories as subcategory (subcategory.id)}
                <div class="rounded-lg border p-3">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-medium">{subcategory.name}</p>
                        <Badge variant={subcategory.is_active ? 'default' : 'secondary'}>
                            {subcategory.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    </div>
                    <dl class="mt-2 grid grid-cols-2 gap-1 text-sm text-muted-foreground">
                        <dt>Category</dt>
                        <dd class="text-right text-foreground">{subcategory.category.name}</dd>
                        <dt>Products</dt>
                        <dd class="text-right text-foreground">{subcategory.products_count}</dd>
                    </dl>
                    <div class="mt-3 flex justify-end gap-4">
                        <button
                            class="text-muted-foreground hover:text-primary"
                            aria-label="Edit {subcategory.name}"
                            onclick={() => editSubcategory(subcategory)}
                        >
                            <Pencil class="size-4" />
                        </button>
                        <button
                            class="text-muted-foreground hover:text-red-600"
                            aria-label="Delete {subcategory.name}"
                            onclick={() => askDeleteSubcategory(subcategory)}
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </div>
            {:else}
                <p class="p-4 text-center text-muted-foreground">No subcategories found.</p>
            {/each}
        </div>
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

                <label class="flex items-center gap-2 text-sm">
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

                    <label class="flex items-center gap-2 text-sm">
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
                    <select
                        id="subcategory_category_id"
                        name="category_id"
                        required
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                    >
                        <option value="" disabled selected>Select a category</option>
                        {#each categories as category (category.id)}
                            <option value={category.id}>{category.name}</option>
                        {/each}
                    </select>
                    <InputError message={errors.category_id} />
                </div>

                <div class="grid gap-2">
                    <Label for="subcategory_name">Name</Label>
                    <Input id="subcategory_name" name="name" required />
                    <InputError message={errors.name} />
                </div>

                <label class="flex items-center gap-2 text-sm">
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
                        <select
                            id="edit_subcategory_category_id"
                            name="category_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                        >
                            {#each categories as category (category.id)}
                                <option value={category.id} selected={category.id === subcategory.category_id}>
                                    {category.name}
                                </option>
                            {/each}
                        </select>
                        <InputError message={errors.category_id} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_subcategory_name">Name</Label>
                        <Input id="edit_subcategory_name" name="name" value={subcategory.name} required />
                        <InputError message={errors.name} />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
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
    bind:open={deleteSubcategoryOpen}
    title="Delete subcategory?"
    description={deleteSubcategoryTarget ? `This will permanently delete "${deleteSubcategoryTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteSubcategory}
/>
