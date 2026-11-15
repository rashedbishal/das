<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { absoluteUrl } from '@/lib/seo';

    let {
        title,
        description = null,
        image = null,
        canonical = null,
        noindex = false,
        type = 'website',
        exactTitle = false,
    }: {
        title: string;
        /** Use the title as-is, without the " | Site name" suffix (e.g. the home page). */
        exactTitle?: boolean;
        description?: string | null;
        image?: string | null;
        /** Path or absolute URL of the preferred version of this page (query string dropped by default). */
        canonical?: string | null;
        noindex?: boolean;
        type?: 'website' | 'product' | 'article';
    } = $props();

    const seo = $derived(page.props.seo);
    const fullTitle = $derived(exactTitle ? title : `${title} | ${page.props.siteSettings.name}`);
    const metaDescription = $derived(
        (description?.trim() || seo.defaultDescription || '').replace(/\s+/g, ' ').slice(0, 300),
    );
    // Always absolute: search engines and social networks expect full URLs.
    const canonicalUrl = $derived(absoluteUrl(seo.appUrl, canonical || seo.canonicalUrl));
    const imageUrl = $derived(image ? absoluteUrl(seo.appUrl, image) : null);
    // Internal search results are thin, near-duplicate pages; keep them out of the index.
    const isSearchResult = $derived(new URL(page.url, 'http://localhost').searchParams.has('q'));
    const robots = $derived(noindex || isSearchResult ? 'noindex, follow' : 'index, follow, max-image-preview:large');
</script>

<svelte:head>
    <title>{fullTitle}</title>
    {#if metaDescription}
        <meta name="description" content={metaDescription} />
        <meta property="og:description" content={metaDescription} />
        <meta name="twitter:description" content={metaDescription} />
    {/if}
    <meta name="robots" content={robots} />
    {#if !noindex}
        <link rel="canonical" href={canonicalUrl} />
    {/if}
    <meta property="og:title" content={fullTitle} />
    <meta name="twitter:title" content={fullTitle} />
    <meta property="og:url" content={canonicalUrl} />
    <meta property="og:type" content={type} />
    <meta property="og:site_name" content={page.props.siteSettings.name} />
    <meta property="og:locale" content="en_US" />
    {#if imageUrl}
        <meta property="og:image" content={imageUrl} />
        <meta name="twitter:image" content={imageUrl} />
    {/if}
    <meta name="twitter:card" content={imageUrl ? 'summary_large_image' : 'summary'} />
</svelte:head>
