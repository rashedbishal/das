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
        empty = 'Nothing here yet.',
        class: className,
    }: {
        items: T[];
        /** The first column becomes the card title on narrow screens. */
        columns: Column[];
        key: (item: T) => string | number;
        cell: Snippet<[T, Column]>;
        actions?: Snippet<[T]>;
        empty?: string;
        class?: string;
    } = $props();
</script>

<!-- Switches on the container's width, not the viewport, so it also works inside split layouts. -->
<div class={cn('@container', className)}>
    <div class="hidden overflow-hidden rounded-lg border @xl:block">
        <table class="w-full text-sm">
            <thead class="bg-muted/50 text-left text-xs text-muted-foreground">
                <tr>
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
                            colspan={columns.length + (actions ? 1 : 0)}
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
            <li class="rounded-lg border bg-card p-4 text-sm">
                <div class="font-medium break-words">
                    {@render cell(item, titleColumn)}
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
