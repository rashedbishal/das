<script lang="ts">
    import type { HTMLTextareaAttributes } from 'svelte/elements';
    import { Textarea } from '@/components/ui/textarea';
    import { cn } from '@/lib/utils';

    /**
     * A textarea with a hard `maxlength` and a small "used/limit" counter in
     * its bottom-right corner.
     */
    let {
        limit,
        value = $bindable(''),
        class: className = '',
        ...rest
    }: Omit<HTMLTextareaAttributes, 'value'> & {
        limit: number;
        value?: string | null;
    } = $props();

    const length = $derived((value ?? '').length);
</script>

<div class="relative w-full min-w-0">
    <Textarea bind:value maxlength={limit} class={cn('min-h-20 pb-6', className)} {...rest} />
    <span
        aria-hidden="true"
        class={cn(
            'pointer-events-none absolute right-2.5 bottom-1.5 rounded-sm bg-background/90 px-1 text-[11px] text-muted-foreground tabular-nums',
            length >= limit && 'text-destructive',
        )}
    >
        {length}/{limit}
    </span>
</div>
