<script module lang="ts">
    import { index } from '@/routes/admin/categories';

    export const layout = {
        breadcrumbs: [{ title: 'Category Management', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
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

    let editingCategory = $state<CategoryRow | null>(null);
    let categoryDialogOpen = $state(false);

    let editingSubcategory = $state<SubcategoryRow | null>(null);
    let subcategoryDialogOpen = $state(false);

    function editCategory(category: CategoryRow) {
        editingCategory = category;
        categoryDialogOpen = true;
    }

    function removeCategory(id: number) {
        if (confirm('Delete this category?')) {
            router.delete(destroy.url(id));
        }
    }

    function editSubcategory(subcategory: SubcategoryRow) {
        editingSubcategory = subcategory;
        subcategoryDialogOpen = true;
    }

    function removeSubcategory(id: number) {
        if (confirm('Delete this subcategory?')) {
            router.delete(destroySubcategory.url(id));
        }
    }
</script>

<AppHead title="Category Management" />

<div class="grid gap-8 p-4 lg:grid-cols-2">
    <div class="space-y-8">
        <div>
            <h1 class="mb-4 text-xl font-semibold">Categories</h1>
            <div class="overflow-x-auto rounded-lg border">
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
                        {#each categories as category (category.id)}
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
                                    <button class="underline" onclick={() => editCategory(category)}>Edit</button>
                                    <button class="ml-3 text-red-600 underline" onclick={() => removeCategory(category.id)}>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        {:else}
                            <tr><td class="p-4 text-muted-foreground" colspan="5">No categories yet.</td></tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="mb-4 text-lg font-semibold">New category</h2>
            <Form {...store.form()} class="space-y-4" resetOnSuccess>
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

                    <Button type="submit" disabled={processing}>Create category</Button>
                {/snippet}
            </Form>
        </div>
    </div>

    <div class="space-y-8">
        <div>
            <h1 class="mb-4 text-xl font-semibold">Subcategories</h1>
            <div class="overflow-x-auto rounded-lg border">
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
                        {#each subcategories as subcategory (subcategory.id)}
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
                                    <button class="underline" onclick={() => editSubcategory(subcategory)}>Edit</button>
                                    <button class="ml-3 text-red-600 underline" onclick={() => removeSubcategory(subcategory.id)}>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        {:else}
                            <tr><td class="p-4 text-muted-foreground" colspan="5">No subcategories yet.</td></tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="mb-4 text-lg font-semibold">New subcategory</h2>
            <Form {...storeSubcategory.form()} class="space-y-4" resetOnSuccess>
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

                    <Button type="submit" disabled={processing}>Create subcategory</Button>
                {/snippet}
            </Form>
        </div>
    </div>
</div>

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
                        <Button type="submit" disabled={processing}>Save</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
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
                        <Button type="submit" disabled={processing}>Save</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>
