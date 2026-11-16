<script lang="ts">
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import ImagePlus from '@lucide/svelte/icons/image-plus';
    import Star from '@lucide/svelte/icons/star';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import TriangleAlert from '@lucide/svelte/icons/triangle-alert';
    import UploadCloud from '@lucide/svelte/icons/upload-cloud';
    import { onDestroy } from 'svelte';
    import { Alert, AlertDescription } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import { imageWarnings } from './media';

    /**
     * Image picker for the "New product" form: add images (button or drag & drop) in
     * several goes, reorder them, choose the main image and remove any before saving.
     * The chosen files are submitted as `images[]` with the main image first, which is
     * how the server picks the main image.
     */
    let {
        limits,
        errors = {},
    }: {
        limits: { max: number; maxSizeKb: number; targetSizeKb: number; requiredDimension: number; formats: string[] };
        errors?: Record<string, string>;
    } = $props();

    type Picked = { id: number; file: File; url: string };

    const ACCEPTED = ['image/webp', 'image/jpeg', 'image/png'];

    let items = $state<Picked[]>([]);
    let mainId = $state<number | null>(null);
    let warnings = $state<string[]>([]);
    let notice = $state<string | null>(null);
    let dragOver = $state(false);
    let nextId = 0;
    let browseInput = $state<HTMLInputElement | null>(null);
    let submitInput = $state<HTMLInputElement | null>(null);

    const full = $derived(items.length >= limits.max);
    const imageErrors = $derived(
        Object.entries(errors).filter(([key]) => key === 'images' || key.startsWith('images.')),
    );

    /** Files in one selection are added in file-name order (a, b, c / img2 before img10). */
    function byName(a: File, b: File): number {
        return a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' });
    }

    function add(list: FileList | null | undefined) {
        notice = null;
        const files = Array.from(list ?? []);
        const images = files.filter((file) => ACCEPTED.includes(file.type)).sort(byName);
        const room = limits.max - items.length;

        if (images.length < files.length) {
            notice = 'Only WebP, JPG and PNG images can be added here.';
        }
        if (images.length > room) {
            notice = `A product can have up to ${limits.max} images; ${images.length - room} not added.`;
        }

        const added = images.slice(0, Math.max(room, 0)).map((file) => ({ id: nextId++, file, url: URL.createObjectURL(file) }));
        items = [...items, ...added];
        if (mainId === null && items.length > 0) mainId = items[0].id;
    }

    function remove(item: Picked) {
        URL.revokeObjectURL(item.url);
        items = items.filter((entry) => entry.id !== item.id);
        if (mainId === item.id) mainId = items[0]?.id ?? null;
    }

    function move(index: number, by: -1 | 1) {
        const target = index + by;
        if (target < 0 || target >= items.length) return;
        const next = [...items];
        [next[index], next[target]] = [next[target], next[index]];
        items = next;
    }

    // Keep the real file input (the one the form submits) in step with the list,
    // main image first, and refresh the size/dimension warnings.
    $effect(() => {
        const ordered = [...items.filter((item) => item.id === mainId), ...items.filter((item) => item.id !== mainId)];

        if (submitInput) {
            const transfer = new DataTransfer();
            ordered.forEach((item) => transfer.items.add(item.file));
            submitInput.files = transfer.files;
        }

        const files = ordered.map((item) => item.file);
        imageWarnings(files, limits)
            .then((result) => (warnings = result))
            .catch(() => (warnings = []));
    });

    onDestroy(() => items.forEach((item) => URL.revokeObjectURL(item.url)));
</script>

<div class="grid gap-3 rounded-md border p-4">
    <div class="flex items-center justify-between gap-2">
        <span class="text-sm font-medium">Product images</span>
        <span class="text-xs text-muted-foreground">{items.length} of {limits.max}</span>
    </div>

    <!-- Submitted with the form; filled from the list above, never shown. -->
    <input bind:this={submitInput} type="file" name="images[]" multiple class="hidden" tabindex="-1" aria-hidden="true" />
    <input
        bind:this={browseInput}
        type="file"
        multiple
        accept={ACCEPTED.join(',')}
        class="sr-only"
        tabindex="-1"
        aria-hidden="true"
        onchange={(event) => {
            add(event.currentTarget.files);
            event.currentTarget.value = '';
        }}
    />

    {#if items.length > 0}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {#each items as item, index (item.id)}
                <div class="overflow-hidden rounded-md border {item.id === mainId ? 'border-primary ring-1 ring-primary' : ''}">
                    <div class="relative aspect-square bg-muted">
                        <img src={item.url} alt={item.file.name} class="size-full object-cover" />
                        {#if item.id === mainId}
                            <span
                                class="absolute top-1.5 left-1.5 inline-flex items-center gap-1 rounded-full bg-primary px-2 py-0.5 text-[10px] font-medium text-primary-foreground"
                            >
                                <Star class="size-3 fill-current" /> Main
                            </span>
                        {/if}
                    </div>
                    <p class="truncate border-t px-2 pt-1 text-[10px] text-muted-foreground" title={item.file.name}>
                        {index + 1}. {item.file.name}
                    </p>
                    <div class="flex items-center justify-between gap-0.5 p-1">
                        {#if item.id !== mainId}
                            <Button type="button" variant="ghost" size="sm" class="h-6 px-1.5 text-[11px]" onclick={() => (mainId = item.id)}>
                                <Star class="size-3" /> Set main
                            </Button>
                        {:else}
                            <span class="px-1.5 text-[11px] text-muted-foreground">Main image</span>
                        {/if}
                        <div class="flex">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-6 px-1"
                                aria-label="Move {item.file.name} left"
                                disabled={index === 0}
                                onclick={() => move(index, -1)}
                            >
                                <ChevronLeft class="size-3.5" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-6 px-1"
                                aria-label="Move {item.file.name} right"
                                disabled={index === items.length - 1}
                                onclick={() => move(index, 1)}
                            >
                                <ChevronRight class="size-3.5" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-6 px-1 text-destructive hover:text-destructive"
                                aria-label="Remove {item.file.name}"
                                onclick={() => remove(item)}
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    {/if}

    {#if !full}
        <div
            role="group"
            aria-label="Add product images"
            class="flex flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed p-6 text-center transition-colors {dragOver
                ? 'border-primary bg-primary/5'
                : 'border-border'}"
            ondragover={(event) => {
                event.preventDefault();
                dragOver = true;
            }}
            ondragleave={() => (dragOver = false)}
            ondrop={(event) => {
                event.preventDefault();
                dragOver = false;
                add(event.dataTransfer?.files);
            }}
        >
            <UploadCloud class="size-6 text-muted-foreground" />
            <p class="text-sm font-medium">Drag & drop images here</p>
            <p class="text-xs text-muted-foreground">or</p>
            <Button type="button" variant="outline" size="sm" onclick={() => browseInput?.click()}>
                <ImagePlus class="size-4" />
                {items.length > 0 ? 'Add more images' : 'Add images'}
            </Button>
        </div>
    {/if}

    <p class="text-xs text-muted-foreground">
        WebP, JPG or PNG (converted to WebP), up to {limits.max} images. Recommended: {limits.requiredDimension}×{limits.requiredDimension}px
        and {limits.targetSizeKb} KB or less. Other sizes are resized to fit, and files up to {limits.maxSizeKb / 1024} MB are accepted and
        compressed by the server. Use “Set main” to choose the image shown on product cards; the arrows change the order.
    </p>

    {#if notice}
        <p class="text-sm text-amber-700 dark:text-amber-400" role="status">{notice}</p>
    {/if}

    {#if warnings.length > 0}
        <Alert>
            <TriangleAlert />
            <AlertDescription>
                {#each warnings as warning (warning)}
                    <p>{warning}</p>
                {/each}
            </AlertDescription>
        </Alert>
    {/if}

    {#each imageErrors as [key, message] (key)}
        <p class="text-sm text-destructive">{message}</p>
    {/each}
</div>
