<script module lang="ts">
    import { index } from '@/routes/admin/brands';

    export const layout = {
        breadcrumbs: [{ title: 'Brands', href: index() }],
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
    import { destroy, store } from '@/routes/admin/brands';

    type BrandRow = {
        id: number;
        name: string;
        is_active: boolean;
        products_count: number;
    };

    let { brands }: { brands: BrandRow[] } = $props();

    function removeBrand(id: number) {
        if (confirm('Delete this brand?')) {
            router.delete(destroy.url(id));
        }
    }
</script>

<AppHead title="Brands" />

<div class="grid gap-8 p-4 lg:grid-cols-[2fr_1fr]">
    <div>
        <h1 class="mb-4 text-xl font-semibold">Brands</h1>
        <div class="overflow-x-auto rounded-lg border">
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
                    {#each brands as brand (brand.id)}
                        <tr class="border-t">
                            <td class="p-2">{brand.name}</td>
                            <td class="p-2">
                                <Badge variant={brand.is_active ? 'default' : 'secondary'}>
                                    {brand.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </td>
                            <td class="p-2">{brand.products_count}</td>
                            <td class="p-2 text-right">
                                <button class="text-red-600 underline" onclick={() => removeBrand(brand.id)}>
                                    Delete
                                </button>
                            </td>
                        </tr>
                    {:else}
                        <tr><td class="p-4 text-muted-foreground" colspan="4">No brands yet.</td></tr>
                    {/each}
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <h2 class="mb-4 text-lg font-semibold">New brand</h2>
        <Form {...store.form()} class="space-y-4" resetOnSuccess>
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

                <Button type="submit" disabled={processing}>Create brand</Button>
            {/snippet}
        </Form>
    </div>
</div>
