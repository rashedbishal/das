<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import LegalContact from '@/components/storefront/LegalContact.svelte';
    import LegalDocument from '@/components/storefront/LegalDocument.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { privacy, terms } from '@/routes';

    /** Change this date whenever the terms change. */
    const UPDATED = '17 November 2026';

    const siteName = $derived(page.props.siteSettings.name);

    const toc = [
        { id: 'acceptance', label: 'About these terms' },
        { id: 'our-service', label: 'What this website is' },
        { id: 'eligibility', label: 'Who may use it' },
        { id: 'accounts', label: 'Your account' },
        { id: 'product-information', label: 'Product information and prices' },
        { id: 'quotations', label: 'Quotations, orders and delivery' },
        { id: 'professional-use', label: 'Professional and regulatory use' },
        { id: 'warranties', label: 'Warranties' },
        { id: 'acceptable-use', label: 'Acceptable use' },
        { id: 'intellectual-property', label: 'Content and trademarks' },
        { id: 'third-parties', label: 'Third-party services' },
        { id: 'liability', label: 'Limitation of liability' },
        { id: 'changes', label: 'Changes and suspension' },
        { id: 'governing-law', label: 'Governing law and disputes' },
        { id: 'contact', label: 'Contact us' },
    ];
</script>

<PageMeta
    title="Terms & Conditions"
    description="The terms for using the {siteName} dental equipment catalogue, accounts, quotations and enquiries."
    canonical={terms.url()}
/>

<LegalDocument title="Terms & Conditions" eyebrow="The ground rules, in plain language" updated={UPDATED} {toc}>
    {#snippet intro()}
        <p>
            These terms govern your use of the {siteName} website. By browsing it,
            creating an account (including with Google) or sending us an enquiry, you
            agree to them. If you do not agree, please do not use the website. How we
            handle your personal information is explained in our
            <Link href={privacy.url()}>Privacy Policy</Link>.
        </p>
    {/snippet}

    <section id="acceptance">
        <h2>1. About these terms</h2>
        <p>
            In these terms, “{siteName}”, “we”, “us” and “our” mean the business
            operating this website in Bangladesh, and “you” means anyone using it. If you
            use the website on behalf of a clinic, laboratory or company, you confirm that
            you may accept these terms for it.
        </p>
    </section>

    <section id="our-service">
        <h2>2. What this website is</h2>
        <p>
            This website is an online <strong>catalogue</strong> of dental machines,
            equipment, instruments, consumables and accessories. It helps you explore
            products, compare specifications, see prices once signed in, and contact our
            team.
        </p>
        <p>
            <strong>You cannot buy or pay for products on this website.</strong> There
            is no online cart, checkout or payment. Purchases are agreed directly with
            our team, as described in <a href="#quotations">section 6</a>.
        </p>
    </section>

    <section id="eligibility">
        <h2>3. Who may use it</h2>
        <p>
            Anyone may browse the catalogue. To create an account you must be at least
            18 years old and able to enter into a binding agreement under the laws of
            Bangladesh. Our products are intended mainly for dental professionals,
            clinics, laboratories, institutions and dealers.
        </p>
    </section>

    <section id="accounts">
        <h2>4. Your account</h2>
        <ul>
            <li>
                You can register with your email address or sign in with Google. When you
                register with email, you must confirm the address with the 6-digit code
                we send you before you can see full product details and prices.
            </li>
            <li>Give accurate information and keep it up to date.</li>
            <li>
                Keep your password secret and do not share your account. You are
                responsible for activity on your account; tell us immediately if you
                think someone else has used it.
            </li>
            <li>One person should hold one account.</li>
            <li>
                We may suspend, deactivate or delete an account that breaks these terms,
                gives false information, or puts the website or other users at risk. You
                may ask us to delete your account at any time (see our
                <Link href={privacy.url()}>Privacy Policy</Link>).
            </li>
        </ul>
    </section>

    <section id="product-information">
        <h2>5. Product information and prices</h2>
        <ul>
            <li>
                We work hard to keep product descriptions, specifications, images and
                availability accurate, but much of this comes from manufacturers and can
                change. Images are illustrative; colours, packaging and accessories may
                differ from the product supplied.
            </li>
            <li>
                Prices are shown in Bangladeshi Taka (BDT) to signed-in customers with a
                verified email. They are <strong>indicative</strong> and may change
                without notice, for example because of exchange rates, import costs or
                manufacturer pricing.
            </li>
            <li>
                A price or product shown on the website is not an offer to sell. The
                price and terms that apply are those confirmed in your written quotation
                or invoice.
            </li>
            <li>
                If we notice an error in a price or description, we may correct it,
                including after you have enquired.
            </li>
            <li>Stock information, where shown, is approximate.</li>
        </ul>
    </section>

    <section id="quotations">
        <h2>6. Quotations, orders and delivery</h2>
        <ul>
            <li>
                You can ask for a quotation through an enquiry form, the Contact page,
                phone, WhatsApp or Messenger. Sending an enquiry does not create an order
                or any obligation for you or for us.
            </li>
            <li>
                A quotation is valid for the period stated on it. An order is agreed only
                when we confirm it with you directly (for example in writing or on an
                invoice).
            </li>
            <li>
                Payment, delivery, installation, training, returns and after-sales service
                are agreed with you for each order, and those agreed terms, together with
                any rights you have under the consumer protection laws of Bangladesh, apply
                to that purchase.
            </li>
            <li>
                Where delivery is agreed, we usually deliver by courier across Bangladesh.
                Delivery times are estimates.
            </li>
        </ul>
    </section>

    <section id="professional-use">
        <h2>7. Professional and regulatory use</h2>
        <ul>
            <li>
                Many of our products are medical or dental devices meant to be used only
                by appropriately trained and qualified professionals, following the
                manufacturer’s instructions, safety warnings and maintenance requirements.
            </li>
            <li>
                Information on this website is general product information. It is not
                clinical, medical or professional advice; please rely on your own
                professional judgement and the manufacturer’s documentation.
            </li>
            <li>
                You are responsible for any licences, registrations, approvals or
                facility requirements that apply to you or your practice in Bangladesh
                for buying, installing or using a product.
            </li>
        </ul>
    </section>

    <section id="warranties">
        <h2>8. Warranties</h2>
        <p>
            Product warranties are those given by the manufacturer or stated in your
            quotation or invoice. The website itself is provided “as is”: we aim to keep
            it accurate, secure and available, but we cannot promise it will always be
            error-free or uninterrupted.
        </p>
    </section>

    <section id="acceptable-use">
        <h2>9. Acceptable use</h2>
        <p>When using the website, you must not:</p>
        <ul>
            <li>break the law or these terms, or help anyone else do so;</li>
            <li>send false, misleading, abusive or spam enquiries;</li>
            <li>
                try to access accounts, data or areas you are not allowed to, or interfere
                with the website’s security or operation;
            </li>
            <li>
                copy, scrape or collect the catalogue, prices or users’ information by
                automated means, or republish our prices, without our written permission;
            </li>
            <li>upload or send viruses or other harmful code.</li>
        </ul>
    </section>

    <section id="intellectual-property">
        <h2>10. Content and trademarks</h2>
        <p>
            The website’s design, text, layout and our own photographs belong to
            {siteName} or are used with permission. You may view and print pages for your
            own business use, but not copy or reuse them otherwise without permission.
            Brand names, logos and product names belong to their respective owners and
            are used only to identify their products; this does not mean those owners
            endorse {siteName} unless we say so.
        </p>
    </section>

    <section id="third-parties">
        <h2>11. Third-party services</h2>
        <p>
            Some features rely on other companies, such as Google (for “Continue with
            Google”), WhatsApp and Facebook Messenger, and links to manufacturers or maps.
            Their own terms and privacy policies apply when you use them, and we are not
            responsible for their services.
        </p>
    </section>

    <section id="liability">
        <h2>12. Limitation of liability</h2>
        <p>
            To the extent permitted by the laws of Bangladesh, {siteName} is not liable
            for any indirect or consequential loss, or for loss of profit, business,
            data or goodwill, arising from your use of the website or reliance on its
            information. Our liability for any purchase is set by the terms agreed for
            that purchase. Nothing in these terms limits any liability or consumer right
            that cannot be limited by law.
        </p>
    </section>

    <section id="changes">
        <h2>13. Changes and suspension</h2>
        <p>
            We may update these terms, change or withdraw products and features, or
            suspend the website for maintenance. The “Last updated” date at the top shows
            when these terms last changed; continuing to use the website after a change
            means you accept the updated terms.
        </p>
    </section>

    <section id="governing-law">
        <h2>14. Governing law and disputes</h2>
        <p>
            These terms are governed by the laws of the People’s Republic of Bangladesh.
            If a problem arises, please contact us first. We will try to resolve it
            fairly and quickly. Any dispute that cannot be resolved this way will be
            decided by the competent courts of Bangladesh.
        </p>
    </section>

    <section id="contact">
        <h2>15. Contact us</h2>
        <p>Questions about these terms? Contact {siteName}:</p>
        <LegalContact />
    </section>
</LegalDocument>
