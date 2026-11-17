<script module lang="ts">
    export type Banner = {
        id: number;
        title: string;
        subtitle: string | null;
        link_url: string | null;
        link_label: string | null;
        show_text: boolean;
        images: {
            desktop: string | null;
            tablet: string | null;
            mobile: string | null;
        };
    };
</script>

<script lang="ts">
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import { onMount } from 'svelte';
    import { SvelteSet } from 'svelte/reactivity';
    import { buttonClass } from './button';

    let { banners }: { banners: Banner[] } = $props();

    /** Time each banner stays up. Mirrored in CSS as --hero-interval. */
    const INTERVAL = 6000;
    /** Length of the scanner sweep. Mirrored in CSS as --hero-sweep. */
    const SWEEP = 1600;
    /** Sweep plus the staggered headline/text/button rise that follows it. */
    const SETTLE = 2200;

    const count = $derived(banners.length);

    let active = $state(0);
    let leaving = $state<number | null>(null);
    let entering = $state(false);
    let direction = $state<1 | -1>(1);
    let sweepId = $state(0);
    let intro = $state(true);
    let paused = $state(false);
    let autoplay = $state(false);
    let reducedMotion = false;

    // Only the first banner's image is in the initial HTML. Others are fetched
    // in the background (one at a time, after the page has loaded) and only
    // rendered once decoded, so a sweep never reveals a half-loaded image and
    // slow connections spend their bandwidth on the first banner.
    const ready = new SvelteSet<number>([0]);
    const loading = new Map<number, Promise<void>>();

    let timer: ReturnType<typeof setTimeout> | undefined;
    let remaining = INTERVAL;
    let startedAt = 0;
    let busy = false;
    let touchStartX: number | null = null;

    function urlFor(banner: Banner) {
        const { desktop, tablet, mobile } = banner.images;

        if (mobile && window.matchMedia('(max-width: 767px)').matches) {
            return mobile;
        }

        if (tablet && window.matchMedia('(max-width: 1099px)').matches) {
            return tablet;
        }

        return desktop;
    }

    function prime(index: number): Promise<void> {
        if (ready.has(index)) {
            return Promise.resolve();
        }

        let pending = loading.get(index);

        if (!pending) {
            const image = new Image();
            image.src = urlFor(banners[index]) ?? '';
            pending = image
                .decode()
                .catch(() => {})
                .then(() => {
                    ready.add(index);
                });
            loading.set(index, pending);
        }

        return pending;
    }

    function wrap(index: number) {
        return (index + count) % count;
    }

    function schedule() {
        clearTimeout(timer);
        timer = undefined;

        if (!autoplay || paused || count < 2) {
            return;
        }

        startedAt = performance.now();
        timer = setTimeout(() => {
            timer = undefined;
            go(active + 1, 1);
        }, remaining);
    }

    function hold() {
        if (timer !== undefined) {
            clearTimeout(timer);
            timer = undefined;
            remaining = Math.max(0, remaining - (performance.now() - startedAt));
        }
    }

    function setPaused(value: boolean) {
        paused = value;

        if (value) {
            hold();
        } else {
            schedule();
        }
    }

    async function go(index: number, dir: 1 | -1) {
        const to = wrap(index);

        if (busy || to === active) {
            return;
        }

        busy = true;
        await prime(to);

        if (reducedMotion) {
            active = to;
        } else {
            const id = ++sweepId;
            direction = dir;
            leaving = active;
            active = to;
            entering = true;
            intro = false;

            setTimeout(() => {
                if (sweepId === id) {
                    leaving = null;
                }
            }, SWEEP);
            setTimeout(() => {
                if (sweepId === id) {
                    entering = false;
                }
            }, SETTLE);
        }

        setTimeout(
            () => {
                busy = false;

                if (navigator.onLine) {
                    prime(wrap(to + 1));
                }
            },
            reducedMotion ? 0 : SWEEP,
        );

        remaining = INTERVAL;
        schedule();
    }

    onMount(() => {
        const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const saveData =
            (navigator as Navigator & { connection?: { saveData?: boolean } })
                .connection?.saveData === true;

        reducedMotion = motion.matches;
        autoplay = count > 1 && !reducedMotion && !saveData;

        const onVisibility = () => setPaused(document.hidden);
        document.addEventListener('visibilitychange', onVisibility);

        // Warm up the second banner once the page itself has finished loading.
        const warm = () => {
            if (!saveData && count > 1) {
                const idle = window.requestIdleCallback ?? ((cb) => setTimeout(cb, 1500));
                idle(() => prime(1));
            }
        };

        if (document.readyState === 'complete') {
            warm();
        } else {
            window.addEventListener('load', warm, { once: true });
        }

        schedule();

        return () => {
            clearTimeout(timer);
            document.removeEventListener('visibilitychange', onVisibility);
            window.removeEventListener('load', warm);
        };
    });

    function isExternal(url: string) {
        try {
            return new URL(url, window.location.href).origin !== window.location.origin;
        } catch {
            return false;
        }
    }
</script>

<!-- Arrow keys bubble up from the focused controls/links inside the carousel. -->
<!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
<section
    class="hero-banners"
    class:is-intro={intro}
    class:is-reverse={direction === -1}
    aria-roledescription="carousel"
    aria-label="Featured"
    onmouseenter={() => setPaused(true)}
    onmouseleave={() => setPaused(false)}
    onfocusin={() => setPaused(true)}
    onfocusout={() => setPaused(false)}
    onkeydown={(event) => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            go(
                active + (event.key === 'ArrowRight' ? 1 : -1),
                event.key === 'ArrowRight' ? 1 : -1,
            );
        }
    }}
    ontouchstart={(event) => (touchStartX = event.touches[0].clientX)}
    ontouchend={(event) => {
        if (touchStartX === null) {
            return;
        }

        const delta = event.changedTouches[0].clientX - touchStartX;
        touchStartX = null;

        if (Math.abs(delta) > 40) {
            go(active + (delta < 0 ? 1 : -1), delta < 0 ? 1 : -1);
        }
    }}
>
    {#each banners as banner, i (banner.id)}
        <div
            class="hero-banner"
            class:is-active={i === active}
            class:is-entering={i === active && entering}
            class:is-leaving={i === leaving}
            role="group"
            aria-roledescription="slide"
            aria-label="{i + 1} of {count}"
            aria-hidden={i !== active}
            inert={i !== active}
        >
            <!-- The slide is the moving "window"; the frame counter-moves so the
                 picture stays still while the window's edge sweeps across it. -->
            <div class="hero-banner-frame">
                {#if ready.has(i)}
                    <picture>
                        {#if banner.images.mobile}
                            <source
                                media="(max-width: 767px)"
                                srcset={banner.images.mobile}
                                type="image/webp"
                            />
                        {/if}
                        {#if banner.images.tablet}
                            <source
                                media="(max-width: 1099px)"
                                srcset={banner.images.tablet}
                                type="image/webp"
                            />
                        {/if}
                        <img
                            src={banner.images.desktop}
                            alt={banner.title}
                            width="1920"
                            height="640"
                            fetchpriority={i === 0 ? 'high' : 'auto'}
                        />
                    </picture>
                {/if}

                {#if banner.show_text}
                    <div class="hero-banner-scrim"></div>
                    <div class="container-wide hero-banner-inner">
                        <div class="hero-banner-copy">
                            <svelte:element
                                this={i === 0 ? 'h1' : 'h2'}
                                class="hero-banner-title"
                            >
                                {banner.title}
                            </svelte:element>
                            {#if banner.subtitle}
                                <p class="hero-banner-subtitle">
                                    {banner.subtitle}
                                </p>
                            {/if}
                            {#if banner.link_url}
                                <div>
                                    <a
                                        href={banner.link_url}
                                        target={isExternal(banner.link_url)
                                            ? '_blank'
                                            : undefined}
                                        rel={isExternal(banner.link_url)
                                            ? 'noopener'
                                            : undefined}
                                        class={buttonClass({
                                            class: 'mt-6 h-11 px-6 text-xs shadow-none max-md:mt-3 max-md:h-9 max-md:px-4',
                                        })}
                                    >
                                        {banner.link_label || 'Explore'}
                                        <ArrowUpRight class="ml-2 size-4" />
                                    </a>
                                </div>
                            {/if}
                        </div>
                    </div>
                {:else if banner.link_url}
                    <a
                        href={banner.link_url}
                        class="absolute inset-0"
                        aria-label={banner.title}
                    ></a>
                {/if}
            </div>
        </div>
    {/each}

    {#key sweepId}
        {#if leaving !== null}
            <div class="hero-scan" aria-hidden="true"></div>
        {/if}
    {/key}

    {#if count > 1}
        <!-- One control bar for every device: big tap targets, always visible. -->
        <div class="hero-controls-wrap container-wide">
            <div
                class="hero-controls"
                class:is-paused={paused}
                class:is-static={!autoplay}
            >
                <button
                    type="button"
                    class="hero-control-arrow"
                    aria-label="Previous banner"
                    onclick={() => go(active - 1, -1)}
                >
                    <ChevronLeft class="size-5" />
                </button>
                <div class="hero-dots">
                    {#each banners as banner, i (banner.id)}
                        <button
                            type="button"
                            class="hero-dot"
                            aria-label="Show banner {i + 1}"
                            aria-current={i === active}
                            class:is-active={i === active}
                            onclick={() => go(i, i > active ? 1 : -1)}
                        >
                            <span></span>
                        </button>
                    {/each}
                </div>
                <button
                    type="button"
                    class="hero-control-arrow"
                    aria-label="Next banner"
                    onclick={() => go(active + 1, 1)}
                >
                    <ChevronRight class="size-5" />
                </button>
            </div>
        </div>
    {/if}
</section>
