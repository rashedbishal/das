<script module lang="ts">
    import { index } from '@/routes/admin/tags';

    export const layout = {
        breadcrumbs: [{ title: 'Tags', href: index() }],
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
    import { destroy, store } from '@/routes/admin/tags';

    type TagRow = { id: number; name: string; products_count: number };

    let { tags }: { tags: TagRow[] } = $props();

    function removeTag(id: number) {
        if (confirm('Delete this tag?')) {
            router.delete(destroy.url(id));
        }
    }
</script>

<AppHead title="Tags" />

<div class="grid gap-8 p-4 lg:grid-cols-[2fr_1fr]">
    <div>
        <h1 class="mb-4 text-xl font-semibold">Tags</h1>
        <div class="flex flex-wrap gap-2">
            {#each tags as tag (tag.id)}
                <Badge variant="outline" class="gap-2">
                    {tag.name} ({tag.products_count})
                    <button class="text-red-600" onclick={() => removeTag(tag.id)}>&times;</button>
                </Badge>
            {:else}
                <p class="text-muted-foreground">No tags yet.</p>
            {/each}
        </div>
    </div>

    <div>
        <h2 class="mb-4 text-lg font-semibold">New tag</h2>
        <Form {...store.form()} class="space-y-4" resetOnSuccess>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" required />
                    <InputError message={errors.name} />
                </div>

                <Button type="submit" disabled={processing}>Create tag</Button>
            {/snippet}
        </Form>
    </div>
</div>
