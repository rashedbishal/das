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
    import { dayLabel, formatTime, orderedHours } from '@/lib/business';
    import { FALLBACK_CONTACT, resolvePhone } from '@/lib/contact';
    import { contact } from '@/routes';

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
        [
            page.props.siteSettings.address,
            page.props.siteSettings.addressCity,
            page.props.siteSettings.addressRegion,
            page.props.siteSettings.postalCode,
        ]
            .filter(Boolean)
            .join(', ') || FALLBACK_CONTACT.address,
    );
    const hours = $derived(orderedHours(page.props.siteSettings.openingHours));

    // The footer's "Request a Quote" link opens this page at the form, framed as a quote request.
    const isQuoteRequest = $derived(new URL(page.url, 'http://localhost').searchParams.get('quote') === '1');

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
        {
            icon: MapPin,
            label: 'Visit us',
            value: address,
            href: page.props.siteSettings.mapUrl ?? undefined,
        },
        ...(hours.length
            ? []
            : [{ icon: Clock, label: 'Business hours', value: FALLBACK_CONTACT.hours, href: undefined }]),
    ]);
</script>

<PageMeta
    title="Contact & Product Enquiries"
    description="Talk to DAS about your dental product requirements and send an enquiry."
    canonical={contact.url()}
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
            {#if hours.length}
                <div class="mt-9 rounded-md border bg-secondary p-5">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold">
                        <Clock class="size-4 text-primary" />
                        Business hours
                    </h3>
                    <dl class="space-y-1.5 text-xs">
                        {#each hours as entry (entry.day)}
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">{dayLabel(entry.day)}</dt>
                                <dd>{entry.closed ? 'Closed' : `${formatTime(entry.opens)} – ${formatTime(entry.closes)}`}</dd>
                            </div>
                        {/each}
                    </dl>
                </div>
            {/if}
        </div>
        <div id="enquiry" class="scroll-mt-24 lg:border-l lg:pl-12">
            {#if isQuoteRequest}
                <h2 class="mb-3 text-xl">Request a quote.</h2>
                <p class="mb-7 text-xs leading-7 text-muted-foreground">
                    Tell us which products and quantities you need, and our team will send you a quote.
                </p>
            {:else}
                <h2 class="mb-3 text-xl">Tell us what you’re looking for.</h2>
                <p class="mb-7 text-xs leading-7 text-muted-foreground">
                    Send us your details and our team will follow up about your requirements.
                </p>
            {/if}
            <EnquiryForm
                messagePlaceholder={isQuoteRequest
                    ? 'Which products do you need a quote for, and how many?'
                    : undefined}
            />
        </div>
    </div>
</section>
