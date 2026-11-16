<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Settings', href: '/admin/settings' }],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import SettingsController from '@/actions/App/Http/Controllers/Admin/SettingsController';
    import AppHead from '@/components/AppHead.svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import LimitedInput from '@/components/dashboard/LimitedInput.svelte';
    import LimitedTextarea from '@/components/dashboard/LimitedTextarea.svelte';
    import { fieldLimits } from '@/lib/field-limits';
    import { Label } from '@/components/ui/label';
    import type { OpeningHoursDay } from '@/lib/business';
    import BusinessFields from './BusinessFields.svelte';
    import { can } from '@/lib/permissions.svelte';

    let {
        general,
        authSettings,
        whatsapp,
        messenger,
        catalog,
        seo,
        tracking,
        canManageTracking,
    }: {
        general: {
            site_name?: string;
            contact_email?: string;
            contact_phone?: string;
            address?: string;
            currency?: string;
            timezone?: string;
            address_city?: string | null;
            address_region?: string | null;
            postal_code?: string | null;
            map_url?: string | null;
            latitude?: string | number | null;
            longitude?: string | number | null;
            opening_hours?: OpeningHoursDay[] | null;
            social_links?: Record<string, string | null> | null;
        };
        authSettings: { login_mode?: string; registration_enabled?: boolean };
        whatsapp: { enabled?: boolean; phone?: string; message?: string };
        messenger: { enabled?: boolean; page_url?: string };
        catalog: { page_size?: number; featured_limit?: number; delivery_note?: string };
        seo: {
            default_title?: string;
            default_description?: string;
            google_site_verification?: string | null;
            bing_site_verification?: string | null;
        };
        tracking: { head_scripts?: string; body_scripts?: string };
        canManageTracking: boolean;
    } = $props();

    const canManageSettings = $derived(can('settings.manage'));
    const canManageCatalogSeo = $derived(can('settings.manage', 'seo.manage'));

    const tabs = [
        { value: 'general', label: 'General' },
        { value: 'auth', label: 'Authentication' },
        { value: 'whatsapp', label: 'WhatsApp' },
        { value: 'messenger', label: 'Messenger' },
        { value: 'catalog', label: 'Catalog & SEO' },
        { value: 'tracking', label: 'Google Tag Manager' },
    ] as const;

    let tab = $state<(typeof tabs)[number]['value']>('general');
    const sitemapUrl = $derived(`${page.props.seo.appUrl}/sitemap.xml`);
</script>

<AppHead title="Settings" />

<div class="w-full p-4">
    <h1 class="mb-6 text-xl font-semibold">Settings</h1>

    <div class="mb-6 flex gap-2 overflow-x-auto border-b">
        {#each tabs as { value, label } (value)}
            <button
                class="shrink-0 border-b-2 px-3 py-2 text-sm {tab === value ? 'border-primary font-medium' : 'border-transparent text-muted-foreground'}"
                onclick={() => (tab = value)}
            >
                {label}
            </button>
        {/each}
    </div>

    {#if tab === 'general'}
        <Card>
            <CardHeader>
                <CardTitle>General</CardTitle>
                <CardDescription>Site identity and contact details shown across the catalog.</CardDescription>
            </CardHeader>
            <CardContent>
                <Form {...SettingsController.updateGeneral.form()} class="space-y-4">
                    {#snippet children({ errors, processing })}
                        <fieldset class="space-y-4" disabled={!canManageSettings} inert={!canManageSettings}>
                            <div class="grid gap-2">
                                <Label for="site_name">Site name</Label>
                                <LimitedInput
                                    id="site_name"
                                    name="site_name"
                                    required
                                    limit={fieldLimits.settings.site_name}
                                    value={general.site_name ?? ''}
                                />
                                <InputError message={errors.site_name} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="contact_email">Contact email</Label>
                                <LimitedInput
                                    id="contact_email"
                                    name="contact_email"
                                    type="email"
                                    limit={fieldLimits.settings.contact_email}
                                    value={general.contact_email ?? ''}
                                />
                                <InputError message={errors.contact_email} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="contact_phone">Contact phone</Label>
                                <LimitedInput
                                    id="contact_phone"
                                    name="contact_phone"
                                    type="tel"
                                    limit={fieldLimits.settings.contact_phone}
                                    value={general.contact_phone ?? ''}
                                />
                                <InputError message={errors.contact_phone} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="address">Street address</Label>
                                <LimitedTextarea
                                    id="address"
                                    name="address"
                                    rows={2}
                                    class="min-h-16"
                                    limit={fieldLimits.settings.address}
                                    value={general.address ?? ''}
                                />
                                <InputError message={errors.address} />
                            </div>
                            <BusinessFields {general} {errors} />
                            <div class="grid items-start gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="currency">Currency</Label>
                                    <Input
                                    id="currency"
                                    name="currency"
                                    required
                                    maxlength={3}
                                    minlength={3}
                                    class="uppercase"
                                    value={general.currency ?? 'BDT'}
                                />
                                <p class="text-xs text-muted-foreground">3-letter code, e.g. BDT.</p>
                                    <InputError message={errors.currency} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="timezone">Timezone</Label>
                                    <Input
                                    id="timezone"
                                    name="timezone"
                                    required
                                    maxlength={fieldLimits.settings.timezone}
                                    value={general.timezone ?? 'Asia/Dhaka'}
                                />
                                <p class="text-xs text-muted-foreground">e.g. Asia/Dhaka.</p>
                                    <InputError message={errors.timezone} />
                                </div>
                            </div>
                        </fieldset>
                        {#if canManageSettings}
                            <Button type="submit" disabled={processing}>Save general settings</Button>
                        {:else}
                            <p class="text-sm text-muted-foreground">You can view these settings but not change them.</p>
                        {/if}
                    {/snippet}
                </Form>
            </CardContent>
        </Card>
    {:else if tab === 'auth'}
        <Card>
            <CardHeader>
                <CardTitle>Customer authentication</CardTitle>
                <CardDescription>Control how customers sign in and whether new registrations are accepted.</CardDescription>
            </CardHeader>
            <CardContent>
                <Form {...SettingsController.updateAuth.form()} class="space-y-4">
                    {#snippet children({ errors, processing })}
                        <fieldset class="space-y-4" disabled={!canManageSettings} inert={!canManageSettings}>
                            <div class="grid gap-2">
                                <Label for="login_mode">Login mode</Label>
                                <FormSelect
                                    id="login_mode"
                                    name="login_mode"
                                    value={authSettings.login_mode ?? 'both'}
                                    options={[
                                        { value: 'email', label: 'Email only' },
                                        { value: 'phone', label: 'Phone only' },
                                        { value: 'both', label: 'Email or phone' },
                                    ]}
                                />
                                <InputError message={errors.login_mode} />
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="hidden" name="registration_enabled" value="0" />
                                <input type="checkbox" name="registration_enabled" value="1" checked={authSettings.registration_enabled ?? true} />
                                Allow new customer registration
                            </label>
                        </fieldset>
                        {#if canManageSettings}
                            <Button type="submit" disabled={processing}>Save authentication settings</Button>
                        {:else}
                            <p class="text-sm text-muted-foreground">You can view these settings but not change them.</p>
                        {/if}
                    {/snippet}
                </Form>
            </CardContent>
        </Card>
    {:else if tab === 'whatsapp'}
        <Card>
            <CardHeader>
                <CardTitle>WhatsApp widget</CardTitle>
                <CardDescription>Configure the floating WhatsApp contact button shown to visitors.</CardDescription>
            </CardHeader>
            <CardContent>
                <Form {...SettingsController.updateWhatsapp.form()} class="space-y-4">
                    {#snippet children({ errors, processing })}
                        <fieldset class="space-y-4" disabled={!canManageSettings} inert={!canManageSettings}>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="hidden" name="enabled" value="0" />
                                <input type="checkbox" name="enabled" value="1" checked={whatsapp.enabled ?? false} />
                                Enabled
                            </label>
                            <div class="grid gap-2">
                                <Label for="whatsapp_phone">Phone number</Label>
                                <LimitedInput
                                    id="whatsapp_phone"
                                    name="phone"
                                    type="tel"
                                    placeholder="01XXXXXXXXX"
                                    limit={fieldLimits.settings.whatsapp_phone}
                                    value={whatsapp.phone ?? ''}
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="whatsapp_message">Default message</Label>
                                <LimitedTextarea
                                    id="whatsapp_message"
                                    name="message"
                                    rows={2}
                                    class="min-h-16"
                                    limit={fieldLimits.settings.whatsapp_message}
                                    value={whatsapp.message ?? ''}
                                />
                                <InputError message={errors.message} />
                            </div>
                        </fieldset>
                        {#if canManageSettings}
                            <Button type="submit" disabled={processing}>Save WhatsApp settings</Button>
                        {:else}
                            <p class="text-sm text-muted-foreground">You can view these settings but not change them.</p>
                        {/if}
                    {/snippet}
                </Form>
            </CardContent>
        </Card>
    {:else if tab === 'messenger'}
        <Card>
            <CardHeader>
                <CardTitle>Messenger widget</CardTitle>
                <CardDescription>Configure the floating Messenger contact button shown to visitors.</CardDescription>
            </CardHeader>
            <CardContent>
                <Form {...SettingsController.updateMessenger.form()} class="space-y-4">
                    {#snippet children({ errors, processing })}
                        <fieldset class="space-y-4" disabled={!canManageSettings} inert={!canManageSettings}>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="hidden" name="enabled" value="0" />
                                <input type="checkbox" name="enabled" value="1" checked={messenger.enabled ?? false} />
                                Enabled
                            </label>
                            <div class="grid gap-2">
                                <Label for="page_url">Messenger page URL</Label>
                                <LimitedInput
                                    id="page_url"
                                    name="page_url"
                                    type="url"
                                    limit={fieldLimits.settings.messenger_url}
                                    value={messenger.page_url ?? ''}
                                />
                                <InputError message={errors.page_url} />
                            </div>
                        </fieldset>
                        {#if canManageSettings}
                            <Button type="submit" disabled={processing}>Save Messenger settings</Button>
                        {:else}
                            <p class="text-sm text-muted-foreground">You can view these settings but not change them.</p>
                        {/if}
                    {/snippet}
                </Form>
            </CardContent>
        </Card>
    {:else if tab === 'catalog'}
        <Card>
            <CardHeader>
                <CardTitle>Catalog & SEO</CardTitle>
                <CardDescription>Default listing size, featured product limit, delivery note, and fallback SEO metadata.</CardDescription>
            </CardHeader>
            <CardContent>
                <Form {...SettingsController.updateCatalogSeo.form()} class="space-y-4">
                    {#snippet children({ errors, processing })}
                        <fieldset class="space-y-4" disabled={!canManageCatalogSeo} inert={!canManageCatalogSeo}>
                            <div class="grid items-start gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="page_size">Products per page</Label>
                                    <Input
                                        id="page_size"
                                        name="page_size"
                                        type="number"
                                        required
                                        min="1"
                                        max="100"
                                        value={catalog.page_size ?? 24}
                                    />
                                    <InputError message={errors.page_size} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="featured_limit">Featured products limit</Label>
                                    <Input
                                        id="featured_limit"
                                        name="featured_limit"
                                        type="number"
                                        required
                                        min="1"
                                        max="50"
                                        value={catalog.featured_limit ?? 8}
                                    />
                                    <InputError message={errors.featured_limit} />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="delivery_note">Product delivery note</Label>
                                <LimitedInput
                                    id="delivery_note"
                                    name="delivery_note"
                                    required
                                    limit={fieldLimits.settings.delivery_note}
                                    value={catalog.delivery_note ?? 'Through courier all over Bangladesh'}
                                />
                                <InputError message={errors.delivery_note} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="default_title">Default SEO title</Label>
                                <LimitedInput
                                    id="default_title"
                                    name="default_title"
                                    limit={fieldLimits.settings.default_title}
                                    value={seo.default_title ?? ''}
                                />
                                <InputError message={errors.default_title} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="default_description">Default SEO description</Label>
                                <LimitedTextarea
                                    id="default_description"
                                    name="default_description"
                                    rows={2}
                                    class="min-h-16"
                                    limit={fieldLimits.settings.default_description}
                                    value={seo.default_description ?? ''}
                                />
                                <InputError message={errors.default_description} />
                            </div>
                            <fieldset class="grid gap-4 rounded-md border p-4">
                                <legend class="px-1 text-sm font-medium">Search engine verification</legend>
                                <p class="text-xs text-muted-foreground">
                                    In Google Search Console (or Bing Webmaster Tools), add your site and choose the “HTML tag”
                                    method. Paste the tag or just its code here, save, then click Verify there.
                                </p>
                                <div class="grid gap-2">
                                    <Label for="google_site_verification">Google Search Console code</Label>
                                    <Input
                                        id="google_site_verification"
                                        name="google_site_verification"
                                        placeholder='<meta name="google-site-verification" content="..." />'
                                        value={seo.google_site_verification ?? ''}
                                    />
                                    <InputError message={errors.google_site_verification} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="bing_site_verification">Bing Webmaster code</Label>
                                    <Input
                                        id="bing_site_verification"
                                        name="bing_site_verification"
                                        placeholder='<meta name="msvalidate.01" content="..." />'
                                        value={seo.bing_site_verification ?? ''}
                                    />
                                    <InputError message={errors.bing_site_verification} />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Then submit your sitemap in Search Console: <span class="font-mono">{sitemapUrl}</span>
                                </p>
                            </fieldset>
                        </fieldset>
                        {#if canManageCatalogSeo}
                            <Button type="submit" disabled={processing}>Save catalog &amp; SEO settings</Button>
                        {:else}
                            <p class="text-sm text-muted-foreground">You can view these settings but not change them.</p>
                        {/if}
                    {/snippet}
                </Form>
            </CardContent>
        </Card>
    {:else if tab === 'tracking'}
        <Card>
            <CardHeader>
                <CardTitle>Google Tag Manager</CardTitle>
                <CardDescription>
                    Paste raw JavaScript to run on every page. The head snippet is injected just before
                    <code>&lt;/head&gt;</code>; the body snippet right after the opening <code>&lt;body&gt;</code> tag (where
                    GTM's <code>&lt;noscript&gt;</code> fallback belongs). It runs on storefront pages only, never in the
                    admin panel. Only the Mother Admin can edit it, since it runs unsanitized on every visitor's page.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {#if !canManageTracking}
                    <p class="text-sm text-muted-foreground">Only the Mother Admin can change tracking scripts.</p>
                {:else}
                <Form {...SettingsController.updateTracking.form()} class="space-y-4">
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <Label for="head_scripts">Head code</Label>
                            <LimitedTextarea
                                id="head_scripts"
                                name="head_scripts"
                                limit={fieldLimits.settings.scripts}
                                rows={8}
                                class="font-mono text-xs"
                                placeholder={'<!-- Google Tag Manager -->\n<script>\n  ...\n<' + '/script>'}
                                value={tracking.head_scripts ?? ''}
                            />
                            <InputError message={errors.head_scripts} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="body_scripts">Body code</Label>
                            <LimitedTextarea
                                id="body_scripts"
                                name="body_scripts"
                                limit={fieldLimits.settings.scripts}
                                rows={8}
                                class="font-mono text-xs"
                                placeholder={'<!-- Google Tag Manager (noscript) -->\n<noscript>...</noscript>'}
                                value={tracking.body_scripts ?? ''}
                            />
                            <InputError message={errors.body_scripts} />
                        </div>
                        <Button type="submit" disabled={processing}>Save tracking scripts</Button>
                    {/snippet}
                </Form>
                {/if}
            </CardContent>
        </Card>
    {/if}
</div>
