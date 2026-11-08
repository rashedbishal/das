<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Settings', href: '/admin/settings' }],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import SettingsController from '@/actions/App/Http/Controllers/Admin/SettingsController';
    import AppHead from '@/components/AppHead.svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';

    let {
        general,
        authSettings,
        whatsapp,
        messenger,
        catalog,
        seo,
        tracking,
    }: {
        general: { site_name?: string; contact_email?: string; contact_phone?: string; address?: string; currency?: string; timezone?: string };
        authSettings: { login_mode?: string; registration_enabled?: boolean; require_email_verification?: boolean };
        whatsapp: { enabled?: boolean; phone?: string; message?: string };
        messenger: { enabled?: boolean; page_url?: string };
        catalog: { page_size?: number; featured_limit?: number; delivery_note?: string };
        seo: { default_title?: string; default_description?: string };
        tracking: { head_scripts?: string; body_scripts?: string };
    } = $props();

    const tabs = [
        { value: 'general', label: 'General' },
        { value: 'auth', label: 'Authentication' },
        { value: 'whatsapp', label: 'WhatsApp' },
        { value: 'messenger', label: 'Messenger' },
        { value: 'catalog', label: 'Catalog & SEO' },
        { value: 'tracking', label: 'Google Tag Manager' },
    ] as const;

    let tab = $state<(typeof tabs)[number]['value']>('general');
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
                        <div class="grid gap-2">
                            <Label for="site_name">Site name</Label>
                            <Input id="site_name" name="site_name" required value={general.site_name ?? ''} />
                            <InputError message={errors.site_name} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="contact_email">Contact email</Label>
                            <Input id="contact_email" name="contact_email" type="email" value={general.contact_email ?? ''} />
                            <InputError message={errors.contact_email} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="contact_phone">Contact phone</Label>
                            <Input id="contact_phone" name="contact_phone" value={general.contact_phone ?? ''} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="address">Address</Label>
                            <Input id="address" name="address" value={general.address ?? ''} />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="currency">Currency</Label>
                                <Input id="currency" name="currency" required value={general.currency ?? 'BDT'} />
                                <InputError message={errors.currency} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="timezone">Timezone</Label>
                                <Input id="timezone" name="timezone" required value={general.timezone ?? 'Asia/Dhaka'} />
                                <InputError message={errors.timezone} />
                            </div>
                        </div>
                        <Button type="submit" disabled={processing}>Save general settings</Button>
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
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="require_email_verification" value="0" />
                            <input
                                type="checkbox"
                                name="require_email_verification"
                                value="1"
                                checked={authSettings.require_email_verification ?? true}
                            />
                            Require customers to verify their email before signing in
                        </label>
                        <Button type="submit" disabled={processing}>Save authentication settings</Button>
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
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="enabled" value="0" />
                            <input type="checkbox" name="enabled" value="1" checked={whatsapp.enabled ?? false} />
                            Enabled
                        </label>
                        <div class="grid gap-2">
                            <Label for="whatsapp_phone">Phone number</Label>
                            <Input id="whatsapp_phone" name="phone" placeholder="01XXXXXXXXX" value={whatsapp.phone ?? ''} />
                            <InputError message={errors.phone} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="whatsapp_message">Default message</Label>
                            <Input id="whatsapp_message" name="message" value={whatsapp.message ?? ''} />
                        </div>
                        <Button type="submit" disabled={processing}>Save WhatsApp settings</Button>
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
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="enabled" value="0" />
                            <input type="checkbox" name="enabled" value="1" checked={messenger.enabled ?? false} />
                            Enabled
                        </label>
                        <div class="grid gap-2">
                            <Label for="page_url">Messenger page URL</Label>
                            <Input id="page_url" name="page_url" type="url" value={messenger.page_url ?? ''} />
                            <InputError message={errors.page_url} />
                        </div>
                        <Button type="submit" disabled={processing}>Save Messenger settings</Button>
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
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="page_size">Products per page</Label>
                                <Input id="page_size" name="page_size" type="number" required value={catalog.page_size ?? 24} />
                                <InputError message={errors.page_size} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="featured_limit">Featured products limit</Label>
                                <Input id="featured_limit" name="featured_limit" type="number" required value={catalog.featured_limit ?? 8} />
                                <InputError message={errors.featured_limit} />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="delivery_note">Product delivery note</Label>
                            <Input
                                id="delivery_note"
                                name="delivery_note"
                                required
                                value={catalog.delivery_note ?? 'Through courier all over Bangladesh'}
                            />
                            <InputError message={errors.delivery_note} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="default_title">Default SEO title</Label>
                            <Input id="default_title" name="default_title" value={seo.default_title ?? ''} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="default_description">Default SEO description</Label>
                            <Input id="default_description" name="default_description" value={seo.default_description ?? ''} />
                        </div>
                        <Button type="submit" disabled={processing}>Save catalog &amp; SEO settings</Button>
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
                    GTM's <code>&lt;noscript&gt;</code> fallback belongs). Only admins with Settings access can edit this,
                    since it runs unsanitized on every visitor's page.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form {...SettingsController.updateTracking.form()} class="space-y-4">
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <Label for="head_scripts">Head code</Label>
                            <Textarea
                                id="head_scripts"
                                name="head_scripts"
                                rows={8}
                                class="font-mono text-xs"
                                placeholder={'<!-- Google Tag Manager -->\n<script>\n  ...\n<' + '/script>'}
                                value={tracking.head_scripts ?? ''}
                            />
                            <InputError message={errors.head_scripts} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="body_scripts">Body code</Label>
                            <Textarea
                                id="body_scripts"
                                name="body_scripts"
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
            </CardContent>
        </Card>
    {/if}
</div>
