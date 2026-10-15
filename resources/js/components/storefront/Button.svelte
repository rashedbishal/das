<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { HTMLButtonAttributes } from 'svelte/elements';
    import { buttonClass } from './button';
    import type { ButtonSize, ButtonVariant } from './button';

    let {
        variant = 'default',
        size = 'default',
        class: className = '',
        type = 'button',
        children,
        ...rest
    }: HTMLButtonAttributes & {
        variant?: ButtonVariant;
        size?: ButtonSize;
        class?: string;
        children?: Snippet;
    } = $props();
</script>

<button
    {type}
    class={buttonClass({ variant, size, class: className })}
    {...rest}
>
    {@render children?.()}
</button>
