<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import ChevronsUpDown from '@lucide/svelte/icons/chevrons-up-down';
    import ImageIcon from '@lucide/svelte/icons/image';
    import X from '@lucide/svelte/icons/x';
    import { Combobox } from 'bits-ui';
    import { onDestroy, untrack } from 'svelte';
    import { fetchProducts, type ProductCard } from '@/data/catalogue';

    /**
     * Searchable product picker for the enquiry form: type to search the whole
     * catalogue (name, SKU, model) instead of scrolling a long list. Submits the
     * chosen product's name as `name`; empty means a general enquiry.
     */
    let {
        name,
        value = '',
        id,
    }: {
        name: string;
        /** Preselected product name (e.g. from a product page). */
        value?: string;
        id?: string;
    } = $props();

    const GENERAL = '';
    const SEARCH_DELAY_MS = 200;
    const RESULT_LIMIT = 20;

    let selected = $state(untrack(() => value));
    let open = $state(false);
    let query = $state('');
    let results = $state<ProductCard[]>([]);
    let loading = $state(false);
    let failed = $state(false);
    let latestRequest = 0;
    let timer: ReturnType<typeof setTimeout> | undefined;

    function search(term: string) {
        clearTimeout(timer);
        const request = ++latestRequest;
        loading = true;
        failed = false;

        timer = setTimeout(() => {
            fetchProducts({ q: term.trim() || undefined }, 1, RESULT_LIMIT)
                .then((page) => {
                    if (request === latestRequest) results = page.items;
                })
                .catch(() => {
                    if (request === latestRequest) failed = true;
                })
                .finally(() => {
                    if (request === latestRequest) loading = false;
                });
        }, term ? SEARCH_DELAY_MS : 0);
    }

    onDestroy(() => clearTimeout(timer));

    function onOpenChange(next: boolean) {
        if (next) {
            query = '';
            search('');
        }
    }

    function clear() {
        selected = GENERAL;
        query = '';
    }

    const inputLabel = $derived(selected || '');
</script>

<Combobox.Root
    type="single"
    {name}
    bind:value={selected}
    bind:open
    {onOpenChange}
    inputValue={open ? query : inputLabel}
>
    <div class="relative">
        <Combobox.Input
            {id}
            class="field pr-16"
            placeholder="General equipment enquiry: type to search products"
            aria-label="Product of interest"
            autocomplete="off"
            oninput={(event) => {
                query = event.currentTarget.value;
                search(query);
                open = true;
            }}
        />
        {#if selected}
            <button
                type="button"
                class="absolute top-1/2 right-9 -translate-y-1/2 rounded p-1 text-muted-foreground hover:text-foreground"
                aria-label="Clear product"
                onclick={clear}
            >
                <X class="size-3.5" />
            </button>
        {/if}
        <Combobox.Trigger
            class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-1 text-muted-foreground hover:text-foreground"
            aria-label="Show products"
        >
            <ChevronsUpDown class="size-4" />
        </Combobox.Trigger>
    </div>

    <Combobox.Portal>
        <Combobox.Content
            sideOffset={4}
            class="z-[60] max-h-72 w-[var(--bits-floating-anchor-width)] min-w-64 overflow-y-auto rounded-md border bg-background p-1 shadow-lg"
        >
            <Combobox.Item
                value={GENERAL}
                label="General equipment enquiry"
                class="flex cursor-pointer items-center gap-2 rounded px-2 py-2 text-xs data-highlighted:bg-accent"
            >
                {#snippet children({ selected: isSelected })}
                    <span class="flex-1">General equipment enquiry</span>
                    {#if isSelected}<Check class="size-3.5 text-primary" />{/if}
                {/snippet}
            </Combobox.Item>

            {#each results as product (product.id)}
                <Combobox.Item
                    value={product.name}
                    label={product.name}
                    class="flex cursor-pointer items-center gap-2.5 rounded px-2 py-1.5 text-xs data-highlighted:bg-accent"
                >
                    {#snippet children({ selected: isSelected })}
                        <span class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded bg-secondary">
                            {#if product.image}
                                <img src={product.image} alt="" class="size-full object-contain" loading="lazy" />
                            {:else}
                                <ImageIcon class="size-3.5 text-muted-foreground" />
                            {/if}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">{product.name}</span>
                            {#if product.brand}
                                <span class="block truncate text-[10px] text-muted-foreground">{product.brand.name}</span>
                            {/if}
                        </span>
                        {#if isSelected}<Check class="size-3.5 shrink-0 text-primary" />{/if}
                    {/snippet}
                </Combobox.Item>
            {/each}

            {#if loading && results.length === 0}
                <p class="px-2 py-3 text-xs text-muted-foreground">Searching…</p>
            {:else if failed}
                <p class="px-2 py-3 text-xs text-muted-foreground">
                    Couldn’t load products. You can still send a general enquiry.
                </p>
            {:else if !loading && results.length === 0}
                <p class="px-2 py-3 text-xs text-muted-foreground">No products match “{query}”.</p>
            {:else if results.length === RESULT_LIMIT}
                <p class="px-2 pt-2 pb-1 text-[10px] text-muted-foreground">Showing the first {RESULT_LIMIT}. Type more to narrow down.</p>
            {/if}
        </Combobox.Content>
    </Combobox.Portal>
</Combobox.Root>
