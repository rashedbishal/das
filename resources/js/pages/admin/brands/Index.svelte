<script module lang="ts">
    import { index } from '@/routes/admin/brands';

    export const layout = {
        breadcrumbs: [{ title: 'Brands', href: index() }],
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
    import { bulk as bulkBrands, destroy, store, update } from '@/routes/admin/brands';

    type BrandRow = {
        id: number;
        name: string;
        slug: string;
        logo: string | null;
        description: string | null;
        seo_title: string | null;
        seo_description: string | null;
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
    let selectedIds = $state<number[]>([]);
    let bulkDeleteOpen = $state(false);
    let bulkError = $state<string | null>(null);

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
            router.delete(destroy.url(deleteTarget.id), {
                onError: (errors) => toast.error(Object.values(errors).join(' ')),
            });
        }
    }

    function runBulkAction(action: 'delete' | 'activate' | 'deactivate') {
        router.post(
            bulkBrands.url(),
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

    const canManage = $derived(can('brands.manage'));
</script>

<AppHead title="Brands" />

{#snippet rowActions(brand: BrandRow)}
    <Button variant="ghost" size="sm" aria-label="Edit {brand.name}" onclick={() => editBrand(brand)}>
        <Pencil class="size-4" />
    </Button>
    <Button
        variant="ghost"
        size="sm"
        class="text-destructive hover:text-destructive"
        aria-label="Delete {brand.name}"
        onclick={() => askDeleteBrand(brand)}
    >
        <Trash2 class="size-4" />
    </Button>
{/snippet}

<div class="w-full p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Brands</h1>
        {#if canManage}
            <Button onclick={() => (createOpen = true)}>Create brand</Button>
        {/if}
    </div>

    <Input bind:value={search} placeholder="Search brands..." class="mb-4 sm:max-w-sm" />

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
        items={filteredBrands}
        key={(brand) => brand.id}
        columns={[
            { key: 'name', label: 'Name' },
            { key: 'active', label: 'Active' },
            { key: 'products', label: 'Products' },
        ]}
        empty="No brands found."
        selectedKeys={canManage ? selectedIds : undefined}
        onSelectionChange={canManage ? (keys) => (selectedIds = keys.map(Number)) : undefined}
        actions={canManage ? rowActions : undefined}
    >
        {#snippet cell(brand, column)}
            {#if column.key === 'name'}
                {brand.name}
            {:else if column.key === 'active'}
                <Badge variant={brand.is_active ? 'default' : 'secondary'}>
                    {brand.is_active ? 'Active' : 'Inactive'}
                </Badge>
            {:else}
                {brand.products_count}
            {/if}
        {/snippet}
    </ResponsiveTable>
</div>

<Dialog bind:open={createOpen}>
    <DialogContent class="max-h-[85vh] overflow-y-auto">
        <DialogTitle>Create brand</DialogTitle>
        <Form {...store.form()} class="space-y-4" resetOnSuccess onSuccess={() => (createOpen = false)}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <LimitedInput id="name" name="name" required limit={fieldLimits.brand.name} />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="website_url">Website URL</Label>
                    <LimitedInput id="website_url" name="website_url" type="url" limit={fieldLimits.brand.website_url} />
                    <InputError message={errors.website_url} />
                </div>

                <div class="grid gap-2">
                    <Label for="create_brand_description">Description</Label>
                    <LimitedTextarea
                        id="create_brand_description"
                        name="description"
                        rows={3}
                        limit={fieldLimits.brand.description}
                        value={''}
                        placeholder="Shown on the brand page: who they are and what they make."
                    />
                    <InputError message={errors.description} />
                </div>

                <div class="grid gap-2">
                    <Label for="create_brand_logo">Logo URL</Label>
                    <LimitedInput id="create_brand_logo" name="logo" type="url" limit={fieldLimits.brand.logo} value={''} />
                    <InputError message={errors.logo} />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked />
                    Active
                </label>

                <SeoFields
                    idPrefix="create_brand"
                    titleLimit={fieldLimits.brand.seo_title}
                    descriptionLimit={fieldLimits.brand.seo_description}
                    {errors}
                    pathFor={(form) => `/brands/${previewSlug(form.name ?? '')}`}
                    content={{ field: 'description', min: 120, label: 'Description of at least 120 characters' }}
                />

                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (createOpen = false)}>Cancel</Button>
                    <Button type="submit" disabled={processing}>Create brand</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<Dialog bind:open={editOpen}>
    <DialogContent class="max-h-[85vh] overflow-y-auto">
        <DialogTitle>Edit brand</DialogTitle>
        {#if editingBrand}
            {@const brand = editingBrand}
            <Form {...update.form(brand.id)} class="space-y-4" onSuccess={() => (editOpen = false)}>
                {#snippet children({ errors, processing })}
                    <div class="grid gap-2">
                        <Label for="edit_brand_name">Name</Label>
                        <LimitedInput id="edit_brand_name" name="name" value={brand.name} required limit={fieldLimits.brand.name} />
                        <InputError message={errors.name} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_brand_website_url">Website URL</Label>
                        <LimitedInput
                            id="edit_brand_website_url"
                            name="website_url"
                            type="url"
                            value={brand.website_url ?? ''}
                            limit={fieldLimits.brand.website_url}
                        />
                        <InputError message={errors.website_url} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_brand_description">Description</Label>
                        <LimitedTextarea
                            id="edit_brand_description"
                            name="description"
                            rows={3}
                            limit={fieldLimits.brand.description}
                            value={brand.description ?? ''}
                            placeholder="Shown on the brand page: who they are and what they make."
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_brand_logo">Logo URL</Label>
                        <LimitedInput id="edit_brand_logo" name="logo" type="url" limit={fieldLimits.brand.logo} value={brand.logo ?? ''} />
                        <InputError message={errors.logo} />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" checked={brand.is_active} />
                        Active
                    </label>

                    <SeoFields
                        idPrefix="edit_brand"
                        titleLimit={fieldLimits.brand.seo_title}
                        descriptionLimit={fieldLimits.brand.seo_description}
                        values={brand}
                        {errors}
                        pathFor={() => `/brands/${brand.slug}`}
                        content={{ field: 'description', min: 120, label: 'Description of at least 120 characters' }}
                        checks={[{ label: 'Has a logo', ok: !!brand.logo, hint: 'Shown on the brand page and when shared.' }]}
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
    title="Delete brand?"
    description={deleteTarget ? `This will permanently delete "${deleteTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteBrand}
/>

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title="Delete selected brands?"
    description={`This will permanently delete ${selectedIds.length} selected brands. Brands with products cannot be deleted.`}
    onConfirm={() => runBulkAction('delete')}
/>
