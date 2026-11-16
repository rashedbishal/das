import { cubicInOut, cubicOut } from 'svelte/easing';

let scrollLocks = 0;
let overflowBeforeLock = '';

/**
 * Stops the page scrolling behind a modal. Counted, so one modal closing while
 * another opens (search result → login modal) can't leave the page stuck.
 */
export function lockScroll() {
    if (scrollLocks++ === 0) {
        overflowBeforeLock = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
    }

    let released = false;

    return () => {
        if (released) {
            return;
        }

        released = true;

        if (--scrollLocks === 0) {
            document.body.style.overflow = overflowBeforeLock;
        }
    };
}

export function focusFirst(container: HTMLElement | undefined) {
    const target =
        container?.querySelector<HTMLElement>('[data-autofocus]') ??
        container?.querySelector<HTMLElement>(
            'input, select, textarea, button, a[href]',
        );

    target?.focus();
}

export function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

/** Closing counterpart of the `.modal-panel` opening animation in Modal.svelte. */
export function zoom(_node: Element, { duration = 200 } = {}) {
    return {
        duration: prefersReducedMotion() ? 0 : duration,
        easing: cubicOut,
        css: (t: number) =>
            `opacity: ${t}; transform: translateY(${(1 - t) * 12}px) scale(${0.96 + 0.04 * t});`,
    };
}

export function slide(
    _node: Element,
    {
        side = 'right',
        duration = 500,
    }: { side?: 'left' | 'right'; duration?: number } = {},
) {
    const direction = side === 'left' ? -1 : 1;

    return {
        duration,
        easing: cubicInOut,
        css: (t: number) =>
            `transform: translateX(${(1 - t) * 100 * direction}%);`,
    };
}
