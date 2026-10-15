<script lang="ts">
    import { business } from '@/data/catalogue';

    let { title, description }: { title: string; description: string } =
        $props();

    const fullTitle = $derived(`${title} | ${business.name}`);
</script>

<svelte:head>
    <title>{fullTitle}</title>
    <meta name="description" content={description} />
    <meta property="og:title" content={fullTitle} />
    <meta property="og:description" content={description} />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
</svelte:head>
