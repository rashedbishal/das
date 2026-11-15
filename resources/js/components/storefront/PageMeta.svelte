<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { absoluteUrl } from '@/lib/seo';

    let {
        title,
        description = null,
        image = null,
        canonical = null,
        noindex = false,
    }: {
        title: string;
        description?: string | null;
        image?: string | null;
        canonical?: string | null;
        noindex?: boolean;
    } = $props();

    const seo = $derived(page.props.seo);
    const fullTitle = $derived(`${title} | ${page.props.siteSettings.name}`);
    const metaDescription = $derived(
        description?.trim() || seo.defaultDescription || '',
    );
    const canonicalUrl = $derived(canonical || seo.canonicalUrl);
    const imageUrl = $derived(image ? absoluteUrl(seo.appUrl, image) : null);
</script>

<svelte:head>
    <title>{fullTitle}</title>
    {#if metaDescription}
        <meta name="description" content={metaDescription} />
        <meta property="og:description" content={metaDescription} />
    {/if}
    {#if noindex}
        <meta name="robots" content="noindex, follow" />
    {:else}
        <link rel="canonical" href={canonicalUrl} />
    {/if}
    <meta property="og:title" content={fullTitle} />
    <meta property="og:url" content={canonicalUrl} />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content={page.props.siteSettings.name} />
    {#if imageUrl}
        <meta property="og:image" content={imageUrl} />
    {/if}
    <meta
        name="twitter:card"
        content={imageUrl ? 'summary_large_image' : 'summary'}
    />
</svelte:head>
