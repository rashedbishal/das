<script lang="ts">
    import ChevronDown from '@lucide/svelte/icons/chevron-down';

    /**
     * Long text shown as its first `lines` lines with a fade and a "Read more"
     * toggle. The full text is always in the HTML (only clipped with CSS), so
     * search engines and screen readers get all of it. The toggle appears only
     * when the text is actually longer than `lines`.
     */
    let {
        text,
        lines = 6,
        class: className = '',
    }: { text: string; lines?: number; class?: string } = $props();

    const id = $props.id();
    let expanded = $state(false);
    let overflowing = $state(false);
    let paragraph = $state<HTMLParagraphElement | null>(null);

    // Re-measure when the text or the width changes (one cheap observer).
    $effect(() => {
        const el = paragraph;
        void text;

        if (!el) {
            return;
        }

        const measure = () => {
            if (!expanded) {
                overflowing = el.scrollHeight > el.clientHeight + 1;
            }
        };
        const observer = new ResizeObserver(measure);

        observer.observe(el);
        measure();

        return () => observer.disconnect();
    });

    function toggle() {
        expanded = !expanded;

        // After collapsing a long text, bring its start back into view.
        if (!expanded && paragraph && paragraph.getBoundingClientRect().top < 0) {
            paragraph.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }
    }
</script>

<div class="relative">
    <p
        bind:this={paragraph}
        {id}
        class="{className} {expanded ? '' : 'collapsible-clamp'}"
        style:--lines={lines}
    >
        {text}
    </p>
    {#if overflowing && !expanded}
        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-linear-to-t from-background to-transparent"
            aria-hidden="true"
        ></div>
    {/if}
</div>
{#if overflowing}
    <button
        type="button"
        class="mt-3 inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
        aria-expanded={expanded}
        aria-controls={id}
        onclick={toggle}
    >
        {expanded ? 'Show less' : 'Read more'}
        <ChevronDown class="size-3.5 transition-transform {expanded ? 'rotate-180' : ''}" />
    </button>
{/if}

<style>
    .collapsible-clamp {
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: var(--lines);
        line-clamp: var(--lines);
    }
</style>
