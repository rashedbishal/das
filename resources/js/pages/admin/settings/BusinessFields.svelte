<script lang="ts">
    import { untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import LimitedInput from '@/components/dashboard/LimitedInput.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { dayLabel, WEEKDAYS, type OpeningHoursDay } from '@/lib/business';
    import { fieldLimits } from '@/lib/field-limits';

    /**
     * Address details, map position, opening hours and social profiles. Shown on the
     * Contact page and footer, and given to Google as LocalBusiness structured data.
     * Rendered inside the General settings <Form>, so the inputs submit with it.
     */
    let {
        general,
        errors,
    }: {
        general: {
            address_city?: string | null;
            address_region?: string | null;
            postal_code?: string | null;
            map_url?: string | null;
            latitude?: string | number | null;
            longitude?: string | number | null;
            opening_hours?: OpeningHoursDay[] | null;
            social_links?: Record<string, string | null> | null;
        };
        errors: Record<string, string | undefined>;
    } = $props();

    const socialNetworks = [
        { key: 'facebook', label: 'Facebook page' },
        { key: 'instagram', label: 'Instagram' },
        { key: 'youtube', label: 'YouTube' },
        { key: 'linkedin', label: 'LinkedIn' },
        { key: 'x', label: 'X (Twitter)' },
    ];

    let hours = $state<OpeningHoursDay[]>(
        untrack(() =>
            WEEKDAYS.map((day) => {
                const saved = general.opening_hours?.find((entry) => entry.day === day);

                return saved
                    ? { ...saved, opens: saved.opens ?? '', closes: saved.closes ?? '' }
                    : { day, closed: day === 'friday', opens: day === 'friday' ? '' : '10:00', closes: day === 'friday' ? '' : '19:00' };
            }),
        ),
    );

    const hoursError = $derived(
        Object.entries(errors).find(([key]) => key.startsWith('opening_hours'))?.[1],
    );
</script>

<fieldset class="grid gap-4 rounded-md border p-4">
    <legend class="px-1 text-sm font-medium">Location (for Google Maps &amp; local search)</legend>
    <div class="grid items-start gap-4 sm:grid-cols-3">
        <div class="grid gap-2">
            <Label for="address_city">City / area</Label>
            <LimitedInput
                id="address_city"
                name="address_city"
                placeholder="e.g. Dhaka"
                limit={fieldLimits.settings.address_city}
                value={general.address_city ?? ''}
            />
            <InputError message={errors.address_city} />
        </div>
        <div class="grid gap-2">
            <Label for="address_region">Division</Label>
            <LimitedInput
                id="address_region"
                name="address_region"
                placeholder="e.g. Dhaka Division"
                limit={fieldLimits.settings.address_region}
                value={general.address_region ?? ''}
            />
            <InputError message={errors.address_region} />
        </div>
        <div class="grid gap-2">
            <Label for="postal_code">Postal code</Label>
            <LimitedInput
                id="postal_code"
                name="postal_code"
                placeholder="e.g. 1205"
                limit={fieldLimits.settings.postal_code}
                value={general.postal_code ?? ''}
            />
            <InputError message={errors.postal_code} />
        </div>
    </div>
    <div class="grid gap-2">
        <Label for="map_url">Google Maps link</Label>
        <Input
            id="map_url"
            name="map_url"
            type="url"
            placeholder="https://maps.app.goo.gl/..."
            maxlength={fieldLimits.settings.map_url}
            value={general.map_url ?? ''}
        />
        <p class="text-xs text-muted-foreground">
            Open your business in Google Maps, click Share and paste the link. Shown as “Visit us” on the Contact page.
        </p>
        <InputError message={errors.map_url} />
    </div>
    <div class="grid items-start gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="latitude">Latitude (optional)</Label>
            <Input id="latitude" name="latitude" inputmode="decimal" placeholder="23.7461" value={general.latitude ?? ''} />
            <InputError message={errors.latitude} />
        </div>
        <div class="grid gap-2">
            <Label for="longitude">Longitude (optional)</Label>
            <Input id="longitude" name="longitude" inputmode="decimal" placeholder="90.3742" value={general.longitude ?? ''} />
            <InputError message={errors.longitude} />
        </div>
    </div>
    <p class="-mt-2 text-xs text-muted-foreground">
        In Google Maps, right-click your shop and click the numbers at the top of the menu to copy them.
    </p>
</fieldset>

<fieldset class="grid gap-3 rounded-md border p-4">
    <legend class="px-1 text-sm font-medium">Business hours</legend>
    <p class="text-xs text-muted-foreground">
        Shown on the Contact page and to search engines. Until set, the Contact page says hours are available on request.
    </p>
    {#each hours as entry, index (entry.day)}
        <div class="grid grid-cols-[6.5rem_auto] items-center gap-3 sm:grid-cols-[6.5rem_6rem_1fr_1fr]">
            <span class="text-sm">{dayLabel(entry.day)}</span>
            <input type="hidden" name="opening_hours[{index}][day]" value={entry.day} />
            <input type="hidden" name="opening_hours[{index}][closed]" value={entry.closed ? '1' : '0'} />
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" class="accent-primary" bind:checked={entry.closed} />
                Closed
            </label>
            {#if !entry.closed}
                <Input
                    type="time"
                    name="opening_hours[{index}][opens]"
                    aria-label="{dayLabel(entry.day)} opens"
                    bind:value={entry.opens}
                    required
                />
                <Input
                    type="time"
                    name="opening_hours[{index}][closes]"
                    aria-label="{dayLabel(entry.day)} closes"
                    bind:value={entry.closes}
                    required
                />
            {/if}
        </div>
    {/each}
    <InputError message={hoursError} />
</fieldset>

<fieldset class="grid gap-3 rounded-md border p-4">
    <legend class="px-1 text-sm font-medium">Social profiles</legend>
    <p class="text-xs text-muted-foreground">Helps Google connect these profiles to your business.</p>
    {#each socialNetworks as network (network.key)}
        <div class="grid gap-1.5">
            <Label for="social_{network.key}">{network.label}</Label>
            <Input
                id="social_{network.key}"
                name="social_links[{network.key}]"
                type="url"
                placeholder="https://"
                maxlength={fieldLimits.settings.social_link}
                value={general.social_links?.[network.key] ?? ''}
            />
            <InputError message={errors[`social_links.${network.key}`]} />
        </div>
    {/each}
</fieldset>
