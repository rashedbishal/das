<script lang="ts">
    import ArrowDown from '@lucide/svelte/icons/arrow-down';
    import ArrowUp from '@lucide/svelte/icons/arrow-up';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import { Button } from '@/components/ui/button';

    /** Move up / move down / remove buttons for one row of a repeatable list. */
    let {
        index,
        count,
        label,
        onmove,
        onremove,
    }: {
        index: number;
        count: number;
        /** What a row is called in button labels, e.g. "specification". */
        label: string;
        onmove: (offset: number) => void;
        onremove: () => void;
    } = $props();
</script>

<div class="flex justify-end gap-1">
    <Button
        type="button"
        variant="ghost"
        size="sm"
        aria-label="Move {label} {index + 1} up"
        disabled={index === 0}
        onclick={() => onmove(-1)}
    >
        <ArrowUp class="size-4" />
    </Button>
    <Button
        type="button"
        variant="ghost"
        size="sm"
        aria-label="Move {label} {index + 1} down"
        disabled={index === count - 1}
        onclick={() => onmove(1)}
    >
        <ArrowDown class="size-4" />
    </Button>
    <Button
        type="button"
        variant="ghost"
        size="sm"
        class="text-destructive hover:text-destructive"
        aria-label="Remove {label} {index + 1}"
        onclick={onremove}
    >
        <Trash2 class="size-4" />
    </Button>
</div>
