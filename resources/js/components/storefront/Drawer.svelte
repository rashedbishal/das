<script lang="ts">
    import X from '@lucide/svelte/icons/x';
    import type { Snippet } from 'svelte';
    import { fade } from 'svelte/transition';
    import { cn } from '@/lib/utils';
    import { focusFirst, lockScroll, slide } from './overlay';

    let {
        open = $bindable(false),
        side = 'right',
        label,
        class: className = '',
        children,
    }: {
        open?: boolean;
        side?: 'left' | 'right';
        label: string;
        class?: string;
        children?: Snippet;
    } = $props();

    let content = $state<HTMLElement>();

    $effect(() => {
        if (!open) {
            return;
        }

        const unlock = lockScroll();
        focusFirst(content);

        return unlock;
    });
</script>

<svelte:window
    onkeydown={(event) => {
        if (open && event.key === 'Escape') {
            open = false;
        }
    }}
/>

{#if open}
    <div
        class="fixed inset-0 z-50 bg-black/80"
        aria-hidden="true"
        onclick={() => (open = false)}
        transition:fade={{ duration: 300 }}
    ></div>
    <div
        bind:this={content}
        role="dialog"
        aria-modal="true"
        aria-label={label}
        class={cn(
            'fixed inset-y-0 z-50 h-full w-3/4 gap-4 bg-background p-6 shadow-lg sm:max-w-sm',
            side === 'left' ? 'left-0 border-r' : 'right-0 border-l',
            className,
        )}
        in:slide={{ side, duration: 500 }}
        out:slide={{ side, duration: 300 }}
    >
        <button
            type="button"
            class="absolute top-4 right-4 cursor-pointer rounded-sm bg-secondary opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
            onclick={() => (open = false)}
        >
            <X class="h-4 w-4" />
            <span class="sr-only">Close</span>
        </button>
        {@render children?.()}
    </div>
{/if}
