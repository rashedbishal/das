<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Phone from '@lucide/svelte/icons/phone';
    import Plus from '@lucide/svelte/icons/plus';
    import { mapCategory, type RawCategory } from '@/data/catalogue';
    import { dayLabel, formatTime, orderedHours } from '@/lib/business';
    import { FALLBACK_CONTACT, resolvePhone } from '@/lib/contact';
    import { about, brands, catalogue, contact, home } from '@/routes';
    import { show as categoryShow } from '@/routes/categories';
    import Wordmark from './Wordmark.svelte';

    // Shared by the server (HandleInertiaRequests), so these links are in the HTML.
    const footerCategories = $derived(
        ((page.props.navCategories as RawCategory[] | undefined) ?? []).map(mapCategory).slice(0, 8),
    );

    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
    const email = $derived(
        page.props.siteSettings.contactEmail || FALLBACK_CONTACT.email,
    );
    const hoursSummary = $derived(
        orderedHours(page.props.siteSettings.openingHours)
            .filter((entry) => !entry.closed)
            .map((entry) => `${dayLabel(entry.day).slice(0, 3)} ${formatTime(entry.opens)}–${formatTime(entry.closes)}`)
            .join(' · '),
    );
</script>

<footer class="footer">
    <div class="container-wide pt-16 pb-8">
        <div
            class="grid grid-cols-2 gap-x-8 gap-y-10 lg:grid-cols-[1.5fr_1fr_1fr_1.1fr]"
        >
            <div class="col-span-2 lg:col-span-1">
                <Link href={home.url()} class="footer-brand-link">
                    <img
                        src="/logo-transparent.png"
                        alt="DAS logo"
                        class="h-10 w-auto max-w-[220px] object-contain sm:h-12"
                    />
                </Link>
                <p class="mt-6 max-w-[270px] text-xs leading-7 opacity-65">
                    Precision equipment. Professional guidance. A considered
                    approach to equipping modern dental practices.
                </p>
                <div
                    class="mt-5 inline-flex items-center gap-2 text-[10px] opacity-65"
                >
                    <Plus class="size-3" />
                    Built around your practice.
                </div>
            </div>
            <div>
                <h3 class="footer-heading">Explore equipment</h3>
                <div class="footer-links">
                    {#each footerCategories as c (c.id)}
                        <Link href={categoryShow.url(c.slug)}>
                            {c.name}
                        </Link>
                    {/each}
                </div>
            </div>
            <div>
                <h3 class="footer-heading">DAS</h3>
                <div class="footer-links">
                    <Link href={about.url()}>About Us</Link>
                    <Link href={brands.url()}>Our Brands</Link>
                    <Link href={catalogue.url()}>Product Catalogue</Link>
                    <Link href={contact.url()}>Contact Us</Link>
                    <Link href={`${contact.url({ query: { quote: 1 } })}#enquiry`}>Request a Quote</Link>
                </div>
            </div>
            <div class="col-span-2 sm:col-span-1">
                <h3 class="footer-heading">Let’s talk equipment</h3>
                <div class="footer-links">
                    <a
                        href="tel:{phone.tel}"
                        class="flex items-center gap-2"
                    >
                        <Phone class="size-3.5" />
                        {phone.display}
                    </a>
                    <a href="mailto:{email}">{email}</a>
                    {#if page.props.siteSettings.address}
                        <span class="leading-6 opacity-65">
                            {[page.props.siteSettings.address, page.props.siteSettings.addressCity]
                                .filter(Boolean)
                                .join(', ')}
                        </span>
                    {/if}
                    {#if hoursSummary}
                        <span class="leading-6 opacity-65">{hoursSummary}</span>
                    {:else}
                        <span class="leading-6 opacity-65">{FALLBACK_CONTACT.hours}</span>
                    {/if}
                </div>
            </div>
        </div>
        <div
            class="mt-14 flex flex-col justify-between gap-4 border-t border-foreground/15 pt-6 text-[10px] opacity-55 sm:flex-row"
        >
            <span>
                © {new Date().getFullYear()}
                {page.props.siteSettings.name}. All rights reserved.
            </span>
            <span>
                Developed by
                <a
                    href="https://opticoresolutionsltd.com"
                    target="_blank"
                    rel="noopener"
                    class="underline-offset-2 hover:underline"
                >
                    Opticore Solutions Limited
                </a>
            </span>
            <span>Dental products and product guidance</span>
        </div>
    </div>
</footer>
