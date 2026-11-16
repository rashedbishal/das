<script lang="ts">
    import X from '@lucide/svelte/icons/x';
    import type { Snippet } from 'svelte';
    import { fade } from 'svelte/transition';
    import { cn } from '@/lib/utils';
    import { focusFirst, lockScroll, prefersReducedMotion, zoom } from './overlay';

    let {
        open = $bindable(false),
        title,
        description,
        class: className = '',
        header,
        children,
    }: {
        open?: boolean;
        title: string;
        description?: string;
        class?: string;
        /** Replaces the default title/description; receives the ids to use for them. */
        header?: Snippet<[{ titleId: string; descriptionId: string }]>;
        children?: Snippet;
    } = $props();

    const id = $props.id();
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
        // A dropdown inside the modal (e.g. the product picker) handles its own Escape.
        if (open && event.key === 'Escape' && !event.defaultPrevented) {
            open = false;
        }
    }}
/>

{#if open}
    <div
        class="modal-backdrop fixed inset-0 z-50 bg-black/80"
        aria-hidden="true"
        onclick={() => (open = false)}
        out:fade={{ duration: prefersReducedMotion() ? 0 : 180 }}
    ></div>
    <div
        bind:this={content}
        role="dialog"
        aria-modal="true"
        aria-labelledby="{id}-title"
        aria-describedby={description ? `${id}-description` : undefined}
        class={cn(
            'modal-panel fixed top-[50%] left-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border bg-background p-6 shadow-lg sm:rounded-lg',
            className,
        )}
        out:zoom
    >
        {#if header}
            {@render header({
                titleId: `${id}-title`,
                descriptionId: `${id}-description`,
            })}
        {:else}
            <h2
                id="{id}-title"
                class="text-lg leading-none font-semibold tracking-tight"
            >
                {title}
            </h2>
            {#if description}
                <p
                    id="{id}-description"
                    class="text-sm text-muted-foreground"
                >
                    {description}
                </p>
            {/if}
        {/if}
        {@render children?.()}
        <button
            type="button"
            class="absolute top-4 right-4 cursor-pointer rounded-sm bg-accent text-muted-foreground opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
            onclick={() => (open = false)}
        >
            <X class="h-4 w-4" />
            <span class="sr-only">Close</span>
        </button>
    </div>
{/if}

<style>
    /* Opening uses CSS keyframes, which replay on every insertion (Svelte intro
       transitions can fail to re-fire in runes mode); closing uses the out:
       transitions above. Only `transform` is animated: the centring uses the
       separate `translate` property, so the two don't clash. */
    .modal-backdrop {
        animation: modal-fade-in 220ms ease-out both;
    }

    .modal-panel {
        animation: modal-pop-in 260ms cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    @keyframes modal-fade-in {
        from {
            opacity: 0;
        }
    }

    @keyframes modal-pop-in {
        from {
            opacity: 0;
            transform: translateY(12px) scale(0.96);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .modal-backdrop,
        .modal-panel {
            animation: none;
        }
    }
</style>
