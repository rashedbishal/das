import type { VisitOptions } from '@inertiajs/core';

/**
 * Storefront motion ("Continuity"); the CSS side is resources/css/motion.css.
 *
 * StorefrontLayout puts `data-motion` on <html> while the storefront is on
 * screen, so the auth screens and the dashboard are never affected.
 */
export const motion = $state<{
    /** Slug of the product card whose photo should morph into the product page. */
    morphSlug: string | null;
}>({
    morphSlug: null,
});

export function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

function saveData(): boolean {
    return (
        (navigator as Navigator & { connection?: { saveData?: boolean } })
            .connection?.saveData === true
    );
}

/** Duration for a JS-driven (Svelte) animation: `ms`, or 0 when motion is off. */
export function motionMs(ms: number): number {
    return prefersReducedMotion() ||
        (typeof document !== 'undefined' &&
            document.documentElement.dataset.motion === undefined)
        ? 0
        : ms;
}

/**
 * Turns storefront motion on; returns the cleanup that turns it off again.
 * Data Saver users get no extra motion at all.
 */
export function enableMotion(): () => void {
    const root = document.documentElement;

    if (saveData()) {
        return () => {};
    }

    root.dataset.motion = '';
    const stopReveal = enableScrollReveal();

    return () => {
        stopReveal();
        delete root.dataset.motion;
    };
}

/**
 * Attachment (`{@attach revealOnView}`): plays an element's entrance once, the
 * first time it scrolls into view. It sets `data-reveal="armed"` (start
 * state) and then `data-reveal="in"` (play); the styles live in motion.css.
 * It only arms an element that is still below the fold, so the
 * server-rendered content is never hidden without JavaScript and nothing
 * already on screen blinks out. motion.css applies the start state only
 * under `data-motion`, so Data Saver users simply see the content.
 */
export function revealOnView(node: HTMLElement) {
    if (
        prefersReducedMotion() ||
        typeof IntersectionObserver === 'undefined' ||
        node.getBoundingClientRect().top < window.innerHeight * 0.85
    ) {
        return;
    }

    node.dataset.reveal = 'armed';

    const observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                node.dataset.reveal = 'in';
                observer.disconnect();
            }
        },
        { threshold: 0.3 },
    );
    observer.observe(node);

    return () => observer.disconnect();
}

/** Elements that rise in on scroll; keep in sync with motion.css. */
const REVEAL_SELECTOR =
    '.product-card:not(.is-skeleton),.category-card:not(.is-skeleton),.section-head,.sf-reveal';

/**
 * Sections with their own choreographed entrance (revealOnView + motion.css,
 * e.g. the home page's editorial banner): the site-wide reveal leaves
 * everything inside them alone, so nothing animates twice.
 */
const OWN_MOTION = '.sf-own-motion';

/**
 * Scroll reveals (styles in motion.css). Elements below the fold are hidden
 * (.sf-pending) and play a timed rise once (.sf-in) as they enter, staggered
 * by --sf-i. Timed rather than scroll-linked, so items in a row visibly
 * follow one another at any scroll speed. Elements already on screen are
 * left alone, so nothing visible ever blinks out.
 */
function enableScrollReveal(): () => void {
    if (
        prefersReducedMotion() ||
        !('IntersectionObserver' in window)
    ) {
        return () => {};
    }

    const seen = new WeakSet<Element>();
    /** Hidden and waiting to scroll in. */
    const pending = new Set<Element>();
    const io = new IntersectionObserver(
        (entries) => {
            for (const { isIntersecting, target } of entries) {
                if (isIntersecting) {
                    io.unobserve(target);
                    pending.delete(target);
                    target.classList.replace('sf-pending', 'sf-in');
                }
            }
        },
        { rootMargin: '0px 0px -8% 0px' },
    );

    // New elements (page changes, "load more", filters) are picked up in one
    // batch per frame.
    let frame = 0;
    const scan = () => {
        frame = 0;
        const fold = window.innerHeight;

        // Let go of elements removed before they were ever seen.
        for (const el of pending) {
            if (!el.isConnected) {
                io.unobserve(el);
                pending.delete(el);
            }
        }

        for (const el of document.querySelectorAll(REVEAL_SELECTOR)) {
            if (seen.has(el)) {
                continue;
            }

            seen.add(el);

            if (
                !el.closest(OWN_MOTION) &&
                el.getBoundingClientRect().top >= fold
            ) {
                el.classList.add('sf-pending');
                pending.add(el);
                io.observe(el);
            }
        }
    };
    const mo = new MutationObserver(() => {
        frame ||= requestAnimationFrame(scan);
    });

    scan();
    mo.observe(document.body, { childList: true, subtree: true });

    return () => {
        cancelAnimationFrame(frame);
        mo.disconnect();
        io.disconnect();
        document
            .querySelectorAll('.sf-pending,.sf-in')
            .forEach((el) => el.classList.remove('sf-pending', 'sf-in'));
    };
}

/** Remember which card was clicked so its photo can morph into the product page. */
export function markMorph(slug: string) {
    motion.morphSlug = slug;
}

/** Destinations outside the storefront (they use other layouts). */
const NON_STOREFRONT =
    /^\/(admin|dashboard|settings|login|register|logout|forgot-password|reset-password|two-factor|email|user|confirm-password)(\/|$)/;

/**
 * Inertia `defaults.visitOptions`: turns on a view transition for page
 * changes between storefront pages while motion is on. Same-page visits
 * (filters, sorting, reloads) and form posts never get one.
 */
export function motionVisitOptions(
    href: string,
    options: VisitOptions,
): VisitOptions {
    if (typeof window === 'undefined') {
        return {};
    }

    const root = document.documentElement;
    const method = String(options.method ?? 'get').toLowerCase();

    if (
        root.dataset.motion === undefined ||
        method !== 'get' ||
        options.preserveState ||
        prefersReducedMotion()
    ) {
        return {};
    }

    const url = new URL(href, window.location.href);

    if (
        url.origin !== window.location.origin ||
        url.pathname === window.location.pathname ||
        NON_STOREFRONT.test(url.pathname)
    ) {
        return {};
    }

    return {
        viewTransition: (transition) => {
            // view-transition-names only exist while this class is on, so the
            // header and main never carry them outside a transition.
            root.classList.add('sf-vt');

            const done = () => {
                root.classList.remove('sf-vt');
                motion.morphSlug = null;
            };

            transition.finished.then(done, done);
        },
    };
}
