<script module lang="ts">
    import { index } from '@/routes/admin/tags';

    export const layout = {
        breadcrumbs: [{ title: 'Tags', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import BulkActions from '@/components/dashboard/BulkActions.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import LimitedInput from '@/components/dashboard/LimitedInput.svelte';
    import { fieldLimits } from '@/lib/field-limits';
    import { Label } from '@/components/ui/label';
    import { bulk as bulkTags, destroy, store } from '@/routes/admin/tags';

    type TagRow = { id: number; name: string; products_count: number };

    let { tags }: { tags: TagRow[] } = $props();

    let search = $state('');
    const filteredTags = $derived(tags.filter((tag) => tag.name.toLowerCase().includes(search.toLowerCase())));

    let createOpen = $state(false);
    let deleteTarget = $state<TagRow | null>(null);
    let deleteOpen = $state(false);
    let selectedIds = $state<number[]>([]);
    let bulkDeleteOpen = $state(false);
    let bulkError = $state<string | null>(null);

    function askDeleteTag(tag: TagRow) {
        deleteTarget = tag;
        deleteOpen = true;
    }

    function confirmDeleteTag() {
        if (deleteTarget) {
            router.delete(destroy.url(deleteTarget.id));
        }
    }

    function runBulkDelete() {
        router.post(
            bulkTags.url(),
            { ids: selectedIds, action: 'delete' },
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

<AppHead title="Tags" />

<div class="w-full p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Tags</h1>
        <Button onclick={() => (createOpen = true)}>Create tag</Button>
    </div>

    <Input bind:value={search} placeholder="Search tags..." class="mb-4 sm:max-w-sm" />

    <BulkActions
        count={selectedIds.length}
        error={bulkError}
        onClear={() => {
            selectedIds = [];
            bulkError = null;
        }}
    >
        {#snippet children()}
            <Button size="sm" variant="destructive" onclick={() => (bulkDeleteOpen = true)}>Delete</Button>
        {/snippet}
    </BulkActions>

    <ResponsiveTable
        items={filteredTags}
        key={(tag) => tag.id}
        columns={[
            { key: 'name', label: 'Name' },
            { key: 'products', label: 'Products', class: 'text-right' },
        ]}
        empty="No tags found."
        selectedKeys={selectedIds}
        onSelectionChange={(keys) => (selectedIds = keys.map(Number))}
    >
        {#snippet cell(tag, column)}
            {#if column.key === 'name'}
                {tag.name}
            {:else}
                {tag.products_count}
            {/if}
        {/snippet}
        {#snippet actions(tag)}
            <Button
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive"
                aria-label="Delete {tag.name}"
                onclick={() => askDeleteTag(tag)}
            >
                <Trash2 class="size-4" />
            </Button>
        {/snippet}
    </ResponsiveTable>
</div>

<Dialog bind:open={createOpen}>
    <DialogContent>
        <DialogTitle>Create tag</DialogTitle>
        <Form {...store.form()} class="space-y-4" resetOnSuccess onSuccess={() => (createOpen = false)}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <LimitedInput id="name" name="name" required limit={fieldLimits.tag.name} />
                    <InputError message={errors.name} />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (createOpen = false)}>Cancel</Button>
                    <Button type="submit" disabled={processing}>Create tag</Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={deleteOpen}
    title="Delete tag?"
    description={deleteTarget ? `This will permanently delete "${deleteTarget.name}". This cannot be undone.` : ''}
    onConfirm={confirmDeleteTag}
/>

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title="Delete selected tags?"
    description={`This will permanently delete ${selectedIds.length} selected tags. Tags assigned to products cannot be deleted.`}
    onConfirm={runBulkDelete}
/>
