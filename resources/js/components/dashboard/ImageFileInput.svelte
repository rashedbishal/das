<script lang="ts">
    import ImageIcon from '@lucide/svelte/icons/image';
    import UploadCloud from '@lucide/svelte/icons/upload-cloud';
    import X from '@lucide/svelte/icons/x';
    import { onDestroy } from 'svelte';
    import { Button } from '@/components/ui/button';

    /**
     * Single-image upload field with a preview panel. Shows the image that will be
     * saved: the newly chosen file, or else the current one. The real file input
     * (submitted with the surrounding form) stays visually hidden.
     */
    let {
        id,
        name = 'image_file',
        currentImage = null,
        alt = '',
    }: {
        id: string;
        name?: string;
        currentImage?: string | null;
        alt?: string;
    } = $props();

    let input = $state<HTMLInputElement | null>(null);
    let selected = $state<{ url: string; name: string; size: string } | null>(null);
    let dragOver = $state(false);

    const preview = $derived(selected?.url ?? currentImage);

    function formatSize(bytes: number): string {
        return bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    function show(file: File | undefined) {
        if (selected) {
            URL.revokeObjectURL(selected.url);
        }
        selected = file ? { url: URL.createObjectURL(file), name: file.name, size: formatSize(file.size) } : null;
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
        {:else if currentImage}
            <span class="text-xs text-muted-foreground">Current image</span>
        {/if}
    </div>

    <input
        bind:this={input}
        {id}
        {name}
        type="file"
        accept="image/*"
        class="sr-only"
        onchange={(event) => show(event.currentTarget.files?.[0])}
    />
</div>
