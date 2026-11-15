<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import { absoluteUrl, jsonLdScript } from '@/lib/seo';
    import { home } from '@/routes';

    let { items }: { items: { label: string; href?: string }[] } = $props();

    const structuredData = $derived.by(() => {
        const appUrl = page.props.seo.appUrl;
        const trail = [{ label: 'Home', href: home.url() }, ...items];

        return jsonLdScript({
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            itemListElement: trail.map((item, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                name: item.label,
                item: absoluteUrl(appUrl, item.href ?? page.url),
            })),
        });
    });
</script>

<svelte:head>
    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
    {@html structuredData}
</svelte:head>

<nav aria-label="Breadcrumb" class="breadcrumb">
    <Link href={home.url()}>Home</Link>
    {#each items as item, n (n)}
        <span class="contents">
            <span class="opacity-50">/</span>
            {#if item.href}
                <Link href={item.href} class="hover:text-primary">
                    {item.label}
                </Link>
            {:else}
                <span class="text-foreground">{item.label}</span>
            {/if}
        </span>
    {/each}
</nav>
