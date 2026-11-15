<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import { catalogue, home } from '@/routes';
    import { buttonClass } from './button';
    import PageMeta from './PageMeta.svelte';
</script>

<PageMeta
    title="Page not found"
    description="Let’s help you find the right equipment instead."
    noindex
/>

<div class="container-wide py-24 text-center lg:py-36">
    <p class="eyebrow">A little off course</p>
    <div class="mt-5 text-[120px] leading-none font-semibold text-primary/20">
        404
    </div>
    <h1 class="page-heading mt-5">This page isn’t in our catalogue.</h1>
    <p class="section-intro">Let’s help you find the right equipment instead.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <Link href={catalogue.url()} class={buttonClass()}>
            Explore Equipment
            <ArrowRight />
        </Link>
        <Link href={home.url()} class={buttonClass({ variant: 'outline' })}>
            Back to Home
        </Link>
    </div>
</div>
