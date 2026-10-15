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
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { destroy, store } from '@/routes/admin/tags';

    type TagRow = { id: number; name: string; products_count: number };

    let { tags }: { tags: TagRow[] } = $props();

    let search = $state('');
    const filteredTags = $derived(tags.filter((tag) => tag.name.toLowerCase().includes(search.toLowerCase())));

    let createOpen = $state(false);
    let deleteTarget = $state<TagRow | null>(null);
    let deleteOpen = $state(false);

    function askDeleteTag(tag: TagRow) {
        deleteTarget = tag;
        deleteOpen = true;
    }

    function confirmDeleteTag() {
        if (deleteTarget) {
            router.delete(destroy.url(deleteTarget.id));
        }
    }
</script>

<AppHead title="Tags" />

<div class="w-full p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Tags</h1>
        <Button onclick={() => (createOpen = true)}>Create tag</Button>
    </div>

    <Input bind:value={search} placeholder="Search tags..." class="mb-4 sm:max-w-sm" />

    <ResponsiveTable
        items={filteredTags}
        key={(tag) => tag.id}
        columns={[
            { key: 'name', label: 'Name' },
            { key: 'products', label: 'Products', class: 'text-right' },
        ]}
        empty="No tags found."
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
                    <Input id="name" name="name" required />
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
