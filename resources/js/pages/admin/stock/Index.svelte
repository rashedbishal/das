<script module lang="ts">
    import { index } from '@/routes/admin/stock';

    export const layout = {
        breadcrumbs: [{ title: 'Stock Management', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import History from '@lucide/svelte/icons/history';
    import ImageIcon from '@lucide/svelte/icons/image';
    import Minus from '@lucide/svelte/icons/minus';
    import Plus from '@lucide/svelte/icons/plus';
    import { onDestroy, untrack } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button, buttonVariants } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { fieldLimits } from '@/lib/field-limits';
    import { history as stockHistory, index as stockIndex, store as storeStock } from '@/routes/admin/stock';

    type StockRow = {
        id: number;
        name: string;
        sku: string;
        status: string;
        stock_quantity: number;
        stock_status: string;
        image_url: string | null;
    };
    type Movement = {
        id: number;
        type: 'add' | 'remove';
        quantity: number;
        quantity_before: number;
        quantity_after: number;
        note: string | null;
        user: string | null;
        created_at: string | null;
    };
    type Paginated<T> = {
        data: T[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };

    let {
        products,
        filters,
        canAdjust,
    }: { products: Paginated<StockRow>; filters: { search: string }; canAdjust: boolean } = $props();

    const statusLabels: Record<string, string> = {
        in_stock: 'In stock',
        out_of_stock: 'Out of stock',
        backorder: 'Backorder',
        on_order: 'On order',
    };

    // Search runs on the server (name, SKU or model) shortly after typing stops.
    let search = $state(untrack(() => filters.search ?? ''));
    let searching = $state(false);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    function onSearchInput() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            const term = search.trim();
            router.get(stockIndex.url({ query: term ? { search: term } : {} }), {}, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['products', 'filters'],
                onStart: () => (searching = true),
                onFinish: () => (searching = false),
            });
        }, 250);
    }

    onDestroy(() => clearTimeout(searchTimer));

    // Add / remove stock dialog.
    let adjustOpen = $state(false);
    let adjustTarget = $state<StockRow | null>(null);
    let adjustType = $state<'add' | 'remove'>('add');
    let quantity = $state<number | null>(1);
    let note = $state('');
    let adjustErrors = $state<Record<string, string>>({});
    let adjusting = $state(false);

    const resultingStock = $derived.by(() => {
        if (!adjustTarget || !quantity || quantity < 1) return null;
        return adjustType === 'add' ? adjustTarget.stock_quantity + quantity : adjustTarget.stock_quantity - quantity;
    });

    function openAdjust(product: StockRow, type: 'add' | 'remove') {
        adjustTarget = product;
        adjustType = type;
        quantity = 1;
        note = '';
        adjustErrors = {};
        adjustOpen = true;
    }

    function submitAdjust(event: SubmitEvent) {
        event.preventDefault();
        if (!adjustTarget) return;

        router.post(
            storeStock.url(adjustTarget.id),
            { type: adjustType, quantity, note: note.trim() || null },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => (adjusting = true),
                onFinish: () => (adjusting = false),
                onSuccess: () => {
                    adjustOpen = false;
                    adjustErrors = {};
                },
                onError: (errors) => (adjustErrors = errors),
            },
        );
    }

    // History dialog.
    let historyOpen = $state(false);
    let historyTarget = $state<StockRow | null>(null);
    let movements = $state<Movement[]>([]);
    let historyLoading = $state(false);
    let historyError = $state<string | null>(null);

    async function openHistory(product: StockRow) {
        historyTarget = product;
        movements = [];
        historyError = null;
        historyLoading = true;
        historyOpen = true;

        try {
            const response = await fetch(stockHistory.url(product.id), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(String(response.status));
            const body = (await response.json()) as { data: Movement[] };
            if (historyTarget?.id === product.id) movements = body.data;
        } catch {
            if (historyTarget?.id === product.id) historyError = 'Could not load the stock history. Please try again.';
        } finally {
            if (historyTarget?.id === product.id) historyLoading = false;
        }
    }

    function formatDate(value: string | null): string {
        return value ? new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '';
    }
</script>

<AppHead title="Stock Management" />

<div class="w-full p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Stock Management</h1>
        <p class="text-sm text-muted-foreground">{products.total} products</p>
    </div>

    <div class="relative mb-4 sm:max-w-sm">
        <Input
            type="search"
            bind:value={search}
            oninput={onSearchInput}
            placeholder="Search by name, SKU, model..."
            aria-label="Search products"
            class="pr-9"
        />
        {#if searching}
            <Spinner class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground" />
        {/if}
    </div>

    <ResponsiveTable
        items={products.data}
        key={(product) => product.id}
        columns={[
            { key: 'name', label: 'Product' },
            { key: 'sku', label: 'SKU' },
            { key: 'stock_status', label: 'Stock status' },
            { key: 'stock', label: 'In stock', class: 'text-right' },
        ]}
        empty={search.trim() ? 'No products match your search.' : 'No products yet.'}
    >
        {#snippet cell(product, column)}
            {#if column.key === 'name'}
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted">
                        {#if product.image_url}
                            <img src={product.image_url} alt={product.name} class="size-full object-cover" loading="lazy" />
                        {:else}
                            <ImageIcon class="size-4 text-muted-foreground" />
                        {/if}
                    </div>
                    <div class="min-w-0">
                        <p class="break-words">{product.name}</p>
                        {#if product.status !== 'published'}
                            <p class="text-xs text-muted-foreground capitalize">{product.status}</p>
                        {/if}
                    </div>
                </div>
            {:else if column.key === 'sku'}
                <span class="font-mono text-xs">{product.sku}</span>
            {:else if column.key === 'stock_status'}
                <Badge variant={product.stock_status === 'out_of_stock' ? 'destructive' : 'outline'}>
                    {statusLabels[product.stock_status] ?? product.stock_status}
                </Badge>
            {:else}
                <span class="font-semibold tabular-nums">{product.stock_quantity.toLocaleString()}</span>
            {/if}
        {/snippet}
        {#snippet actions(product)}
            {#if canAdjust}
                <Button variant="outline" size="sm" onclick={() => openAdjust(product, 'add')}>
                    <Plus class="size-4" />
                    Add stock
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={product.stock_quantity === 0}
                    onclick={() => openAdjust(product, 'remove')}
                >
                    <Minus class="size-4" />
                    Remove stock
                </Button>
            {/if}
            <Button variant="ghost" size="sm" aria-label="Stock history for {product.name}" onclick={() => openHistory(product)}>
                <History class="size-4" />
            </Button>
        {/snippet}
    </ResponsiveTable>

    {#if products.last_page > 1}
        <div class="mt-4 flex flex-wrap items-center justify-center gap-4 text-sm">
            {#if products.prev_page_url}
                <Link href={products.prev_page_url} preserveScroll class={buttonVariants({ variant: 'ghost', size: 'sm' })}>Previous</Link>
            {/if}
            <span>Page {products.current_page} of {products.last_page}</span>
            {#if products.next_page_url}
                <Link href={products.next_page_url} preserveScroll class={buttonVariants({ variant: 'ghost', size: 'sm' })}>Next</Link>
            {/if}
        </div>
    {/if}
</div>

<Dialog bind:open={adjustOpen}>
    <DialogContent>
        <DialogTitle>{adjustType === 'add' ? 'Add stock' : 'Remove stock'}</DialogTitle>
        {#if adjustTarget}
            <form onsubmit={submitAdjust} class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">{adjustTarget.name}</span>, currently
                    {adjustTarget.stock_quantity.toLocaleString()} in stock.
                </p>

                <div class="grid gap-2">
                    <Label for="stock-quantity">Quantity to {adjustType}</Label>
                    <Input
                        id="stock-quantity"
                        type="number"
                        required
                        min="1"
                        max={adjustType === 'remove' ? adjustTarget.stock_quantity : fieldLimits.number.stock_quantity}
                        step="1"
                        bind:value={quantity}
                    />
                    <InputError message={adjustErrors.quantity} />
                </div>

                <div class="grid gap-2">
                    <Label for="stock-note">Note (optional)</Label>
                    <Input
                        id="stock-note"
                        bind:value={note}
                        maxlength={fieldLimits.stock.note}
                        placeholder={adjustType === 'add' ? 'e.g. New shipment received' : 'e.g. Sold to clinic, damaged'}
                    />
                    <InputError message={adjustErrors.note} />
                </div>

                {#if resultingStock !== null && resultingStock >= 0}
                    <p class="text-sm">
                        Stock after this change: <span class="font-semibold tabular-nums">{resultingStock.toLocaleString()}</span>
                    </p>
                {/if}
                <InputError message={adjustErrors.type} />

                <DialogFooter>
                    <Button type="button" variant="outline" onclick={() => (adjustOpen = false)}>Cancel</Button>
                    <Button type="submit" variant={adjustType === 'remove' ? 'destructive' : 'default'} disabled={adjusting}>
                        {#if adjusting}<Spinner />{/if}
                        {adjustType === 'add' ? 'Add stock' : 'Remove stock'}
                    </Button>
                </DialogFooter>
            </form>
        {/if}
    </DialogContent>
</Dialog>

<Dialog bind:open={historyOpen}>
    <DialogContent class="max-h-[85vh] max-w-xl overflow-y-auto">
        <DialogTitle>Stock history</DialogTitle>
        {#if historyTarget}
            <p class="text-sm text-muted-foreground">{historyTarget.name}</p>
        {/if}

        {#if historyLoading}
            <div class="flex justify-center py-6"><Spinner /></div>
        {:else if historyError}
            <p class="text-sm text-destructive">{historyError}</p>
        {:else if movements.length === 0}
            <p class="py-4 text-sm text-muted-foreground">No stock changes recorded yet.</p>
        {:else}
            <ul class="divide-y">
                {#each movements as movement (movement.id)}
                    <li class="flex items-start justify-between gap-3 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium {movement.type === 'add' ? 'text-emerald-600 dark:text-emerald-400' : 'text-destructive'}">
                                {movement.type === 'add' ? '+' : '−'}{movement.quantity.toLocaleString()}
                                <span class="font-normal text-muted-foreground">
                                    ({movement.quantity_before.toLocaleString()} → {movement.quantity_after.toLocaleString()})
                                </span>
                            </p>
                            {#if movement.note}
                                <p class="break-words">{movement.note}</p>
                            {/if}
                            <p class="text-xs text-muted-foreground">{movement.user ?? 'Deleted user'}</p>
                        </div>
                        <time class="shrink-0 text-xs text-muted-foreground" datetime={movement.created_at}>
                            {formatDate(movement.created_at)}
                        </time>
                    </li>
                {/each}
            </ul>
            {#if movements.length >= 50}
                <p class="pt-2 text-xs text-muted-foreground">Showing the latest 50 changes.</p>
            {/if}
        {/if}
    </DialogContent>
</Dialog>
