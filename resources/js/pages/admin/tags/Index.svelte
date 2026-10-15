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

<div class="p-4">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Tags</h1>
        <Button onclick={() => (createOpen = true)}>Create tag</Button>
    </div>

    <Input bind:value={search} placeholder="Search tags..." class="mb-4 max-w-sm" />

    <div class="hidden overflow-x-auto rounded-lg border md:block">
        <table class="w-full text-sm">
            <thead class="bg-muted/50 text-left">
                <tr>
                    <th class="p-2">Name</th>
                    <th class="p-2">Products</th>
                    <th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
                {#each filteredTags as tag (tag.id)}
                    <tr class="border-t">
                        <td class="p-2">{tag.name}</td>
                        <td class="p-2">{tag.products_count}</td>
                        <td class="p-2 text-right">
                            <button
                                class="text-muted-foreground hover:text-red-600"
                                aria-label="Delete {tag.name}"
                                onclick={() => askDeleteTag(tag)}
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </td>
                    </tr>
                {:else}
                    <tr><td class="p-4 text-muted-foreground" colspan="3">No tags found.</td></tr>
                {/each}
            </tbody>
        </table>
    </div>

    <div class="grid gap-3 md:hidden">
        {#each filteredTags as tag (tag.id)}
            <div class="rounded-lg border p-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-medium">{tag.name}</p>
                    <button
                        class="text-muted-foreground hover:text-red-600"
                        aria-label="Delete {tag.name}"
                        onclick={() => askDeleteTag(tag)}
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">{tag.products_count} products</p>
            </div>
        {:else}
            <p class="p-4 text-center text-muted-foreground">No tags found.</p>
        {/each}
    </div>
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
