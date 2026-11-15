<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import ContactWidgets from '@/components/ContactWidgets.svelte';
    import Footer from '@/components/storefront/Footer.svelte';
    import Header from '@/components/storefront/Header.svelte';
    import { absoluteUrl, jsonLdScript } from '@/lib/seo';

    let { children }: { children?: Snippet } = $props();

    const organization = $derived.by(() => {
        const { siteSettings, seo } = page.props;

        return jsonLdScript({
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: siteSettings.name,
            url: seo.appUrl,
            logo: absoluteUrl(seo.appUrl, siteSettings.logo || '/logo.png'),
            ...(siteSettings.contactEmail ? { email: siteSettings.contactEmail } : {}),
            ...(siteSettings.contactPhone ? { telephone: siteSettings.contactPhone } : {}),
            ...(siteSettings.address ? { address: siteSettings.address } : {}),
        });
    });
</script>

<svelte:head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
    />
    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
    {@html organization}
</svelte:head>

<div class="storefront">
    <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-background focus:p-3"
    >
        Skip to content
    </a>
    <Header />
    <main id="main-content">
        {@render children?.()}
    </main>
    <Footer />
    <ContactWidgets />
</div>
