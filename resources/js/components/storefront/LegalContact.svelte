<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import { resolvePhone } from '@/lib/contact';
    import { contact } from '@/routes';

    /**
     * The business's contact details for the legal pages, taken from
     * Admin → Settings (nothing hard-coded). Details that are not set are left
     * out; the Contact page link is always offered.
     */
    const settings = $derived(page.props.siteSettings);
    const phone = $derived(resolvePhone(page.props.contactWidgets.whatsapp, settings.contactPhone));
    const address = $derived(
        [settings.address, settings.addressCity, settings.addressRegion, settings.postalCode]
            .filter(Boolean)
            .join(', '),
    );
</script>

<ul>
    <li><strong>Business:</strong> {settings.name}</li>
    {#if settings.contactEmail}
        <li>
            <strong>Email:</strong>
            <a href="mailto:{settings.contactEmail}">{settings.contactEmail}</a>
        </li>
    {/if}
    {#if phone}
        <li>
            <strong>Phone:</strong> <a href="tel:{phone.tel}">{phone.display}</a>
        </li>
    {/if}
    {#if address}
        <li><strong>Address:</strong> {address}</li>
    {/if}
    <li>
        <strong>Online:</strong> the <Link href={contact.url()}>Contact page</Link> on this website
    </li>
</ul>
