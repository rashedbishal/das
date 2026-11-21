<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Headphones from '@lucide/svelte/icons/headphones';
    import Layers3 from '@lucide/svelte/icons/layers';
    import ScanLine from '@lucide/svelte/icons/scan-line';
    import hero from '@/assets/storefront/dental-suite.jpg';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import { buttonClass } from '@/components/storefront/button';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { contact } from '@/routes';
    import type { AboutPageContent } from '@/types';

    let { about }: { about: AboutPageContent } = $props();

    /** Headline words, numbered across both lines so they animate in reading order. */
    const headlineLines = $derived.by(() => {
        let index = 0;

        return [about.headline_line_1, about.headline_line_2].map((line) =>
            line
                .trim()
                .split(/\s+/)
                .map((word) => ({ word, index: index++ })),
        );
    });

    /** Icons stay fixed per card position; the copy comes from the CMS. */
    const icons = [Layers3, ScanLine, Headphones];
    const principles = $derived(
        about.principles.map((principle, i) => ({
            ...principle,
            icon: icons[i],
        })),
    );
</script>

<PageMeta
    title="About Us"
    description="Learn about DAS and our approach to helping dental professionals find suitable products."
/>

<div class="container-wide pt-10">
    <Breadcrumb items={[{ label: 'About DAS' }]} />
</div>

<section
    class="about-hero relative isolate flex min-h-[440px] items-center overflow-hidden bg-secondary sm:min-h-[510px]"
>
    <div class="about-hero-parallax absolute inset-0 -z-20">
        <img
            src={hero}
            class="about-hero-image h-full w-full object-cover object-[65%] opacity-35"
            alt="A modern dental treatment suite"
            width={1920}
            height={1024}
        />
    </div>
    <div class="container-wide py-14 sm:py-16">
        <p class="about-hero-eyebrow eyebrow mb-5">Meet DAS</p>
        <h1
            class="max-w-[650px] text-[36px] leading-[1.1] sm:text-[44px] md:text-[60px]"
        >
            {#each headlineLines as words, line (line)}
                <span class="about-hero-line">
                    {#each words as { word, index } (index)}
                        <span class="about-hero-word" style:--i={index}
                            >{word}</span
                        >{' '}
                    {/each}
                </span>
            {/each}
        </h1>
        <p class="about-hero-copy mt-6 max-w-md text-sm leading-7 sm:mt-7 sm:leading-8">
            {about.intro}
        </p>
        <Link
            href={contact.url()}
            class={buttonClass({
                class: 'about-hero-cta mt-8 h-11 w-full justify-center sm:w-auto',
            })}
        >
            Let’s start a conversation
            <ArrowUpRight />
        </Link>
    </div>
</section>

<section class="container-wide section-space">
    <div class="grid gap-10 md:grid-cols-2 lg:gap-24">
        <div class="sf-reveal">
            <p class="eyebrow mb-4">Our perspective</p>
            <h2 class="section-heading">{about.heading}</h2>
        </div>
        <div class="sf-reveal" style:--sf-i={1}>
            <p class="text-sm leading-8 text-muted-foreground">
                {about.paragraph_1}
            </p>
            <p class="mt-5 text-sm leading-8 text-muted-foreground">
                {about.paragraph_2}
            </p>
        </div>
    </div>
    <div class="mt-16 grid gap-8 md:grid-cols-3">
        {#each principles as { icon: Icon, title, text }, i (i)}
            <div class="sf-reveal border-t pt-7" style:--sf-i={i}>
                <Icon class="mb-6 size-7 text-primary" strokeWidth={1.3} />
                <h3 class="text-lg">{title}</h3>
                <p class="mt-3 text-xs leading-7 text-muted-foreground">{text}</p>
            </div>
        {/each}
    </div>
</section>

<ContactBanner />

<style>
    /*
     * About hero entrance. Pure CSS, so it plays straight from the
     * server-rendered page with no flash. Only opacity, transform and (briefly)
     * filter animate. Reduced-motion users see the hero at rest.
     */
    .about-hero {
        --about-ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    /*
     * Headline: every word unfolds upward in 3D out of a soft blur, in reading
     * order (--i is the word's position). Once all have landed, a band of the
     * primary colour runs across the words one after another, like light along
     * polished steel. The text is painted with a gradient clipped to the
     * letters; at rest only the plain foreground part of it shows.
     */
    .about-hero-line {
        display: block;
        perspective: 700px;
    }
    .about-hero-word {
        display: inline-block;
        transform-origin: 50% 100%;
        color: transparent;
        background-image: linear-gradient(
            110deg,
            var(--foreground) 38%,
            color-mix(in oklab, var(--primary) 55%, white) 50%,
            var(--foreground) 62%
        );
        background-size: 260% 100%;
        background-position: 100% 0;
        -webkit-background-clip: text;
        background-clip: text;
    }

    .about-hero-eyebrow {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .about-hero-eyebrow::before {
        content: '';
        width: 28px;
        height: 1px;
        background: currentColor;
        transform-origin: left;
    }

    .about-hero :global(.about-hero-cta) {
        position: relative;
        overflow: hidden;
        isolation: isolate;
    }
    .about-hero :global(.about-hero-cta)::after {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 40%;
        z-index: -1;
        pointer-events: none;
        background: linear-gradient(
            100deg,
            rgb(255 255 255 / 0) 0%,
            rgb(255 255 255 / 0.35) 50%,
            rgb(255 255 255 / 0) 100%
        );
        transform: translateX(-120%) skewX(-18deg);
    }
    .about-hero :global(.about-hero-cta svg) {
        transition: transform 0.35s var(--about-ease);
    }
    .about-hero :global(.about-hero-cta:hover svg) {
        transform: translate(2px, -2px);
    }

    @media (prefers-reduced-motion: no-preference) {
        .about-hero-image {
            animation: about-image 2.4s var(--about-ease) both;
        }
        .about-hero-eyebrow {
            animation: about-fade 0.9s var(--about-ease) 0.25s both;
        }
        .about-hero-eyebrow::before {
            animation: about-draw 1s var(--about-ease) 0.3s both;
        }
        .about-hero-word {
            animation:
                about-word 1.3s var(--about-ease) calc(0.35s + var(--i) * 90ms)
                    both,
                about-word-sheen 1.5s cubic-bezier(0.65, 0, 0.35, 1)
                    calc(1.6s + var(--i) * 110ms) both;
        }
        .about-hero-copy {
            animation: about-focus 1.1s var(--about-ease) 0.8s both;
        }
        .about-hero :global(.about-hero-cta) {
            animation: about-rise 1s var(--about-ease) 1s both;
        }
        .about-hero :global(.about-hero-cta)::after {
            animation: about-sheen 1.2s var(--about-ease) 1.9s both;
        }

        /* The photo drifts slower than the page as the hero scrolls away. */
        @supports (animation-timeline: view()) {
            .about-hero-parallax {
                animation: about-parallax linear both;
                animation-timeline: view();
                animation-range: exit 0% exit 100%;
            }
        }
    }

    @keyframes about-image {
        from {
            opacity: 0;
            transform: scale(1.12);
        }
        to {
            opacity: 0.35;
            transform: scale(1);
        }
    }
    @keyframes about-fade {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
    }
    @keyframes about-draw {
        from {
            transform: scaleX(0);
        }
    }
    @keyframes about-word {
        from {
            opacity: 0;
            filter: blur(12px);
            transform: translateY(0.5em) rotateX(-75deg) scale(0.96);
        }
    }
    @keyframes about-word-sheen {
        from {
            background-position: 100% 0;
        }
        to {
            background-position: 0% 0;
        }
    }
    @keyframes about-focus {
        from {
            opacity: 0;
            filter: blur(8px);
            transform: translateY(10px);
        }
    }
    @keyframes about-rise {
        from {
            opacity: 0;
            transform: translateY(14px);
        }
    }
    @keyframes about-sheen {
        to {
            transform: translateX(320%) skewX(-18deg);
        }
    }
    @keyframes about-parallax {
        to {
            transform: translateY(18%);
        }
    }
</style>
