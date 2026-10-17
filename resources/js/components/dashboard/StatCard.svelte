<script lang="ts">
    import type { Component } from 'svelte';
    import { Card, CardContent } from '@/components/ui/card';

    let {
        label,
        value,
        hint,
        icon: Icon,
        tone = 'default',
    }: {
        label: string;
        value: string | number;
        hint?: string;
        icon: Component;
        tone?: 'default' | 'warning';
    } = $props();
</script>

<Card>
    <CardContent class="flex items-start justify-between gap-3 px-4 py-3">
        <div class="min-w-0">
            <p class="text-sm text-muted-foreground">{label}</p>
            <p class="mt-1 text-2xl font-semibold tracking-tight">{value}</p>
            {#if hint}
                <p
                    class={[
                        'mt-1 text-xs',
                        tone === 'warning' ? 'text-destructive' : 'text-muted-foreground',
                    ].join(' ')}
                >
                    {hint}
                </p>
            {/if}
        </div>
        <div
            class={[
                'flex size-9 shrink-0 items-center justify-center rounded-lg',
                tone === 'warning'
                    ? 'bg-destructive/10 text-destructive'
                    : 'bg-primary/10 text-primary',
            ].join(' ')}
        >
            <Icon class="size-4.5" />
        </div>
    </CardContent>
</Card>
