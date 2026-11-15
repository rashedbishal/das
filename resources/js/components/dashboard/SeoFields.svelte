<script module lang="ts">
    export type SeoCheck = { label: string; ok: boolean; hint?: string };

    /** Rough slug of a name, for previewing the URL of a record that has no slug yet. */
    export function previewSlug(value: string): string {
        return (
            value
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[^\w\s-]/g, '')
                .trim()
                .replace(/[\s_-]+/g, '-') || 'new-page'
        );
    }
</script>

<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import CircleAlert from '@lucide/svelte/icons/circle-alert';
    import CircleCheck from '@lucide/svelte/icons/circle-check';
    import { onMount, untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import LimitedInput from '@/components/dashboard/LimitedInput.svelte';
    import LimitedTextarea from '@/components/dashboard/LimitedTextarea.svelte';
    import { Label } from '@/components/ui/label';

    /**
     * SEO title/description inputs plus a live Google result preview and a checklist.
     * It reads the surrounding <form>'s other fields (name, description, …) as the
     * fallbacks the storefront uses when the SEO fields are empty.
     */
    let {
        idPrefix = 'seo',
        titleLimit = 70,
        descriptionLimit = 170,
        values = {},
        errors = {},
        pathFor,
        fallbackDescriptionFields = ['description'],
        content = null,
        checks = [],
        withInputs = true,
    }: {
        idPrefix?: string;
        titleLimit?: number;
        descriptionLimit?: number;
        values?: { seo_title?: string | null; seo_description?: string | null };
        errors?: Record<string, string | undefined>;
        /** The storefront path for the current form values, e.g. "/brands/acme". */
        pathFor: (form: Record<string, string>) => string;
        /** Form fields used (in order) as the description when the SEO description is empty. */
        fallbackDescriptionFields?: string[];
        /** The main page text, checked for a useful minimum length. */
        content?: { field: string; min: number; label: string } | null;
        checks?: SeoCheck[];
        /** False when the form already has its own seo_title/seo_description inputs. */
        withInputs?: boolean;
    } = $props();

    let root = $state<HTMLElement | null>(null);
    let form = $state<Record<string, string>>({});

    function readForm(target: HTMLFormElement) {
        const next: Record<string, string> = {};
        new FormData(target).forEach((value, key) => {
            if (typeof value === 'string') next[key] = value;
        });
        form = next;
    }

    onMount(() => {
        const target = root?.closest('form');
        if (!target) return;

        readForm(target);
        const update = () => readForm(target);
        target.addEventListener('input', update);
        target.addEventListener('change', update);

        return () => {
            target.removeEventListener('input', update);
            target.removeEventListener('change', update);
        };
    });

    const siteName = $derived(page.props.siteSettings.name);
    const appUrl = $derived(page.props.seo.appUrl);
    const name = $derived((form.name ?? '').trim());
    const seoTitle = $derived((form.seo_title ?? untrack(() => values.seo_title) ?? '').trim());
    const seoDescription = $derived((form.seo_description ?? untrack(() => values.seo_description) ?? '').trim());
    const title = $derived(`${seoTitle || name || 'Page title'} | ${siteName}`);
    const description = $derived(
        seoDescription ||
            fallbackDescriptionFields.map((field) => (form[field] ?? '').trim()).find((value) => value !== '') ||
            '',
    );
    const url = $derived.by(() => {
        const host = appUrl.replace(/^https?:\/\//, '');
        const segments = pathFor(form).split('/').filter(Boolean);
        return [host, ...segments].join(' › ');
    });

    const contentLength = $derived(content ? (form[content.field] ?? '').trim().length : 0);
    const checklist = $derived<SeoCheck[]>([
        {
            label: `Title length ${title.length} characters`,
            ok: title.length >= 30 && title.length <= 65,
            hint: title.length < 30 ? 'Aim for 30–60: add what it is and who it is for.' : 'Over ~60 characters gets cut off in Google.',
        },
        {
            label: description
                ? `Description length ${description.length} characters${seoDescription ? '' : ' (from the main description)'}`
                : 'No description yet',
            ok: description.length >= 70 && description.length <= 160,
            hint: description.length < 70 ? 'Aim for 120–160 characters that make people want to click.' : 'Over ~160 characters gets cut off.',
        },
        ...(content
            ? [{ label: content.label, ok: contentLength >= content.min, hint: `${contentLength} of ${content.min} characters.` }]
            : []),
        ...checks,
    ]);
    const passed = $derived(checklist.filter((check) => check.ok).length);

    function truncate(value: string, max: number): string {
        return value.length > max ? `${value.slice(0, max - 1).trimEnd()}…` : value;
    }
</script>

<fieldset bind:this={root} class="grid gap-4 rounded-md border p-4">
    <legend class="px-1 text-sm font-medium">Search engine (SEO)</legend>

    {#if withInputs}
        <div class="grid gap-2">
            <Label for="{idPrefix}_seo_title">SEO title <span class="font-normal text-muted-foreground">(optional)</span></Label>
            <LimitedInput
                id="{idPrefix}_seo_title"
                name="seo_title"
                limit={titleLimit}
                value={values.seo_title ?? ''}
                placeholder={name || 'Defaults to the name'}
            />
            <InputError message={errors.seo_title} />
        </div>
        <div class="grid gap-2">
            <Label for="{idPrefix}_seo_description">
                SEO description <span class="font-normal text-muted-foreground">(optional)</span>
            </Label>
            <LimitedTextarea
                id="{idPrefix}_seo_description"
                name="seo_description"
                rows={2}
                class="min-h-16"
                limit={descriptionLimit}
                value={values.seo_description ?? ''}
                placeholder="Defaults to the description"
            />
            <InputError message={errors.seo_description} />
        </div>
    {/if}

    <div>
        <p class="mb-2 text-xs font-medium text-muted-foreground">Google preview</p>
        <div class="rounded-md border bg-background p-3" aria-label="Google search result preview">
            <p class="truncate text-xs text-muted-foreground">{url}</p>
            <p class="mt-0.5 text-base leading-snug break-words text-[#1a0dab] dark:text-[#8ab4f8]">{truncate(title, 60)}</p>
            <p class="mt-1 text-xs leading-5 break-words text-muted-foreground">
                {description ? truncate(description, 160) : 'Google will pick text from the page. Add a description to control this.'}
            </p>
        </div>
    </div>

    <div>
        <p class="mb-2 text-xs font-medium text-muted-foreground">SEO checklist: {passed} of {checklist.length} done</p>
        <ul class="space-y-1.5 text-xs">
            {#each checklist as check (check.label)}
                <li class="flex items-start gap-2">
                    {#if check.ok}
                        <CircleCheck class="mt-px size-3.5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <span>{check.label}</span>
                    {:else}
                        <CircleAlert class="mt-px size-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                        <span>
                            {check.label}
                            {#if check.hint}<span class="text-muted-foreground"> · {check.hint}</span>{/if}
                        </span>
                    {/if}
                </li>
            {/each}
        </ul>
    </div>
</fieldset>
