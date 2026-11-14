<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Phone from '@lucide/svelte/icons/phone';
    import Plus from '@lucide/svelte/icons/plus';
    import { categories, loadCategories } from '@/data/catalogue';
    import { FALLBACK_CONTACT, resolvePhone } from '@/lib/contact';
    import { about, brands, catalogue, contact, home } from '@/routes';
    import Wordmark from './Wordmark.svelte';

    loadCategories();

    const phone = $derived(
        resolvePhone(
            page.props.contactWidgets.whatsapp,
            page.props.siteSettings.contactPhone,
        ),
    );
    const email = $derived(
        page.props.siteSettings.contactEmail || FALLBACK_CONTACT.email,
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
                    {#each $categories.slice(0, 5) as c (c.id)}
                        <Link href={catalogue.url({ query: { category: c.slug } })}>
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
                    <Link href={contact.url()}>Request a Quote</Link>
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
                    <span class="leading-6 opacity-65">{FALLBACK_CONTACT.hours}</span>
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
            <span>Dental products and product guidance</span>
        </div>
    </div>
</footer>
