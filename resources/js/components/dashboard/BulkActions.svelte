<script lang="ts">
    import type { Snippet } from 'svelte';
    import { Button } from '@/components/ui/button';

    let {
        count,
        onClear,
        error = null,
        children,
    }: {
        count: number;
        onClear: () => void;
        error?: string | null;
        children: Snippet;
    } = $props();
</script>

{#if count > 0}
    <div class="mb-3 flex flex-wrap items-center gap-2 rounded-lg border bg-muted/30 p-3">
        <span class="mr-auto text-sm font-medium">
            {count} selected <span class="font-normal text-muted-foreground">(up to 100 per action)</span>
        </span>
        {@render children()}
        <Button variant="ghost" size="sm" onclick={onClear}>Clear selection</Button>
        {#if error}
            <p class="w-full text-sm text-destructive" role="alert">{error}</p>
        {/if}
    </div>
{/if}
