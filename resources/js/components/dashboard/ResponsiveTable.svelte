<script module lang="ts">
    export type Column = {
        key: string;
        label: string;
        /** Applied to the table cell only (e.g. `text-right`, `whitespace-nowrap`). */
        class?: string;
    };
</script>

<script lang="ts" generics="T">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        items,
        columns,
        key,
        cell,
        actions,
        selectedKeys,
        disabledKeys = [],
        onSelectionChange,
        empty = 'Nothing here yet.',
        class: className,
    }: {
        items: T[];
        /** The first column becomes the card title on narrow screens. */
        columns: Column[];
        key: (item: T) => string | number;
        cell: Snippet<[T, Column]>;
        actions?: Snippet<[T]>;
        selectedKeys?: Array<string | number>;
        disabledKeys?: Array<string | number>;
        onSelectionChange?: (keys: Array<string | number>) => void;
        empty?: string;
        class?: string;
    } = $props();

    const selectable = $derived(selectedKeys !== undefined && onSelectionChange !== undefined);
    const maxSelected = 100;
    const allSelected = $derived(
        items.some((item) => !disabledKeys.includes(key(item))) &&
            items.filter((item) => !disabledKeys.includes(key(item))).every((item) => selectedKeys?.includes(key(item))),
    );

    function toggleItem(item: T, checked: boolean) {
        if (!selectedKeys || !onSelectionChange) return;

        const itemKey = key(item);
        if (disabledKeys.includes(itemKey)) return;
        if (checked && selectedKeys.length >= maxSelected) return;
        onSelectionChange(
            checked
                ? [...selectedKeys, itemKey]
                : selectedKeys.filter((selectedKey) => selectedKey !== itemKey),
        );
    }

    function toggleAll(checked: boolean) {
        if (!selectedKeys || !onSelectionChange) return;

        const visibleKeys = items.map(key).filter((itemKey) => !disabledKeys.includes(itemKey));
        onSelectionChange(
            checked
                ? [...new Set([...selectedKeys, ...visibleKeys])].slice(0, maxSelected)
                : selectedKeys.filter((selectedKey) => !visibleKeys.includes(selectedKey)),
        );
    }
</script>

<!-- Switches on the container's width, not the viewport, so it also works inside split layouts. -->
<div class={cn('@container', className)}>
    <div class="hidden overflow-hidden rounded-lg border @xl:block">
        <table class="w-full text-sm">
            <thead class="bg-muted/50 text-left text-xs text-muted-foreground">
                <tr>
                    {#if selectable}
                        <th class="w-10 px-3 py-2.5">
                            <input
                                type="checkbox"
                                aria-label="Select all rows"
                                checked={allSelected}
                                disabled={
                                    !items.some((item) => !disabledKeys.includes(key(item))) ||
                                    (selectedKeys?.length === maxSelected && !allSelected)
                                }
                                onchange={(event) => toggleAll(event.currentTarget.checked)}
                                class="size-4 accent-primary"
                            />
                        </th>
                    {/if}
                    {#each columns as column (column.key)}
                        <th class={cn('px-3 py-2.5 font-medium', column.class)}>
                            {column.label}
                        </th>
                    {/each}
                    {#if actions}
                        <th class="px-3 py-2.5">
                            <span class="sr-only">Actions</span>
                        </th>
                    {/if}
                </tr>
            </thead>
            <tbody>
                {#each items as item (key(item))}
                    <tr class="border-t transition-colors hover:bg-muted/30">
                        {#if selectable}
                            <td class="w-10 px-3 py-2.5">
                                <input
                                    type="checkbox"
                                    aria-label="Select row"
                                    checked={selectedKeys?.includes(key(item)) ?? false}
                                    disabled={disabledKeys.includes(key(item)) ||
                                        (!(selectedKeys?.includes(key(item)) ?? false) &&
                                            (selectedKeys?.length ?? 0) >= maxSelected)}
                                    onchange={(event) => toggleItem(item, event.currentTarget.checked)}
                                    class="size-4 accent-primary"
                                />
                            </td>
                        {/if}
                        {#each columns as column (column.key)}
                            <td class={cn('px-3 py-2.5', column.class)}>
                                {@render cell(item, column)}
                            </td>
                        {/each}
                        {#if actions}
                            <td class="px-3 py-1.5">
                                <div class="flex justify-end gap-1">
                                    {@render actions(item)}
                                </div>
                            </td>
                        {/if}
                    </tr>
                {:else}
                    <tr>
                        <td
                            colspan={columns.length + (actions ? 1 : 0) + (selectable ? 1 : 0)}
                            class="px-3 py-8 text-center text-muted-foreground"
                        >
                            {empty}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>

    <ul class="grid gap-3 @xl:hidden">
        {#each items as item (key(item))}
            {@const [titleColumn, ...detailColumns] = columns}
            <!-- min-w-0: a long unbreakable title must not stretch the card past the screen. -->
            <li class="min-w-0 rounded-lg border bg-card p-4 text-sm">
                <div class="flex items-start gap-3">
                    {#if selectable}
                        <input
                            type="checkbox"
                            aria-label="Select row"
                            checked={selectedKeys?.includes(key(item)) ?? false}
                            disabled={disabledKeys.includes(key(item)) ||
                                (!(selectedKeys?.includes(key(item)) ?? false) &&
                                    (selectedKeys?.length ?? 0) >= maxSelected)}
                            onchange={(event) => toggleItem(item, event.currentTarget.checked)}
                            class="mt-0.5 size-4 shrink-0 accent-primary"
                        />
                    {/if}
                    <div class="min-w-0 font-medium break-words">
                        {@render cell(item, titleColumn)}
                    </div>
                </div>
                {#if detailColumns.length > 0}
                    <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2">
                        {#each detailColumns as column (column.key)}
                            <dt class="text-muted-foreground">
                                {column.label}
                            </dt>
                            <dd class="min-w-0 text-right break-words">
                                {@render cell(item, column)}
                            </dd>
                        {/each}
                    </dl>
                {/if}
                {#if actions}
                    <div
                        class="mt-3 flex flex-wrap justify-end gap-2 border-t pt-3 empty:hidden"
                    >
                        {@render actions(item)}
                    </div>
                {/if}
            </li>
        {:else}
            <li
                class="rounded-lg border p-6 text-center text-sm text-muted-foreground"
            >
                {empty}
            </li>
        {/each}
    </ul>
</div>
