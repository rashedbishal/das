<script lang="ts">
    import type { Snippet } from 'svelte';
    import ContactWidgets from '@/components/ContactWidgets.svelte';
    import Footer from '@/components/storefront/Footer.svelte';
    import Header from '@/components/storefront/Header.svelte';

    let { children }: { children?: Snippet } = $props();
</script>

<svelte:head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
    />
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
