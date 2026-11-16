<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { cubicOut } from 'svelte/easing';
    import { fade } from 'svelte/transition';
    import X from '@lucide/svelte/icons/x';
    import { cn } from '@/lib/utils';
    import { DIALOG_CONTEXT, type DialogContext } from './context';

    let { class: className = '', children }: { class?: string; children?: Snippet } = $props();

    const { open, setOpen } = getContext<DialogContext>(DIALOG_CONTEXT);

    const close = () => setOpen(false);

    const reducedMotion = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const duration = reducedMotion ? 0 : 200;

    /** Fades in while growing slightly and rising into place; reversed on close. */
    function pop(_node: Element) {
        return {
            duration,
            easing: cubicOut,
            css: (t: number) => `opacity: ${t}; transform: translateY(${(1 - t) * 8}px) scale(${0.96 + 0.04 * t});`,
        };
    }
</script>

{#if open()}
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <button
            type="button"
            class="fixed inset-0 bg-black/50"
            aria-label="Close"
            onclick={close}
            transition:fade={{ duration }}
        ></button>
        <div
            transition:pop
            class={cn(
                'relative z-10 w-full max-w-lg rounded-lg border bg-background p-6 shadow-lg',
                className,
            )}
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                class="absolute top-4 right-4 rounded-sm text-muted-foreground opacity-70 transition-opacity hover:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                aria-label="Close"
                onclick={close}
            >
                <X class="size-4" />
            </button>
            {@render children?.()}
        </div>
    </div>
{/if}
