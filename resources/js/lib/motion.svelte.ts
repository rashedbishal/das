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

    if (!saveData()) {
        root.dataset.motion = '';
    }

    return () => {
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
