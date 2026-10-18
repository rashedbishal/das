<script lang="ts">
    import type { LinkComponentBaseProps, Method } from '@inertiajs/core';
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        href,
        tabindex,
        method,
        as,
        class: className = '',
        children,
        ...rest
    }: {
        href: LinkComponentBaseProps['href'];
        tabindex?: number;
        method?: Method;
        as?: keyof HTMLElementTagNameMap;
        class?: string;
        children?: Snippet;
        [key: string]: unknown;
    } = $props();
</script>

<Link
    {href}
    {tabindex}
    {method}
    {as}
    class={cn('font-medium text-primary hover:underline', className)}
    {...rest}
>
    {@render children?.()}
</Link>
