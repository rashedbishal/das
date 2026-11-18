<script lang="ts">
    import X from '@lucide/svelte/icons/x';
    import { SvelteSet } from 'svelte/reactivity';
    import { untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import { Input } from '@/components/ui/input';

    type ProductOption = { id: number; name: string; sku: string };

    let {
        products,
        currentId,
        initial = [],
        errors,
    }: {
        products: ProductOption[];
        /** The product being edited, which can't be related to itself. */
        currentId?: number;
        initial?: number[];
        errors: Record<string, string>;
    } = $props();

    const selected = new SvelteSet<number>(untrack(() => initial));
    let search = $state('');

    const candidates = $derived(products.filter((product) => product.id !== currentId));
    const picked = $derived(candidates.filter((product) => selected.has(product.id)));
    const matches = $derived.by(() => {
        const term = search.trim().toLowerCase();

        return term === ''
            ? candidates
            : candidates.filter(
                  (product) =>
                      product.name.toLowerCase().includes(term) || product.sku.toLowerCase().includes(term),
              );
    });

    function toggle(id: number, checked: boolean) {
        if (checked) {
            selected.add(id);
        } else {
            selected.delete(id);
        }
    }

    const relatedError = $derived(
        errors.related_product_ids ??
            Object.entries(errors).find(([key]) => key.startsWith('related_product_ids.'))?.[1],
    );
</script>

<fieldset class="grid gap-2">
    <legend class="mb-1 text-sm font-medium">Related products</legend>
    <p class="-mt-1 text-xs text-muted-foreground">
        Shown under "related products" on the product page. If you pick none, products from the same category
        are shown instead.
    </p>

    <!-- The checkboxes below are only a view of the search results, so the picks are submitted from here. -->
    {#each picked as product (product.id)}
        <input type="hidden" name="related_product_ids[]" value={product.id} />
    {:else}
        <input type="hidden" name="related_product_ids" value="" />
    {/each}

    {#if picked.length > 0}
        <ul class="flex flex-wrap gap-1.5" aria-label="Selected related products">
            {#each picked as product (product.id)}
                <li class="flex max-w-full items-center gap-1 rounded-md border bg-muted py-0.5 pr-1 pl-2 text-xs">
                    <span class="min-w-0 truncate">{product.name}</span>
                    <button
                        type="button"
                        class="rounded-sm p-0.5 text-muted-foreground hover:text-foreground"
                        aria-label="Remove {product.name}"
                        onclick={() => selected.delete(product.id)}
                    >
                        <X class="size-3.5" />
                    </button>
                </li>
            {/each}
        </ul>
    {/if}

    <Input type="search" placeholder="Search by name or SKU" aria-label="Search products" bind:value={search} />
    <div class="grid max-h-56 gap-1 overflow-y-auto rounded-md border p-3 text-sm">
        {#each matches as product (product.id)}
            <label class="flex items-start gap-2">
                <input
                    type="checkbox"
                    class="mt-0.5"
                    checked={selected.has(product.id)}
                    onchange={(event) => toggle(product.id, event.currentTarget.checked)}
                />
                <span class="min-w-0 break-words">
                    {product.name}
                    <span class="font-mono text-xs text-muted-foreground">{product.sku}</span>
                </span>
            </label>
        {:else}
            <p class="text-muted-foreground">No products match.</p>
        {/each}
    </div>
    <InputError message={relatedError} />
</fieldset>
