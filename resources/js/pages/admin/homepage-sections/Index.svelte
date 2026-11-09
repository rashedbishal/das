<script module lang="ts">
    import { index } from '@/routes/admin/homepage-sections';

    export const layout = {
        breadcrumbs: [{ title: 'Homepage banners', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import ImageIcon from '@lucide/svelte/icons/image';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import BulkActions from '@/components/dashboard/BulkActions.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import DatePicker from '@/components/dashboard/DatePicker.svelte';
    import ResponsiveTable from '@/components/dashboard/ResponsiveTable.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { bulk as bulkSections, destroy, store, update } from '@/routes/admin/homepage-sections';

    type Section = {
        id: number;
        type: 'banner' | 'section';
        title: string;
        subtitle: string | null;
        link_url: string | null;
        link_label: string | null;
        show_text: boolean;
        images: {
            desktop: string | null;
            tablet: string | null;
            mobile: string | null;
        };
        sort_order: number;
        is_active: boolean;
        starts_at: string | null;
        ends_at: string | null;
    };

    type Sizes = Record<'desktop' | 'tablet' | 'mobile', [number, number]>;

    let { sections, imageSizes }: { sections: Section[]; imageSizes: Sizes } =
        $props();

    let formOpen = $state(false);
    let editing = $state<Section | null>(null);
    let deleteTarget = $state<Section | null>(null);
    let deleteOpen = $state(false);
    let selectedIds = $state<number[]>([]);
    let bulkDeleteOpen = $state(false);
    let bulkError = $state<string | null>(null);
    let landscapePreview = $state<string | null>(null);
    let portraitPreview = $state<string | null>(null);

    const nextOrder = $derived(
        sections.reduce((max, s) => Math.max(max, s.sort_order), -1) + 1,
    );

    function openForm(section: Section | null) {
        editing = section;
        landscapePreview = null;
        portraitPreview = null;
        formOpen = true;
    }

    function preview(event: Event, set: (url: string | null) => void) {
        const file = (event.currentTarget as HTMLInputElement).files?.[0];
        set(file ? URL.createObjectURL(file) : null);
    }

    function askDelete(section: Section) {
        deleteTarget = section;
        deleteOpen = true;
    }

    function confirmDelete() {
        if (deleteTarget) {
            router.delete(destroy.url(deleteTarget.id));
        }
    }

    function runBulkAction(action: 'delete' | 'activate' | 'deactivate') {
        router.post(
            bulkSections.url(),
            { ids: selectedIds, action },
            {
                onSuccess: () => {
                    selectedIds = [];
                    bulkError = null;
                },
                onError: (errors) => {
                    bulkError = Object.values(errors).join(' ');
                    bulkDeleteOpen = false;
                },
            },
        );
    }

    function size(device: keyof Sizes) {
        return `${imageSizes[device][0]}×${imageSizes[device][1]}`;
    }
</script>

<AppHead title="Homepage banners" />

<div class="w-full p-4">
    <div class="mb-1 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Homepage banners</h1>
        <Button onclick={() => openForm(null)}>Add banner</Button>
    </div>
    <p class="mb-4 max-w-2xl text-sm text-muted-foreground">
        Banners rotate in the storefront hero, in order. Upload the original
        images; they are cropped and converted automatically for desktop ({size(
            'desktop',
        )}), tablet ({size('tablet')}) and mobile ({size('mobile')}).
    </p>

    <BulkActions
        count={selectedIds.length}
        error={bulkError}
        onClear={() => {
            selectedIds = [];
            bulkError = null;
        }}
    >
        {#snippet children()}
            <Button size="sm" onclick={() => runBulkAction('activate')}>Activate</Button>
            <Button size="sm" variant="outline" onclick={() => runBulkAction('deactivate')}>Deactivate</Button>
            <Button size="sm" variant="destructive" onclick={() => (bulkDeleteOpen = true)}>Delete</Button>
        {/snippet}
    </BulkActions>

    <ResponsiveTable
        items={sections}
        key={(section) => section.id}
        columns={[
            { key: 'title', label: 'Banner' },
            { key: 'status', label: 'Status' },
            { key: 'schedule', label: 'Schedule' },
            { key: 'order', label: 'Order' },
        ]}
        empty="No banners yet. The storefront shows its default hero until you add one."
        selectedKeys={selectedIds}
        onSelectionChange={(keys) => (selectedIds = keys.map(Number))}
    >
        {#snippet cell(section, column)}
            {#if column.key === 'title'}
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-10 w-[120px] shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted"
                    >
                        {#if section.images.desktop}
                            <img
                                src={section.images.desktop}
                                alt=""
                                class="size-full object-cover"
                            />
                        {:else}
                            <ImageIcon class="size-4 text-muted-foreground" />
                        {/if}
                    </div>
                    <span class="min-w-0 truncate">{section.title}</span>
                </div>
            {:else if column.key === 'status'}
                <Badge variant={section.is_active ? 'default' : 'secondary'}>
                    {section.is_active ? 'Active' : 'Hidden'}
                </Badge>
                {#if !section.images.desktop}
                    <Badge variant="outline">No image</Badge>
                {/if}
            {:else if column.key === 'schedule'}
                {#if section.starts_at || section.ends_at}
                    {section.starts_at ?? '…'} – {section.ends_at ?? '…'}
                {:else}
                    <span class="text-muted-foreground">Always</span>
                {/if}
            {:else}
                {section.sort_order}
            {/if}
        {/snippet}
        {#snippet actions(section)}
            <Button
                variant="ghost"
                size="sm"
                aria-label="Edit {section.title}"
                onclick={() => openForm(section)}
            >
                <Pencil class="size-4" />
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive"
                aria-label="Delete {section.title}"
                onclick={() => askDelete(section)}
            >
                <Trash2 class="size-4" />
            </Button>
        {/snippet}
    </ResponsiveTable>
</div>

<Dialog bind:open={formOpen}>
    <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-2xl">
        <DialogTitle>{editing ? 'Edit banner' : 'Add banner'}</DialogTitle>
        <DialogDescription>
            JPG, PNG or WebP up to 20 MB. Keep the subject near the centre;
            edges are cropped to fit each device.
        </DialogDescription>

        {#key editing?.id ?? 'new'}
            {@const section = editing}
            <Form
                {...section ? update.form(section.id) : store.form()}
                class="grid gap-5"
                onSuccess={() => (formOpen = false)}
            >
                {#snippet children({ errors, processing, progress })}
                    <input type="hidden" name="type" value="banner" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid content-start gap-2">
                            <Label for="landscape_image">
                                Landscape image
                                <span class="font-normal text-muted-foreground">
                                    · desktop & tablet
                                </span>
                            </Label>
                            <div
                                class="flex aspect-[3/1] items-center justify-center overflow-hidden rounded-md border bg-muted"
                            >
                                {#if landscapePreview ?? section?.images.desktop}
                                    <img
                                        src={landscapePreview ??
                                            section?.images.desktop}
                                        alt=""
                                        class="size-full object-cover"
                                    />
                                {:else}
                                    <ImageIcon
                                        class="size-5 text-muted-foreground"
                                    />
                                {/if}
                            </div>
                            <Input
                                id="landscape_image"
                                name="landscape_image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onchange={(e) =>
                                    preview(e, (url) => (landscapePreview = url))}
                            />
                            <p class="text-xs text-muted-foreground">
                                At least 1200×500, e.g. 1536×1024.
                            </p>
                            <InputError message={errors.landscape_image} />
                        </div>

                        <div class="grid content-start gap-2">
                            <Label for="portrait_image">
                                Portrait image
                                <span class="font-normal text-muted-foreground">
                                    · mobile
                                </span>
                            </Label>
                            <div
                                class="mx-auto flex aspect-[4/5] w-1/2 items-center justify-center overflow-hidden rounded-md border bg-muted sm:w-[45%]"
                            >
                                {#if portraitPreview ?? section?.images.mobile}
                                    <img
                                        src={portraitPreview ??
                                            section?.images.mobile}
                                        alt=""
                                        class="size-full object-cover"
                                    />
                                {:else}
                                    <ImageIcon
                                        class="size-5 text-muted-foreground"
                                    />
                                {/if}
                            </div>
                            <Input
                                id="portrait_image"
                                name="portrait_image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onchange={(e) =>
                                    preview(e, (url) => (portraitPreview = url))}
                            />
                            <p class="text-xs text-muted-foreground">
                                Optional, at least 800×1000, e.g. 1024×1536. If
                                left empty, mobile is cut from the landscape
                                image.
                            </p>
                            <InputError message={errors.portrait_image} />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="title">Headline</Label>
                        <Input
                            id="title"
                            name="title"
                            value={section?.title ?? ''}
                            required
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="subtitle">Supporting text</Label>
                        <Input
                            id="subtitle"
                            name="subtitle"
                            value={section?.subtitle ?? ''}
                        />
                        <InputError message={errors.subtitle} />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-[2fr_1fr]">
                        <div class="grid gap-2">
                            <Label for="link_url">Button link</Label>
                            <Input
                                id="link_url"
                                name="link_url"
                                type="url"
                                placeholder="https://…"
                                value={section?.link_url ?? ''}
                            />
                            <InputError message={errors.link_url} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="link_label">Button text</Label>
                            <Input
                                id="link_label"
                                name="link_label"
                                placeholder="Explore"
                                value={section?.link_label ?? ''}
                            />
                            <InputError message={errors.link_label} />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="starts_at">Show from</Label>
                            <DatePicker
                                id="starts_at"
                                name="starts_at"
                                value={section?.starts_at ?? ''}
                                clearable
                            />
                            <InputError message={errors.starts_at} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ends_at">Show until</Label>
                            <DatePicker
                                id="ends_at"
                                name="ends_at"
                                value={section?.ends_at ?? ''}
                                clearable
                            />
                            <InputError message={errors.ends_at} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="sort_order">Order</Label>
                            <Input
                                id="sort_order"
                                name="sort_order"
                                type="number"
                                min="0"
                                value={section?.sort_order ?? nextOrder}
                            />
                            <InputError message={errors.sort_order} />
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0" />
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                checked={section?.is_active ?? true}
                            />
                            Active
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="show_text" value="0" />
                            <input
                                type="checkbox"
                                name="show_text"
                                value="1"
                                checked={section?.show_text ?? true}
                            />
                            Show headline and button over the image
                        </label>
                    </div>

                    {#if progress}
                        <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full bg-primary transition-all"
                                style="width: {progress.percentage ?? 0}%"
                            ></div>
                        </div>
                    {/if}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onclick={() => (formOpen = false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing
                                ? 'Processing images…'
                                : section
                                  ? 'Save'
                                  : 'Add banner'}
                        </Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/key}
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={deleteOpen}
    title="Delete banner?"
    description={deleteTarget
        ? `This will permanently delete "${deleteTarget.title}" and its images. This cannot be undone.`
        : ''}
    onConfirm={confirmDelete}
/>

<ConfirmDialog
    bind:open={bulkDeleteOpen}
    title="Delete selected banners?"
    description={`This will permanently delete ${selectedIds.length} selected banners. This cannot be undone.`}
    onConfirm={() => runBulkAction('delete')}
/>
