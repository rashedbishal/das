<script module lang="ts">
    import { index } from '@/routes/admin/brands';

    export const layout = {
        breadcrumbs: [{ title: 'Brands', href: index() }],
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
    import { destroy, store, update } from '@/routes/admin/brands';

    type BrandRow = {
        id: number;
        name: string;
        website_url: string | null;
        is_active: boolean;
        products_count: number;
    };

    let { brands }: { brands: BrandRow[] } = $props();

    let search = $state('');
    const filteredBrands = $derived(brands.filter((brand) => brand.name.toLowerCase().includes(search.toLowerCase())));

    let createOpen = $state(false);
    let editingBrand = $state<BrandRow | null>(null);
    let editOpen = $state(false);
    let deleteTarget = $state<BrandRow | null>(null);
    let deleteOpen = $state(false);

    function editBrand(brand: BrandRow) {
        editingBrand = brand;
        editOpen = true;
    }

    function askDeleteBrand(brand: BrandRow) {
        deleteTarget = brand;
        deleteOpen = true;
    }

    function confirmDeleteBrand() {
        if (deleteTarget) {
            router.delete(destroy.url(deleteTarget.id));
        }
    }
</script>

<AppHead title="Brands" />

<div class="p-4">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Brands</h1>
        <Button onclick={() => (createOpen = true)}>Create brand</Button>
    </div>

    <Input bind:value={search} placeholder="Search brands..." class="mb-4 max-w-sm" />

    <div class="hidden overflow-x-auto rounded-lg border md:block">
        <table class="w-full text-sm">
            <thead class="bg-muted/50 text-left">
                <tr>
                    <th class="p-2">Name</th>
                    <th class="p-2">Active</th>
                    <th class="p-2">Products</th>
                    <th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
                {#each filteredBrands as brand (brand.id)}
                    <tr class="border-t">
                        <td class="p-2">{brand.name}</td>
                        <td class="p-2">
                            <Badge variant={brand.is_active ? 'default' : 'secondary'}>
                                {brand.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                        </td>
                        <td class="p-2">{brand.products_count}</td>
                        <td class="p-2 text-right">
                            <button
                                class="mr-3 text-muted-foreground hover:text-primary"
                                aria-label="Edit {brand.name}"
                                onclick={() => editBrand(brand)}
                            >
                                <Pencil class="size-4" />
                            </button>
                            <button
                                class="text-muted-foreground hover:text-red-600"
                                aria-label="Delete {brand.name}"
                                onclick={() => askDeleteBrand(brand)}
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </td>
                    </tr>
                {:else}
                    <tr><td class="p-4 text-muted-foreground" colspan="4">No brands found.</td></tr>
                {/each}
            </tbody>
        </table>
    </div>

    <div class="grid gap-3 md:hidden">
        {#each filteredBrands as brand (brand.id)}
            <div class="rounded-lg border p-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium">{brand.name}</p>
                    <Badge variant={brand.is_active ? 'default' : 'secondary'}>
                        {brand.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                </div>
                <dl class="mt-2 grid grid-cols-2 gap-1 text-sm text-muted-foreground">
                    <dt>Products</dt>
                    <dd class="text-right text-foreground">{brand.products_count}</dd>
                </dl>
                <div class="mt-3 flex justify-end gap-4">
                    <button
                        class="text-muted-foreground hover:text-primary"
                        aria-label="Edit {brand.name}"
                        onclick={() => editBrand(brand)}
                    >
                        <Pencil class="size-4" />
                    </button>
                    <button
                        class="text-muted-foreground hover:text-red-600"
                        aria-label="Delete {brand.name}"
                        onclick={() => askDeleteBrand(brand)}
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>
        {:else}
            <p class="p-4 text-center text-muted-foreground">No brands found.</p>
        {/each}
    </div>
</div>

<Dialog bind:open={createOpen}>
    <DialogContent>
        <DialogTitle>Create brand</DialogTitle>
        <Form {...store.form()} class="space-y-4" resetOnSuccess onSuccess={() => (createOpen = false)}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" required />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="website_url">Website URL</Label>
                    <Input id="website_url" name="website_url" type="url" />
                    <InputError message={errors.website_url} />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked />
                    Active
                </label>

                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (createOpen = false)}>Cancel</Button>
                    <Button type="submit" disabled={processing}>Create brand</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<Dialog bind:open={editOpen}>
    <DialogContent>
        <DialogTitle>Edit brand</DialogTitle>
        {#if editingBrand}
            {@const brand = editingBrand}
            <Form {...update.form(brand.id)} class="space-y-4" onSuccess={() => (editOpen = false)}>
                {#snippet children({ errors, processing })}
                    <div class="grid gap-2">
                        <Label for="edit_brand_name">Name</Label>
                        <Input id="edit_brand_name" name="name" value={brand.name} required />
                        <InputError message={errors.name} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_brand_website_url">Website URL</Label>
                        <Input id="edit_brand_website_url" name="website_url" type="url" value={brand.website_url ?? ''} />
                        <InputError message={errors.website_url} />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" checked={brand.is_active} />
                        Active
                    </label>

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
    title="Delete brand?"
    description={deleteTarget ? `This will permanently delete "${deleteTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteBrand}
/>
