<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title = '',
        children,
    }: {
        title?: string;
        children?: Snippet;
    } = $props();

    const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
    const fullTitle = $derived(title ? `${title} - ${appName}` : appName);
</script>

<!-- Used by the CMS and sign-in/account pages, none of which belong in search results. -->
<svelte:head>
    <title>{fullTitle}</title>
    <meta name="robots" content="noindex, nofollow" />
    {@render children?.()}
</svelte:head>
