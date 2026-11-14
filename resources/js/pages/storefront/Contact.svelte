<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Clock from '@lucide/svelte/icons/clock';
    import Mail from '@lucide/svelte/icons/mail';
    import MapPin from '@lucide/svelte/icons/map-pin';
    import Phone from '@lucide/svelte/icons/phone';
    import Breadcrumb from '@/components/storefront/Breadcrumb.svelte';
    import EnquiryForm from '@/components/storefront/EnquiryForm.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { FALLBACK_CONTACT, resolvePhone } from '@/lib/contact';

    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
    const email = $derived(
        page.props.siteSettings.contactEmail || FALLBACK_CONTACT.email,
    );
    const address = $derived(
        page.props.siteSettings.address || FALLBACK_CONTACT.address,
    );

    const details = $derived([
        {
            icon: Phone,
            label: 'Call our sales team',
            value: phone.display,
            href: `tel:${phone.tel}`,
        },
        {
            icon: Mail,
            label: 'Email us',
            value: email,
            href: `mailto:${email}`,
        },
        { icon: MapPin, label: 'Visit us', value: address },
        { icon: Clock, label: 'Business hours', value: FALLBACK_CONTACT.hours },
    ]);
</script>

<PageMeta
    title="Contact & Product Enquiries"
    description="Talk to DAS about your dental product requirements and send an enquiry."
/>

<section class="container-wide pt-10 pb-20">
    <Breadcrumb items={[{ label: 'Contact Us' }]} />
    <p class="eyebrow mb-3">A conversation is a good place to start</p>
    <h1 class="page-heading">Let’s talk about your practice.</h1>
    <p class="section-intro max-w-lg">
        A product question, a new clinical setup, or a little guidance on what
        comes next. We’re here to help.
    </p>
    <div class="mt-12 grid gap-12 lg:grid-cols-[.85fr_1.15fr] lg:gap-24">
        <div>
            <h2 class="mb-7 text-xl">Direct access. Personal assistance.</h2>
            <div class="space-y-7">
                {#each details as { icon: Icon, label, value, href } (label)}
                    <div class="flex gap-4">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-md bg-accent"
                        >
                            <Icon class="size-4 text-primary" />
                        </span>
                        <div>
                            <h3 class="mb-2 text-[11px] text-muted-foreground">
                                {label}
                            </h3>
                            {#if href}
                                <a
                                    {href}
                                    class="inline-flex items-center gap-2 text-sm font-medium break-all"
                                >
                                    {value}
                                    <ArrowUpRight
                                        class="size-3 shrink-0 text-primary"
                                    />
                                </a>
                            {:else}
                                <p class="text-xs leading-6">{value}</p>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>
            <div class="mt-9 rounded-md border bg-secondary p-5">
                <p class="eyebrow mb-2">Contact information</p>
                <p class="text-[11px] leading-7 text-muted-foreground">
                    Phone and email are maintained in CMS settings. Address
                    and business hours should be confirmed with the team.
                </p>
            </div>
        </div>
        <div class="lg:border-l lg:pl-12">
            <h2 class="mb-3 text-xl">Tell us what you’re looking for.</h2>
            <p class="mb-7 text-xs leading-7 text-muted-foreground">
                Send us your details and our team will follow up about your requirements.
            </p>
            <EnquiryForm />
        </div>
    </div>
</section>
