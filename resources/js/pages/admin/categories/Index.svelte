<script module lang="ts">
    import { index } from '@/routes/admin/categories';

    export const layout = {
        breadcrumbs: [{ title: 'Categories', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { destroy, store } from '@/routes/admin/categories';

    type CategoryRow = {
        id: number;
        name: string;
        parent_id: number | null;
        is_active: boolean;
        children_count: number;
        products_count: number;
    };

    let { categories }: { categories: CategoryRow[] } = $props();

    function removeCategory(id: number) {
        if (confirm('Delete this category?')) {
            router.delete(destroy.url(id));
        }
    }
</script>

<AppHead title="Categories" />

<div class="grid gap-8 p-4 lg:grid-cols-[2fr_1fr]">
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
                            <td class="p-2">{category.children_count}</td>
                            <td class="p-2">{category.products_count}</td>
                            <td class="p-2 text-right">
                                <button class="text-red-600 underline" onclick={() => removeCategory(category.id)}>
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

                <div class="grid gap-2">
                    <Label for="parent_id">Parent category</Label>
                    <select
                        id="parent_id"
                        name="parent_id"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                    >
                        <option value="">None</option>
                        {#each categories as category (category.id)}
                            <option value={category.id}>{category.name}</option>
                        {/each}
                    </select>
                    <InputError message={errors.parent_id} />
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
