<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Headphones from '@lucide/svelte/icons/headphones';
    import Layers3 from '@lucide/svelte/icons/layers';
    import ScanLine from '@lucide/svelte/icons/scan-line';
    import hero from '@/assets/storefront/dental-suite.jpg';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import { buttonClass } from '@/components/storefront/button';
    import ContactBanner from '@/components/storefront/ContactBanner.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { contact } from '@/routes';

    const principles = [
        {
            icon: Layers3,
            title: 'A focused selection',
            text: 'Clinical essentials and specialist technology, organised around the work you do.',
        },
        {
            icon: ScanLine,
            title: 'Clarity before commitment',
            text: 'Useful details, understandable specifications and room to ask the important questions.',
        },
        {
            icon: Headphones,
            title: 'A human point of contact',
            text: 'A direct conversation about your requirements, your workflow and your next step.',
        },
    ];
</script>

<PageMeta
    title="About Us"
    description="Learn about DAS and our approach to helping dental professionals find suitable products."
/>

<div class="container-wide pt-10">
    <Breadcrumb items={[{ label: 'About DAS' }]} />
</div>

<section
    class="relative isolate flex min-h-[510px] items-center overflow-hidden bg-secondary"
>
    <img
        src={hero}
        class="absolute inset-0 -z-20 h-full w-full object-cover object-[65%] opacity-35"
        alt="A modern dental treatment suite"
        width={1920}
        height={1024}
    />
    <div class="container-wide py-16">
        <p class="eyebrow mb-5">Meet DAS</p>
        <h1 class="max-w-[650px] text-[44px] leading-[1.1] md:text-[60px]">
            Better equipment.
            <br />A more personal approach.
        </h1>
        <p class="mt-7 max-w-md text-sm leading-8">
            We believe choosing dental equipment should feel considered, clear
            and human. Your practice deserves nothing less.
        </p>
        <Link href={contact.url()} class={buttonClass({ class: 'mt-8 h-11' })}>
            Let’s start a conversation
            <ArrowUpRight />
        </Link>
    </div>
</section>

<section class="container-wide section-space">
    <div class="grid gap-10 md:grid-cols-2 lg:gap-24">
        <div>
            <p class="eyebrow mb-4">Our perspective</p>
            <h2 class="section-heading">
                Supporting the people
                <br />
                behind better dental care.
            </h2>
        </div>
        <div>
            <p class="text-sm leading-8 text-muted-foreground">
                DAS helps dental professionals explore products for clinics,
                laboratories and everyday practice needs.
            </p>
            <p class="mt-5 text-sm leading-8 text-muted-foreground">
                We focus on clear product information and direct assistance,
                so each buying decision can start with the right conversation.
            </p>
        </div>
    </div>
    <div class="mt-16 grid gap-8 md:grid-cols-3">
        {#each principles as { icon: Icon, title, text } (title)}
            <div class="border-t pt-7">
                <Icon class="mb-6 size-7 text-primary" strokeWidth={1.3} />
                <h3 class="text-lg">{title}</h3>
                <p class="mt-3 text-xs leading-7 text-muted-foreground">{text}</p>
            </div>
        {/each}
    </div>
</section>

<ContactBanner />
