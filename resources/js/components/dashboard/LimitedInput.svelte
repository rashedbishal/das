<script lang="ts">
    import type { HTMLInputAttributes } from 'svelte/elements';
    import { Input } from '@/components/ui/input';
    import { cn } from '@/lib/utils';

    /**
     * A text input with a hard `maxlength` and a small "used/limit" counter
     * inside the field's right edge, so the limit is visible without adding
     * a row (labels and inputs in a grid stay aligned).
     */
    let {
        limit,
        value = $bindable(''),
        class: className = '',
        ...rest
    }: Omit<HTMLInputAttributes, 'value'> & {
        limit: number;
        value?: string | number | null;
    } = $props();

    const length = $derived(String(value ?? '').length);
    // Reserve room for the widest counter ("1000/1000") so text never runs under it.
    const reserve = $derived(String(limit).length * 2 + 1);
</script>

<div class="relative w-full min-w-0">
    <Input
        bind:value
        maxlength={limit}
        class={cn(className)}
        style="padding-right: calc({reserve}ch + 0.75rem)"
        {...rest}
    />
    <span
        aria-hidden="true"
        class={cn(
            'pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 text-[11px] text-muted-foreground tabular-nums',
            length >= limit && 'text-destructive',
        )}
    >
        {length}/{limit}
    </span>
</div>
