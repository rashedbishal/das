import { cubicInOut, cubicOut } from 'svelte/easing';

export function lockScroll() {
    const previous = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    return () => {
        document.body.style.overflow = previous;
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

export function zoom(_node: Element, { duration = 200 } = {}) {
    return {
        duration,
        easing: cubicOut,
        css: (t: number) =>
            `opacity: ${t}; transform: scale(${0.95 + 0.05 * t});`,
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
