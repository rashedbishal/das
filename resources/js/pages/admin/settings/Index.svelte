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
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    let {
        general,
        authSettings,
        whatsapp,
        messenger,
        catalog,
        seo,
    }: {
        general: { site_name?: string; contact_email?: string; contact_phone?: string; address?: string; currency?: string; timezone?: string };
        authSettings: { login_mode?: string; registration_enabled?: boolean };
        whatsapp: { enabled?: boolean; phone?: string; message?: string };
        messenger: { enabled?: boolean; page_url?: string };
        catalog: { page_size?: number; featured_limit?: number };
        seo: { default_title?: string; default_description?: string };
    } = $props();
</script>

<AppHead title="Settings" />

<div class="mx-auto w-full max-w-2xl space-y-10 p-4">
    <h1 class="text-xl font-semibold">Settings</h1>

    <section>
        <h2 class="mb-4 text-lg font-semibold">General</h2>
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
    </section>

    <section>
        <h2 class="mb-4 text-lg font-semibold">Customer authentication</h2>
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
                    <input type="checkbox" name="registration_enabled" value="1" checked={authSettings.registration_enabled ?? true} />
                    Allow new customer registration
                </label>
                <Button type="submit" disabled={processing}>Save authentication settings</Button>
            {/snippet}
        </Form>
    </section>

    <section>
        <h2 class="mb-4 text-lg font-semibold">WhatsApp widget</h2>
        <Form {...SettingsController.updateWhatsapp.form()} class="space-y-4">
            {#snippet children({ errors, processing })}
                <label class="flex items-center gap-2 text-sm">
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
    </section>

    <section>
        <h2 class="mb-4 text-lg font-semibold">Messenger widget</h2>
        <Form {...SettingsController.updateMessenger.form()} class="space-y-4">
            {#snippet children({ errors, processing })}
                <label class="flex items-center gap-2 text-sm">
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
    </section>

    <section>
        <h2 class="mb-4 text-lg font-semibold">Catalog &amp; SEO</h2>
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
    </section>
</div>
