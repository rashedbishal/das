<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import LegalContact from '@/components/storefront/LegalContact.svelte';
    import LegalDocument from '@/components/storefront/LegalDocument.svelte';
    import PageMeta from '@/components/storefront/PageMeta.svelte';
    import { privacy, terms } from '@/routes';

    /** Change this date whenever the policy text changes. */
    const UPDATED = '17 November 2026';

    const siteName = $derived(page.props.siteSettings.name);

    const toc = [
        { id: 'who-we-are', label: 'Who we are' },
        { id: 'information-we-collect', label: 'Information we collect' },
        { id: 'google-sign-in', label: 'Signing in with Google' },
        { id: 'how-we-use', label: 'How we use your information' },
        { id: 'sharing', label: 'When we share information' },
        { id: 'cookies', label: 'Cookies and similar technologies' },
        { id: 'retention', label: 'How long we keep information' },
        { id: 'security', label: 'How we protect information' },
        { id: 'your-rights', label: 'Your rights and choices' },
        { id: 'children', label: 'Children' },
        { id: 'third-party-links', label: 'Links to other websites' },
        { id: 'changes', label: 'Changes to this policy' },
        { id: 'contact', label: 'Contact us' },
    ];
</script>

<PageMeta
    title="Privacy Policy"
    description="How {siteName} collects, uses and protects your personal information, including when you sign in with Google."
    canonical={privacy.url()}
/>

<LegalDocument title="Privacy Policy" eyebrow="Your information, handled with care" updated={UPDATED} {toc}>
    {#snippet intro()}
        <p>
            This policy explains what personal information {siteName} collects when you
            use this website, why we collect it, how we use and protect it, and the
            choices you have. It applies to everyone who browses the catalogue, creates
            an account (including with Google), or sends us an enquiry. Please also read
            our <Link href={terms.url()}>Terms &amp; Conditions</Link>.
        </p>
    {/snippet}

    <section id="who-we-are">
        <h2>1. Who we are</h2>
        <p>
            {siteName} is a supplier of dental machines, equipment, instruments and
            accessories based in Bangladesh. This website is our online product catalogue:
            it lets dental professionals and businesses explore products, see prices once
            signed in, and contact us. We do not sell products or take payments through
            this website; purchases are arranged directly with our team.
        </p>
        <p>
            {siteName} is responsible for the personal information described in this
            policy. Our contact details are in <a href="#contact">section 13</a>.
        </p>
    </section>

    <section id="information-we-collect">
        <h2>2. Information we collect</h2>

        <h3>Information you give us</h3>
        <ul>
            <li>
                <strong>Account details:</strong> your name, email address and password
                when you register. Your password is stored only in an irreversibly
                encrypted (hashed) form; we can never see it.
            </li>
            <li>
                <strong>Email verification:</strong> when you register, we email you a
                6-digit code to confirm that the address belongs to you.
            </li>
            <li>
                <strong>Enquiries and quote requests:</strong> your name, phone number,
                and optionally your clinic or business name, email address, the product
                you are interested in, and your message.
            </li>
            <li>
                <strong>Messages to us</strong> by email, phone, WhatsApp or Messenger,
                and the details you choose to share in them.
            </li>
        </ul>

        <h3>Information from Google</h3>
        <p>
            If you choose “Continue with Google”, we receive limited information from
            your Google account. See <a href="#google-sign-in">section 3</a> for exactly
            what we receive and how it is used.
        </p>

        <h3>Information collected automatically</h3>
        <ul>
            <li>
                <strong>Security and account activity:</strong> when you sign in, sign
                out, fail to sign in, reset your password, verify your email or your
                account changes, we record the event together with your IP address and
                browser details. This helps us detect misuse and protect your account.
            </li>
            <li>
                <strong>Visit counts:</strong> we count how many pages are viewed each day.
                These are totals only; they do not identify you.
            </li>
            <li>
                <strong>Cookies:</strong> small files needed to keep you signed in and to
                protect forms. See <a href="#cookies">section 6</a>.
            </li>
        </ul>
        <p>
            We do not collect payment card or bank details through this website, and we
            do not ask for sensitive personal information such as health records.
        </p>
    </section>

    <section id="google-sign-in">
        <h2>3. Signing in with Google</h2>
        <p>
            “Continue with Google” is optional; you can always register with your email
            address instead. If you use it, Google shares the following with us, only
            after you agree on Google’s own consent screen:
        </p>
        <ul>
            <li>your name;</li>
            <li>your email address, and whether Google has verified it;</li>
            <li>a unique Google account identifier.</li>
        </ul>
        <p>
            We use this information <strong>only</strong> to create your {siteName}
            account or sign you in to it, to link it to an existing account that uses
            the same email address, and to treat your email address as verified. We do
            not receive your Google password, and we do not access your contacts, Gmail,
            Google Drive, calendar or any other Google data. We do not store your Google
            profile photo.
        </p>
        <p>
            We do not sell information received from Google, use it for advertising,
            or share it with anyone except as needed to run this sign-in (for example,
            our hosting provider). {siteName}’s use and transfer of information received
            from Google APIs adheres to the
            <a
                href="https://developers.google.com/terms/api-services-user-data-policy"
                target="_blank"
                rel="noopener noreferrer">Google API Services User Data Policy</a
            >, including the Limited Use requirements.
        </p>
        <p>
            You can remove {siteName}’s access at any time from your Google Account at
            <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener noreferrer"
                >myaccount.google.com/permissions</a
            >. This stops future Google sign-ins; to delete the account and the
            information we already hold, contact us as described in
            <a href="#your-rights">section 9</a>.
        </p>
    </section>

    <section id="how-we-use">
        <h2>4. How we use your information</h2>
        <ul>
            <li>to create and manage your account and let you sign in;</li>
            <li>to verify your email address and keep your account secure;</li>
            <li>to show prices and full product details to signed-in, verified customers;</li>
            <li>to reply to your enquiries and quote requests and to arrange purchases you ask for;</li>
            <li>
                to send service emails you need, such as verification codes and
                password reset links (we do not send marketing emails without your
                permission);
            </li>
            <li>to prevent fraud, spam and abuse, and to protect our website and customers;</li>
            <li>to understand overall website use and improve our catalogue;</li>
            <li>to comply with the laws of Bangladesh and respond to lawful requests from authorities.</li>
        </ul>
        <p>
            We use your information only for these purposes, because you asked us to
            (for example by registering or sending an enquiry), because it is needed to
            provide the service, or because the law requires it.
        </p>
    </section>

    <section id="sharing">
        <h2>5. When we share information</h2>
        <p><strong>We do not sell or rent your personal information.</strong> We share it only with:</p>
        <ul>
            <li>
                <strong>Service providers</strong> who run parts of our service for us
                and may only use the information for that purpose: our website hosting
                provider, our email delivery provider, and our backup storage provider.
            </li>
            <li>
                <strong>Google</strong>, when you choose to sign in with Google (see
                <a href="#google-sign-in">section 3</a>).
            </li>
            <li>
                <strong>WhatsApp or Facebook Messenger</strong> (Meta), only if you choose
                to contact us through them; their own privacy policies apply.
            </li>
            <li>
                <strong>Authorities</strong>, when the law of Bangladesh requires it, or
                to protect our rights, our customers or the public.
            </li>
            <li>
                <strong>A new owner</strong>, if our business is sold or reorganised; the
                information would remain protected by this policy.
            </li>
        </ul>
        <p>
            Some service providers may store or process information on servers outside
            Bangladesh. When that happens, we only use reputable providers that protect it
            to a standard comparable to this policy.
        </p>
    </section>

    <section id="cookies">
        <h2>6. Cookies and similar technologies</h2>
        <p>This website uses a small number of cookies and browser storage items:</p>
        <ul>
            <li>
                <strong>Session cookie (essential):</strong> keeps you signed in as you
                move between pages. It expires after about 2 hours of inactivity.
            </li>
            <li>
                <strong>Security cookie (essential):</strong> protects forms against
                cross-site request forgery.
            </li>
            <li>
                <strong>Preferences:</strong> remembers display choices, such as the
                light or dark appearance.
            </li>
        </ul>
        <p>
            Essential cookies are needed for the website to work, so they cannot be
            switched off. We may also use analytics or advertising tools (for example,
            Google Analytics or the Meta Pixel) to understand how the website is used;
            these set their own cookies and are governed by their providers’ policies.
            You can block or delete cookies in your browser settings, but signing in
            will not work without the essential ones.
        </p>
    </section>

    <section id="retention">
        <h2>7. How long we keep information</h2>
        <ul>
            <li><strong>Your account:</strong> until you ask us to delete it, or we close it.</li>
            <li>
                <strong>Enquiries:</strong> for as long as needed to respond and follow up,
                and to keep reasonable business records; then deleted.
            </li>
            <li><strong>Security and account activity records:</strong> 12 months.</li>
            <li><strong>Email verification codes:</strong> 15 minutes, or until used.</li>
            <li><strong>Password reset links:</strong> 60 minutes, or until used.</li>
            <li>
                <strong>Backups:</strong> we keep backups of the website so we can
                recover from failures. Older backups are deleted on a rolling
                schedule; the oldest are kept for up to 2 years. Information deleted from
                the website disappears from backups as they expire.
            </li>
        </ul>
    </section>

    <section id="security">
        <h2>8. How we protect information</h2>
        <p>
            We use reasonable technical and organisational measures to protect your
            information, including encrypted (HTTPS) connections, hashed passwords and
            verification codes, limits on repeated sign-in and code attempts, and
            restricted, permission-based access for our staff. No website can be
            completely secure, so please use a strong password that you do not use
            elsewhere, and tell us straight away if you think your account has been misused.
        </p>
    </section>

    <section id="your-rights">
        <h2>9. Your rights and choices</h2>
        <p>You can ask us to:</p>
        <ul>
            <li>tell you what personal information we hold about you and give you a copy;</li>
            <li>correct information that is inaccurate or incomplete;</li>
            <li>
                <strong>delete your account</strong> and the personal information linked
                to it (we may keep what the law requires us to keep);
            </li>
            <li>stop using your information for a particular purpose, where possible.</li>
        </ul>
        <p>
            To make a request, contact us using the details in
            <a href="#contact">section 13</a> from the email address on your account (so
            we can confirm it is you). We will respond within 30 days. You can also stop
            Google sign-in at any time, as described in <a href="#google-sign-in">section 3</a>.
        </p>
    </section>

    <section id="children">
        <h2>10. Children</h2>
        <p>
            This website is intended for adults, especially dental professionals and
            businesses. It is not directed at anyone under 18, and we do not knowingly
            collect personal information from children. If you believe a child has
            given us information, contact us and we will delete it.
        </p>
    </section>

    <section id="third-party-links">
        <h2>11. Links to other websites</h2>
        <p>
            Our website may link to manufacturers, brands, WhatsApp, Messenger, maps and
            other websites. We are not responsible for their content or privacy practices;
            please read their policies before giving them information.
        </p>
    </section>

    <section id="changes">
        <h2>12. Changes to this policy</h2>
        <p>
            We may update this policy as our website or the law changes. The “Last
            updated” date at the top shows when it last changed. If we make significant
            changes, we will make them clear on this website.
        </p>
    </section>

    <section id="contact">
        <h2>13. Contact us</h2>
        <p>For any question or request about your privacy, contact {siteName}:</p>
        <LegalContact />
    </section>
</LegalDocument>
