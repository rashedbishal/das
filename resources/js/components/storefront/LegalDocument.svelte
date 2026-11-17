<script lang="ts">
    import type { Snippet } from 'svelte';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import { prefersReducedMotion } from '@/lib/motion.svelte';

    /**
     * Shared layout for the legal pages (Privacy Policy, Terms & Conditions):
     * header with the "last updated" date, a contents list (sticky beside the
     * text on large screens) and readable typography for the sections, which
     * are passed in as children. Each section is a <section id> matching `toc`.
     *
     * Motion (calm, for reading; all off for reduced motion):
     * - on load, the title rises word by word out of a mask, the eyebrow's
     *   letters close up, the contents cascade in and the intro comes into focus;
     * - the contents' left rail fills with the brand colour as you read
     *   (scroll-driven, large screens);
     * - the contents highlight the section being read;
     * - each section draws a line along its top and lifts its text in as it
     *   scrolls into view;
     * - contents links glide to their section.
     */
    let {
        title,
        eyebrow,
        updated,
        toc,
        intro,
        children,
    }: {
        title: string;
        eyebrow: string;
        /** Human date, e.g. "17 November 2026". */
        updated: string;
        toc: { id: string; label: string }[];
        intro: Snippet;
        children: Snippet;
    } = $props();

    const titleWords = $derived(title.split(' '));

    /** The section currently being read, highlighted in the contents. */
    let activeId = $state<string | null>(null);

    /**
     * Attachment for the article: reveals sections that start below the fold as
     * they scroll in, and tracks which section is being read.
     */
    function watchSections(article: HTMLElement) {
        if (typeof IntersectionObserver === 'undefined') {
            return;
        }

        const sections = [...article.querySelectorAll<HTMLElement>(':scope > section[id]')];

        const spy = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        activeId = entry.target.id;
                    }
                }
            },
            // A section counts as "being read" while it crosses the upper third.
            { rootMargin: '-25% 0px -65% 0px' },
        );
        sections.forEach((section) => spy.observe(section));

        let reveal: IntersectionObserver | null = null;

        if (!prefersReducedMotion()) {
            reveal = new IntersectionObserver(
                (entries) => {
                    for (const entry of entries) {
                        if (entry.isIntersecting) {
                            (entry.target as HTMLElement).dataset.reveal = 'in';
                            reveal?.unobserve(entry.target);
                        }
                    }
                },
                { rootMargin: '0px 0px -12% 0px' },
            );

            for (const section of sections) {
                // Only hide what is still off screen, so nothing visible blinks out.
                if (section.getBoundingClientRect().top > window.innerHeight * 0.9) {
                    section.dataset.reveal = 'armed';
                    reveal.observe(section);
                }
            }
        }

        return () => {
            spy.disconnect();
            reveal?.disconnect();
        };
    }

    /** Glide to a section (instantly for reduced motion) and keep the URL in sync. */
    function goTo(event: MouseEvent, id: string) {
        const target = document.getElementById(id);

        if (!target) {
            return;
        }

        event.preventDefault();
        target.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
        history.replaceState(history.state, '', `#${id}`);
        activeId = id;
    }
</script>

<section class="legal sf-own-motion container-wide pt-10 pb-20">
    <div class="legal-fade">
        <Breadcrumb items={[{ label: title }]} />
    </div>
    <p class="legal-eyebrow eyebrow mb-3">{eyebrow}</p>
    <h1 class="page-heading" aria-label={title}>
        {#each titleWords as word, i (i)}
            <span class="legal-word" aria-hidden="true"><span style:--i={i}>{word}</span></span
            >{' '}
        {/each}
    </h1>
    <p class="legal-updated mt-3 text-xs text-muted-foreground">Last updated: {updated}</p>

    <div class="mt-10 grid gap-10 lg:grid-cols-[240px_1fr] lg:gap-16">
        <nav aria-label="Contents" class="legal-toc">
            <p class="legal-toc-title mb-3 text-[11px] font-semibold tracking-[1.6px] text-muted-foreground uppercase">
                Contents
            </p>
            <ol>
                {#each toc as item, i (item.id)}
                    <li style:--i={i}>
                        <a
                            href="#{item.id}"
                            class:is-active={activeId === item.id}
                            aria-current={activeId === item.id ? 'location' : undefined}
                            onclick={(event) => goTo(event, item.id)}
                        >
                            <span class="legal-toc-num">{String(i + 1).padStart(2, '0')}</span>
                            {item.label}
                        </a>
                    </li>
                {/each}
            </ol>
        </nav>

        <article class="legal-body" {@attach watchSections}>
            <div class="legal-intro">
                {@render intro()}
            </div>
            {@render children()}
        </article>
    </div>
</section>

<style>
    .legal {
        --legal-ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Title words sit in masks so they can rise into place. */
    .legal-word {
        display: inline-block;
        overflow: hidden;
        padding-bottom: 0.1em;
        margin-bottom: -0.1em;
        vertical-align: top;
    }
    .legal-word > span {
        display: inline-block;
    }

    /* Contents */
    .legal-toc {
        align-self: start;
    }
    @media (min-width: 1024px) {
        .legal-toc {
            position: sticky;
            top: 110px;
        }
    }
    .legal-toc ol {
        position: relative;
        display: grid;
        gap: 2px;
        border-left: 1px solid var(--border);
    }
    .legal-toc a {
        position: relative;
        display: flex;
        gap: 10px;
        margin-left: -1px;
        border-left: 2px solid transparent;
        border-radius: 0 6px 6px 0;
        padding: 6px 8px 6px 14px;
        font-size: 12.5px;
        line-height: 1.45;
        color: var(--muted-foreground);
        transition:
            color 0.3s var(--legal-ease),
            border-color 0.3s var(--legal-ease),
            background-color 0.3s var(--legal-ease),
            padding-left 0.35s var(--legal-ease);
    }
    .legal-toc a:hover,
    .legal-toc a:focus-visible {
        border-left-color: color-mix(in oklab, var(--primary) 45%, transparent);
        color: var(--foreground);
    }
    .legal-toc a.is-active {
        border-left-color: var(--primary);
        background-color: color-mix(in oklab, var(--primary) 6%, transparent);
        padding-left: 18px;
        color: var(--foreground);
        font-weight: 500;
    }
    .legal-toc-num {
        flex: none;
        font-variant-numeric: tabular-nums;
        color: var(--primary);
    }

    /* Body */
    .legal-body {
        max-width: 760px;
        font-size: 14px;
        line-height: 1.85;
        color: var(--foreground);
    }
    .legal-intro {
        margin-bottom: 8px;
        border-radius: var(--radius);
        background: var(--secondary);
        padding: 20px 22px;
    }
    .legal-body :global(section) {
        position: relative;
        scroll-margin-top: 110px;
        border-top: 1px solid var(--border);
        padding-top: 30px;
        margin-top: 30px;
    }
    /* The line drawn along a section's top edge as it scrolls in. */
    .legal-body :global(section)::before {
        content: '';
        position: absolute;
        top: -1px;
        left: 0;
        right: 0;
        height: 1px;
        background: var(--primary);
        transform-origin: left;
        opacity: 0;
        pointer-events: none;
    }
    .legal-body :global(h2) {
        margin-bottom: 12px;
        font-size: 21px;
        line-height: 1.3;
    }
    .legal-body :global(h3) {
        margin: 20px 0 6px;
        font-size: 15px;
        font-weight: 600;
    }
    .legal-body :global(p + p),
    .legal-body :global(ul + p),
    .legal-body :global(p + ul) {
        margin-top: 12px;
    }
    .legal-body :global(ul) {
        display: grid;
        gap: 6px;
        padding-left: 20px;
        list-style: disc;
    }
    .legal-body :global(li::marker) {
        color: var(--primary);
    }
    .legal-body :global(a) {
        color: var(--primary);
        text-decoration: underline;
        text-underline-offset: 3px;
        transition: text-decoration-color 0.2s;
    }
    .legal-body :global(a:hover) {
        text-decoration-color: transparent;
    }
    .legal-body :global(strong) {
        font-weight: 600;
    }

    @media (prefers-reduced-motion: no-preference) {
        /* Entrance (pure CSS, so it plays straight from the server-rendered page). */
        .legal-fade {
            animation: legal-fade 0.8s var(--legal-ease) backwards;
        }
        .legal-eyebrow {
            animation: legal-track 1.2s var(--legal-ease) 0.05s backwards;
        }
        .legal-word > span {
            animation: legal-word 1.1s var(--legal-ease) calc(0.15s + var(--i) * 90ms) backwards;
        }
        .legal-updated {
            animation: legal-fade 0.9s var(--legal-ease) 0.45s backwards;
        }
        .legal-toc-title {
            animation: legal-fade 0.8s var(--legal-ease) 0.4s backwards;
        }
        .legal-toc li {
            animation: legal-slide 0.8s var(--legal-ease) calc(0.45s + var(--i) * 40ms) backwards;
        }
        .legal-intro {
            animation: legal-focus 1.1s var(--legal-ease) 0.5s backwards;
        }

        /* Sections below the fold: hidden until they scroll in (see watchSections). */
        :global(:root[data-motion]) .legal-body :global(section[data-reveal='armed']) {
            opacity: 0;
        }
        :global(:root[data-motion]) .legal-body :global(section[data-reveal='in']) {
            animation: legal-fade 0.6s var(--legal-ease) backwards;
        }
        :global(:root[data-motion]) .legal-body :global(section[data-reveal='in'])::before {
            animation: legal-rule 1.6s var(--legal-ease) both;
        }
        :global(:root[data-motion]) .legal-body :global(section[data-reveal='in'] > h2) {
            animation: legal-rise 0.9s var(--legal-ease) 0.1s backwards;
        }
        :global(:root[data-motion]) .legal-body :global(section[data-reveal='in'] > :not(h2)) {
            animation: legal-rise 0.9s var(--legal-ease) 0.22s backwards;
        }

        /* Reading progress: the contents rail fills as the page scrolls. */
        @supports (animation-timeline: scroll()) {
            @media (min-width: 1024px) {
                .legal-toc ol::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    bottom: 0;
                    left: -1px;
                    width: 1px;
                    background: var(--primary);
                    transform-origin: top;
                    animation: legal-progress linear both;
                    animation-timeline: scroll(root);
                }
            }
        }
    }

    @keyframes legal-fade {
        from {
            opacity: 0;
        }
    }
    @keyframes legal-track {
        from {
            opacity: 0;
            letter-spacing: 6px;
        }
    }
    @keyframes legal-word {
        from {
            translate: 0 110%;
            filter: blur(4px);
        }
    }
    @keyframes legal-slide {
        from {
            opacity: 0;
            translate: -12px 0;
        }
    }
    @keyframes legal-focus {
        from {
            opacity: 0;
            filter: blur(8px);
            translate: 0 10px;
        }
    }
    @keyframes legal-rise {
        from {
            opacity: 0;
            translate: 0 12px;
        }
    }
    @keyframes legal-rule {
        0% {
            opacity: 1;
            transform: scaleX(0);
        }
        60% {
            opacity: 1;
            transform: scaleX(1);
        }
        100% {
            opacity: 0;
            transform: scaleX(1);
        }
    }
    @keyframes legal-progress {
        from {
            transform: scaleY(0);
        }
        to {
            transform: scaleY(1);
        }
    }
</style>
