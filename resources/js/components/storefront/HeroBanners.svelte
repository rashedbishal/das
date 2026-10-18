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
    import { buttonClass } from './button';

    let { banners }: { banners: Banner[] } = $props();

    const INTERVAL = 6000;

    let active = $state(0);
    let paused = $state(false);
    let reducedMotion = $state(false);
    let touchStartX: number | null = null;

    const count = $derived(banners.length);

    function go(index: number) {
        active = (index + count) % count;
    }

    onMount(() => {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');
        reducedMotion = query.matches;
        const onChange = () => (reducedMotion = query.matches);
        query.addEventListener('change', onChange);

        return () => query.removeEventListener('change', onChange);
    });

    $effect(() => {
        if (count < 2 || paused || reducedMotion) {
            return;
        }

        // Re-runs (and so restarts the timer) whenever `active` changes, so a
        // manual click gets a full interval before the next auto-advance.
        void active;
        const timer = setTimeout(() => go(active + 1), INTERVAL);

        return () => clearTimeout(timer);
    });

    function isExternal(url: string) {
        try {
            return new URL(url, window.location.href).origin !== window.location.origin;
        } catch {
            return false;
        }
    }
</script>

<section
    class="hero-banners"
    aria-roledescription="carousel"
    aria-label="Featured"
    onmouseenter={() => (paused = true)}
    onmouseleave={() => (paused = false)}
    onfocusin={() => (paused = true)}
    onfocusout={() => (paused = false)}
    ontouchstart={(event) => (touchStartX = event.touches[0].clientX)}
    ontouchend={(event) => {
        if (touchStartX === null) {
            return;
        }

        const delta = event.changedTouches[0].clientX - touchStartX;
        touchStartX = null;

        if (Math.abs(delta) > 40) {
            go(active + (delta < 0 ? 1 : -1));
        }
    }}
>
    {#each banners as banner, i (banner.id)}
        <div
            class="hero-banner"
            class:is-active={i === active}
            role="group"
            aria-roledescription="slide"
            aria-label="{i + 1} of {count}"
            aria-hidden={i !== active}
            inert={i !== active}
        >
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
                    alt={banner.show_text ? '' : banner.title}
                    width="1920"
                    height="640"
                    loading={i === 0 ? 'eager' : 'lazy'}
                    fetchpriority={i === 0 ? 'high' : 'auto'}
                />
            </picture>

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
                            <a
                                href={banner.link_url}
                                target={isExternal(banner.link_url)
                                    ? '_blank'
                                    : undefined}
                                rel={isExternal(banner.link_url)
                                    ? 'noopener'
                                    : undefined}
                                class={buttonClass({
                                    class: 'mt-6 h-11 px-6 text-xs shadow-none max-md:mt-4 max-md:h-10',
                                })}
                            >
                                {banner.link_label || 'Explore'}
                                <ArrowUpRight class="ml-2 size-4" />
                            </a>
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
    {/each}

    {#if count > 1}
        <button
            type="button"
            class="hero-banner-arrow left-4"
            aria-label="Previous slide"
            onclick={() => go(active - 1)}
        >
            <ChevronLeft class="size-5" />
        </button>
        <button
            type="button"
            class="hero-banner-arrow right-4"
            aria-label="Next slide"
            onclick={() => go(active + 1)}
        >
            <ChevronRight class="size-5" />
        </button>

        <div class="hero-banner-dots">
            {#each banners as banner, i (banner.id)}
                <button
                    type="button"
                    aria-label="Go to slide {i + 1}"
                    aria-current={i === active}
                    class:is-active={i === active}
                    onclick={() => go(i)}
                ></button>
            {/each}
        </div>
    {/if}
</section>
