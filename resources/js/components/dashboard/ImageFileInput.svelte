<script lang="ts">
    import ImageIcon from '@lucide/svelte/icons/image';
    import TriangleAlert from '@lucide/svelte/icons/triangle-alert';
    import UploadCloud from '@lucide/svelte/icons/upload-cloud';
    import X from '@lucide/svelte/icons/x';
    import { onDestroy } from 'svelte';
    import { Button } from '@/components/ui/button';

    /**
     * Single-image upload field with a preview panel. Shows the image that will be
     * saved: the newly chosen file, or else the current one. The real file input
     * (submitted with the surrounding form) stays visually hidden.
     *
     * Optional: `maxSizeKb` rejects larger files before upload, `recommendedSizeKb`
     * warns that larger files will be compressed by the server, and `removeName`
     * adds a "Remove" button that submits `removeName=1` to clear the current image.
     */
    let {
        id,
        name = 'image_file',
        currentImage = null,
        alt = '',
        accept = 'image/*',
        maxSizeKb,
        recommendedSizeKb,
        removeName,
    }: {
        id: string;
        name?: string;
        currentImage?: string | null;
        alt?: string;
        accept?: string;
        maxSizeKb?: number;
        recommendedSizeKb?: number;
        removeName?: string;
    } = $props();

    let input = $state<HTMLInputElement | null>(null);
    let selected = $state<{ url: string; name: string; size: string; kb: number } | null>(null);
    let dragOver = $state(false);
    let removed = $state(false);
    let rejected = $state<string | null>(null);

    const preview = $derived(selected?.url ?? (removed ? null : currentImage));
    const oversized = $derived(!!selected && recommendedSizeKb !== undefined && selected.kb > recommendedSizeKb);

    function formatSize(bytes: number): string {
        return bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    function show(file: File | undefined) {
        if (selected) {
            URL.revokeObjectURL(selected.url);
        }

        rejected = null;

        if (file && maxSizeKb !== undefined && file.size > maxSizeKb * 1024) {
            rejected = `${file.name} is ${formatSize(file.size)}; the limit is ${formatSize(maxSizeKb * 1024)}.`;
            selected = null;
            if (input) {
                input.value = '';
            }

            return;
        }

        selected = file
            ? { url: URL.createObjectURL(file), name: file.name, size: formatSize(file.size), kb: file.size / 1024 }
            : null;

        if (file) {
            removed = false;
        }
    }

    function clearSelection() {
        show(undefined);
        if (input) {
            input.value = '';
        }
    }

    function handleDrop(event: DragEvent) {
        event.preventDefault();
        dragOver = false;
        const file = Array.from(event.dataTransfer?.files ?? []).find((item) => item.type.startsWith('image/'));
        if (!file || !input) {
            return;
        }
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        show(file);
    }

    onDestroy(() => show(undefined));
</script>

<div
    role="group"
    aria-label="Image upload"
    class="grid gap-3 rounded-md border p-3 transition-colors {dragOver ? 'border-primary bg-accent/40' : ''}"
    ondragover={(event) => {
        event.preventDefault();
        dragOver = true;
    }}
    ondragleave={() => (dragOver = false)}
    ondrop={handleDrop}
>
    <div class="flex aspect-video w-full items-center justify-center overflow-hidden rounded-md bg-muted">
        {#if preview}
            <img src={preview} {alt} class="size-full object-contain" />
        {:else}
            <div class="flex flex-col items-center gap-2 text-center text-sm text-muted-foreground">
                <ImageIcon class="size-8" />
                No image yet. Choose one or drop it here.
            </div>
        {/if}
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <Button type="button" variant="outline" size="sm" onclick={() => input?.click()}>
            <UploadCloud class="size-4" />
            {preview ? 'Replace image' : 'Choose image'}
        </Button>
        {#if selected}
            <span class="min-w-0 flex-1 truncate text-xs text-muted-foreground" title={selected.name}>
                {selected.name} · {selected.size}
            </span>
            <Button type="button" variant="ghost" size="sm" aria-label="Undo image selection" onclick={clearSelection}>
                <X class="size-4" />
            </Button>
        {:else if currentImage && !removed}
            <span class="text-xs text-muted-foreground">Current image</span>
            {#if removeName}
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    onclick={() => (removed = true)}
                >
                    Remove
                </Button>
            {/if}
        {:else if currentImage && removed}
            <span class="text-xs text-muted-foreground">Image will be removed when you save.</span>
            <Button type="button" variant="ghost" size="sm" onclick={() => (removed = false)}>Undo</Button>
        {/if}
    </div>

    {#if rejected}
        <p class="text-xs text-destructive" role="alert">{rejected}</p>
    {:else if oversized}
        <p class="flex items-start gap-1.5 text-xs text-muted-foreground" role="status">
            <TriangleAlert class="mt-px size-3.5 shrink-0 text-destructive" />
            Over the recommended {recommendedSizeKb} KB. The server will compress it to WebP at {recommendedSizeKb} KB or less,
            which may reduce its quality.
        </p>
    {/if}

    {#if removeName && removed && !selected}
        <input type="hidden" name={removeName} value="1" />
    {/if}

    <input
        bind:this={input}
        {id}
        {name}
        type="file"
        {accept}
        class="sr-only"
        onchange={(event) => show(event.currentTarget.files?.[0])}
    />
</div>
