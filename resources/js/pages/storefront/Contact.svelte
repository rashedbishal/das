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
    import { resolvePhone } from '@/lib/contact';
    import { contact } from '@/routes';

    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
    const email = $derived(page.props.siteSettings.contactEmail || null);
    const address = $derived(
        [
            page.props.siteSettings.address,
            page.props.siteSettings.addressCity,
            page.props.siteSettings.addressRegion,
            page.props.siteSettings.postalCode,
        ]
            .filter(Boolean)
            .join(', '),
    );
    const headlineWords = 'Let’s talk about your practice.'.split(' ');

    const hours = $derived(orderedHours(page.props.siteSettings.openingHours));

    // The footer's "Request a Quote" link opens this page at the form, framed as a quote request.
    const isQuoteRequest = $derived(new URL(page.url, 'http://localhost').searchParams.get('quote') === '1');

    // Only the details set in the CMS (Settings → General) are listed.
    const details = $derived(
        [
            phone && {
                icon: Phone,
                label: 'Call our sales team',
                value: phone.display,
                href: `tel:${phone.tel}`,
            },
            email && {
                icon: Mail,
                label: 'Email us',
                value: email,
                href: `mailto:${email}`,
            },
            address && {
                icon: MapPin,
                label: 'Visit us',
                value: address,
                href: page.props.siteSettings.mapUrl ?? undefined,
            },
        ].filter((detail) => !!detail),
    );
</script>

<PageMeta
    title="Contact & Product Enquiries"
    description="Talk to DAS about your dental product requirements and send an enquiry."
    canonical={contact.url()}
/>

<section class="contact-page container-wide pt-10 pb-20">
    <Breadcrumb items={[{ label: 'Contact Us' }]} />
<<<<<<< HEAD
    <p class="contact-eyebrow eyebrow mb-3">
        A conversation is a good place to start
    </p>
    <h1 class="page-heading">
        {#each headlineWords as word, i (i)}
            <span class="contact-word"><span style:--i={i}>{word}</span></span
            >{' '}
        {/each}
    </h1>
    <p class="contact-intro section-intro max-w-lg">
=======
    <p class="sf-enter eyebrow mb-3">A conversation is a good place to start</p>
    <h1 class="sf-enter page-heading" style:--sf-i={1}>Let’s talk about your practice.</h1>
    <p class="sf-enter section-intro max-w-lg" style:--sf-i={2}>
>>>>>>> 39c0ea4 (animation: added in pages)
        A product question, a new clinical setup, or a little guidance on what
        comes next. We’re here to help.
    </p>
    <div class="mt-12 grid gap-12 lg:grid-cols-[.85fr_1.15fr] lg:gap-24">
        <div class="contact-direct">
            <h2 class="mb-7 text-xl">Direct access. Personal assistance.</h2>
            <div class="space-y-7">
                {#each details as { icon: Icon, label, value, href }, i (label)}
                    <div class="contact-detail flex gap-4" style:--i={i}>
                        <span
                            class="contact-detail-icon relative flex size-10 shrink-0 items-center justify-center rounded-md bg-accent"
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
                <div class="contact-hours mt-9 rounded-md border bg-secondary p-5">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold">
                        <Clock class="size-4 text-primary" />
                        Business hours
                    </h3>
                    <dl class="space-y-1.5 text-xs">
                        {#each hours as entry, i (entry.day)}
                            <div class="flex justify-between gap-4" style:--i={i}>
                                <dt class="text-muted-foreground">{dayLabel(entry.day)}</dt>
                                <dd>{entry.closed ? 'Closed' : `${formatTime(entry.opens)} – ${formatTime(entry.closes)}`}</dd>
                            </div>
                        {/each}
                    </dl>
                </div>
            {/if}
        </div>
        <div
            id="enquiry"
            class="contact-enquiry relative scroll-mt-24 lg:border-l lg:pl-12"
        >
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

<style>
    /*
     * Contact page entrance. Pure CSS, so it plays straight from the
     * server-rendered page with no flash; only opacity, transforms and
     * (briefly) filter/clip-path animate, and reduced-motion users see the
     * page at rest. Order: breadcrumb and eyebrow, the headline word by word
     * out of a mask, the intro; then the contact details (each icon tile
     * pops in and sends out one soft ring), the business hours card with its
     * rows cascading; and alongside, the enquiry column, whose divider is
     * drawn top to bottom before its heading and fields rise in turn and the
     * send button catches a sheen. The thank-you panel blooms in after a
     * successful send.
     */
    .contact-page {
        --contact-ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    .contact-word {
        display: inline-block;
        overflow: hidden;
        padding-bottom: 0.12em;
        margin-bottom: -0.12em;
        vertical-align: top;
    }
    .contact-word > span {
        display: inline-block;
    }

    .contact-detail-icon::after {
        content: '';
        position: absolute;
        inset: 0;
        border: 1px solid var(--primary);
        border-radius: inherit;
        opacity: 0;
        pointer-events: none;
    }

    /* The enquiry column's divider, drawn over the plain border (large screens). */
    @media (min-width: 1024px) {
        .contact-enquiry::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: -1px;
            width: 1px;
            background: var(--primary);
            transform-origin: top;
            opacity: 0;
            pointer-events: none;
        }
    }

    /* Field labels pick up the brand colour while their field has focus. */
    .contact-enquiry :global(.form-label) {
        transition: color 0.3s var(--contact-ease);
    }
    .contact-enquiry :global(label:focus-within .form-label) {
        color: var(--primary);
    }

    .contact-enquiry :global(button[type='submit']) {
        position: relative;
        overflow: hidden;
        isolation: isolate;
    }
    .contact-enquiry :global(button[type='submit'])::after {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 40%;
        z-index: -1;
        pointer-events: none;
        background: linear-gradient(
            100deg,
            rgb(255 255 255 / 0) 0%,
            rgb(255 255 255 / 0.35) 50%,
            rgb(255 255 255 / 0) 100%
        );
        transform: translateX(-120%) skewX(-18deg);
    }

    @media (prefers-reduced-motion: no-preference) {
    .contact-enquiry :global(form > :nth-child(2)) {
        --i: 0;
    }
    .contact-enquiry :global(form > :nth-child(3)) {
        --i: 1;
    }
    .contact-enquiry :global(form > :nth-child(4)) {
        --i: 2;
    }
    .contact-enquiry :global(form > :nth-child(5)) {
        --i: 3;
    }
    .contact-enquiry :global(form > :nth-child(6)) {
        --i: 4;
    }
    .contact-enquiry :global(form > :nth-child(7)) {
        --i: 5;
    }
    .contact-enquiry :global(form .grid > :nth-child(1)) {
        --j: 0;
    }
    .contact-enquiry :global(form .grid > :nth-child(2)) {
        --j: 1;
    }
    .contact-enquiry :global(form .grid > :nth-child(3)) {
        --j: 2;
    }
    .contact-enquiry :global(form .grid > :nth-child(4)) {
        --j: 3;
    }

        /* Header */
        .contact-page > :global(.breadcrumb) {
            animation: contact-fade 0.8s var(--contact-ease) backwards;
        }
        .contact-eyebrow {
            animation: contact-slide 0.9s var(--contact-ease) 0.1s backwards;
        }
        .contact-word > span {
            animation: contact-word 1.1s var(--contact-ease)
                calc(0.2s + var(--i) * 75ms) backwards;
        }
        .contact-intro {
            animation: contact-focus 1.1s var(--contact-ease) 0.6s backwards;
        }

        /* Direct contact column */
        .contact-direct > h2 {
            animation: contact-rise 1s var(--contact-ease) 0.75s backwards;
        }
        .contact-detail {
            --delay: calc(0.9s + var(--i) * 120ms);
        }
        .contact-detail-icon {
            animation: contact-pop 0.9s var(--contact-ease) var(--delay)
                backwards;
        }
        .contact-detail-icon::after {
            animation: contact-ring 1.4s ease-out calc(var(--delay) + 0.35s);
        }
        .contact-detail > div {
            animation: contact-slide 1s var(--contact-ease)
                calc(var(--delay) + 0.1s) backwards;
        }
        .contact-hours {
            animation: contact-rise 1.1s var(--contact-ease) 1.35s backwards;
        }
        .contact-hours dl > div {
            animation: contact-fade 0.7s var(--contact-ease)
                calc(1.5s + var(--i) * 55ms) backwards;
        }

        /* Enquiry column */
        .contact-enquiry::before {
            animation: contact-divider 1.8s var(--contact-ease) 0.6s;
        }
        .contact-enquiry > :global(h2) {
            animation: contact-rise 1s var(--contact-ease) 0.85s backwards;
        }
        .contact-enquiry > :global(h2 + p) {
            animation: contact-rise 1s var(--contact-ease) 0.95s backwards;
        }
        .contact-enquiry :global(form > *:not(.grid)) {
            animation: contact-rise 1s var(--contact-ease)
                calc(1.05s + var(--i) * 90ms) backwards;
        }
        .contact-enquiry :global(form .grid > *) {
            animation: contact-rise 1s var(--contact-ease)
                calc(1.05s + var(--j) * 80ms) backwards;
        }
        .contact-enquiry :global(button[type='submit'])::after {
            animation: contact-sheen 1.2s var(--contact-ease) 2.3s;
        }
        .contact-enquiry :global([role='status']) {
            animation: contact-bloom 0.9s var(--contact-ease) backwards;
        }
    }

    @keyframes contact-fade {
        from {
            opacity: 0;
        }
    }
    @keyframes contact-slide {
        from {
            opacity: 0;
            translate: -14px 0;
        }
    }
    @keyframes contact-rise {
        from {
            opacity: 0;
            translate: 0 14px;
        }
    }
    @keyframes contact-word {
        from {
            translate: 0 110%;
            filter: blur(4px);
        }
    }
    @keyframes contact-focus {
        from {
            opacity: 0;
            filter: blur(8px);
            translate: 0 8px;
        }
    }
    @keyframes contact-pop {
        from {
            opacity: 0;
            scale: 0.6;
            rotate: -12deg;
        }
    }
    @keyframes contact-ring {
        from {
            opacity: 0.7;
            scale: 1;
        }
        to {
            opacity: 0;
            scale: 1.7;
        }
    }
    @keyframes contact-divider {
        0% {
            opacity: 1;
            transform: scaleY(0);
        }
        65% {
            opacity: 1;
            transform: scaleY(1);
        }
        100% {
            opacity: 0;
            transform: scaleY(1);
        }
    }
    @keyframes contact-sheen {
        to {
            transform: translateX(320%) skewX(-18deg);
        }
    }
    @keyframes contact-bloom {
        from {
            opacity: 0;
            scale: 0.96;
            filter: blur(6px);
        }
    }
</style>
