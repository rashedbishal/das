<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import ContactWidgets from '@/components/ContactWidgets.svelte';
    import Footer from '@/components/storefront/Footer.svelte';
    import Header from '@/components/storefront/Header.svelte';
    import { openingHoursSpecification } from '@/lib/business';
    import { enableMotion } from '@/lib/motion.svelte';
    import { absoluteUrl, jsonLdScript } from '@/lib/seo';

    let { children }: { children?: Snippet } = $props();

    // Storefront motion applies only while the storefront is on screen.
    $effect(() => enableMotion());

    /**
     * The business as schema.org data on every storefront page: a local Store (with
     * address, map position and opening hours) when an address is set, otherwise an
     * Organization.
     */
    const organization = $derived.by(() => {
        const { siteSettings, seo } = page.props;
        const hours = openingHoursSpecification(siteSettings.openingHours);
        const sameAs = Object.values(siteSettings.socialLinks ?? {}).filter(Boolean);
        const hasAddress = Boolean(siteSettings.address);
        const logo = absoluteUrl(seo.appUrl, siteSettings.logo || '/logo.png');

        return jsonLdScript({
            '@context': 'https://schema.org',
            '@type': hasAddress ? 'Store' : 'Organization',
            '@id': `${seo.appUrl}/#business`,
            name: siteSettings.name,
            url: seo.appUrl,
            logo,
            ...(hasAddress ? { image: logo } : {}),
            ...(siteSettings.contactEmail ? { email: siteSettings.contactEmail } : {}),
            ...(siteSettings.contactPhone ? { telephone: siteSettings.contactPhone } : {}),
            ...(hasAddress
                ? {
                      address: {
                          '@type': 'PostalAddress',
                          streetAddress: siteSettings.address,
                          ...(siteSettings.addressCity ? { addressLocality: siteSettings.addressCity } : {}),
                          ...(siteSettings.addressRegion ? { addressRegion: siteSettings.addressRegion } : {}),
                          ...(siteSettings.postalCode ? { postalCode: siteSettings.postalCode } : {}),
                          addressCountry: 'BD',
                      },
                  }
                : {}),
            ...(siteSettings.latitude !== null && siteSettings.longitude !== null && siteSettings.latitude !== undefined
                ? {
                      geo: {
                          '@type': 'GeoCoordinates',
                          latitude: Number(siteSettings.latitude),
                          longitude: Number(siteSettings.longitude),
                      },
                  }
                : {}),
            ...(siteSettings.mapUrl ? { hasMap: siteSettings.mapUrl } : {}),
            ...(hasAddress && hours.length ? { openingHoursSpecification: hours } : {}),
            ...(sameAs.length ? { sameAs } : {}),
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
    {#if page.props.seo.googleSiteVerification}
        <meta name="google-site-verification" content={page.props.seo.googleSiteVerification} />
    {/if}
    {#if page.props.seo.bingSiteVerification}
        <meta name="msvalidate.01" content={page.props.seo.bingSiteVerification} />
    {/if}
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
