<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { promptLogin } from '@/lib/login-prompt.svelte';
    import type { LoginPromptProduct } from '@/lib/login-prompt.svelte';

    /**
     * A link to a product page. Product pages need sign-in, so for guests a plain
     * click opens the login modal on the current page instead of navigating (a
     * modified click, e.g. "open in new tab", still follows the link).
     */
    let {
        href,
        product,
        onclick,
        children,
        ...rest
    }: {
        href: string;
        product: LoginPromptProduct;
        onclick?: () => void;
        children?: Snippet;
        [key: string]: unknown;
    } = $props();

    const isGuest = $derived(!page.props.auth.user);

    function promptGuest(event: MouseEvent) {
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();
        onclick?.();
        promptLogin(href, product);
    }
</script>

{#if isGuest}
    <a {href} {...rest} onclick={promptGuest}>{@render children?.()}</a>
{:else}
    <Link {href} {onclick} {...rest}>{@render children?.()}</Link>
{/if}
