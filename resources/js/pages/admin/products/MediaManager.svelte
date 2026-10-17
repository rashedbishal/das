<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import ImageOff from '@lucide/svelte/icons/image-off';
    import Info from '@lucide/svelte/icons/info';
    import Link from '@lucide/svelte/icons/link';
    import Loader2 from '@lucide/svelte/icons/loader-2';
    import CirclePlay from '@lucide/svelte/icons/circle-play';
    import Star from '@lucide/svelte/icons/star';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import UploadCloud from '@lucide/svelte/icons/upload-cloud';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import FormSelect from '@/components/dashboard/FormSelect.svelte';
    import { Alert, AlertDescription } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import {
        store as storeMedia,
        update as updateMedia,
        destroy as destroyMedia,
    } from '@/routes/admin/products/media';

    type MediaItem = {
        id: number;
        type: 'image' | 'video';
        source: 'upload' | 'url';
        is_main: boolean;
        alt_text: string | null;
        sort_order: number;
        url: string | null;
    };

    type MediaLimits = {
        image: {
            max: number;
            maxSizeKb: number;
            requiredDimension: number;
            formats: string[];
        };
        video: {
            max: number;
            maxSizeKb: number;
            maxSeconds: number;
            formats: string[];
        };
    };

    type QueueItem = {
        id: string;
        file: File;
        type: 'image' | 'video';
        previewUrl: string;
        status: 'pending' | 'uploading' | 'error';
        progress: number;
        error?: string;
    };

    let { productId, media, limits }: { productId: number; media: MediaItem[]; limits: MediaLimits } = $props();

    let queue = $state<QueueItem[]>([]);
    let dragOver = $state(false);
    let fileInput: HTMLInputElement | undefined = $state(undefined);
    let deleteTarget = $state<MediaItem | null>(null);
    let deleteDialogOpen = $state(false);

    let showUrlForm = $state(false);
    let urlType = $state('image');
    let urlValue = $state('');

    const imageCount = $derived(media.filter((m) => m.type === 'image').length);
    const videoCount = $derived(media.filter((m) => m.type === 'video').length);

    function formatSize(kb: number) {
        return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${kb} KB`;
    }

    function extensionOf(file: File) {
        return file.name.split('.').pop()?.toLowerCase() ?? '';
    }

    function imageDimensions(previewUrl: string): Promise<{ width: number; height: number }> {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve({ width: img.naturalWidth, height: img.naturalHeight });
            img.onerror = () => reject(new Error('Could not read the image.'));
            img.src = previewUrl;
        });
    }

    async function validate(file: File, previewUrl: string): Promise<{ type: 'image' | 'video'; error?: string }> {
        const type: 'image' | 'video' = file.type.startsWith('video/') ? 'video' : 'image';
        const rules = limits[type];
        const ext = extensionOf(file);

        if (!rules.formats.includes(ext)) {
            return { type, error: `Must be ${rules.formats.join('/').toUpperCase()}.` };
        }

        if (file.size > rules.maxSizeKb * 1024) {
            return { type, error: `Too large — max ${formatSize(rules.maxSizeKb)}.` };
        }

        const existing = type === 'image' ? imageCount : videoCount;
        const queued = queue.filter((q) => q.type === type && q.status !== 'error').length;

        if (existing + queued >= rules.max) {
            return { type, error: `Limit reached — up to ${rules.max} ${type}s per product.` };
        }

        if (type === 'image') {
            const size = limits.image.requiredDimension;
            try {
                const { width, height } = await imageDimensions(previewUrl);
                if (width !== size || height !== size) {
                    return { type, error: `Must be exactly ${size}×${size}px (this is ${width}×${height}px).` };
                }
            } catch {
                return { type, error: 'Could not read the image.' };
            }
        }

        return { type };
    }

    async function handleFiles(files: FileList | null) {
        if (!files) return;

        for (const file of Array.from(files)) {
            const previewUrl = URL.createObjectURL(file);
            const { type, error } = await validate(file, previewUrl);
            const item: QueueItem = {
                id: crypto.randomUUID(),
                file,
                type,
                previewUrl,
                status: error ? 'error' : 'pending',
                progress: 0,
                error,
            };
            queue.push(item);
            uploadNext();
        }
    }

    function uploadNext() {
        if (queue.some((q) => q.status === 'uploading')) return;

        const item = queue.find((q) => q.status === 'pending');
        if (!item) return;

        item.status = 'uploading';

        const data = new FormData();
        data.append('source', 'upload');
        data.append('type', item.type);
        data.append('file', item.file);

        router.post(storeMedia.url(productId), data, {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (event) => {
                item.progress = Math.round(event?.percentage ?? 0);
            },
            onSuccess: () => {
                URL.revokeObjectURL(item.previewUrl);
                queue = queue.filter((q) => q.id !== item.id);
                uploadNext();
            },
            onError: (errors) => {
                item.status = 'error';
                item.error = Object.values(errors)[0] ?? 'Upload failed.';
                uploadNext();
            },
        });
    }

    function dismissQueueItem(id: string) {
        const item = queue.find((q) => q.id === id);
        if (item) URL.revokeObjectURL(item.previewUrl);
        queue = queue.filter((q) => q.id !== id);
    }

    function onDrop(event: DragEvent) {
        event.preventDefault();
        dragOver = false;
        handleFiles(event.dataTransfer?.files ?? null);
    }

    function setMain(item: MediaItem) {
        router.patch(updateMedia.url({ product: productId, media: item.id }), {}, { preserveScroll: true });
    }

    function confirmDelete(item: MediaItem) {
        deleteTarget = item;
        deleteDialogOpen = true;
    }

    function removeMedia() {
        if (!deleteTarget) return;
        router.delete(destroyMedia.url({ product: productId, media: deleteTarget.id }), { preserveScroll: true });
    }

    function submitUrl(event: SubmitEvent) {
        event.preventDefault();
        if (!urlValue) return;

        const data = new FormData();
        data.append('source', 'url');
        data.append('type', urlType);
        data.append('url', urlValue);

        router.post(storeMedia.url(productId), data, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                urlValue = '';
                showUrlForm = false;
            },
        });
    }
</script>

<section class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold">Media</h2>
        <p class="text-sm text-muted-foreground">
            Photos and video customers see on this product's page.
        </p>
    </div>

    <Alert>
        <Info />
        <AlertDescription>
            <p>
                <strong>Images:</strong> WebP only, exactly {limits.image.requiredDimension}×{limits.image.requiredDimension}px
                (square) — other dimensions are rejected. Max {formatSize(limits.image.maxSizeKb)}. Up to
                {limits.image.max} images.
            </p>
            <p class="mt-1">
                <strong>Video:</strong> {limits.video.formats.join(', ').toUpperCase()} only, max
                {formatSize(limits.video.maxSizeKb)}, under {limits.video.maxSeconds}s. Up to
                {limits.video.max} videos.
            </p>
        </AlertDescription>
    </Alert>

    {#if media.length > 0}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            {#each media as item (item.id)}
                <div class="relative overflow-hidden rounded-lg border bg-card">
                    <div class="aspect-square bg-muted">
                        {#if item.type === 'image' && item.url}
                            <img src={item.url} alt={item.alt_text ?? ''} class="size-full object-cover" />
                        {:else if item.type === 'video' && item.url}
                            <video src={item.url} class="size-full object-cover" muted playsinline preload="metadata">
                                <track kind="captions" />
                            </video>
                        {:else}
                            <div class="flex size-full items-center justify-center text-muted-foreground">
                                <ImageOff class="size-6" />
                            </div>
                        {/if}
                    </div>

                    {#if item.type === 'video'}
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <CirclePlay class="size-8 text-white drop-shadow" />
                        </div>
                    {/if}

                    {#if item.is_main}
                        <span
                            class="absolute top-1.5 left-1.5 inline-flex items-center gap-1 rounded-full bg-primary px-2 py-0.5 text-[10px] font-medium text-primary-foreground"
                        >
                            <Star class="size-3 fill-current" /> Main
                        </span>
                    {/if}

                    <div class="flex items-center justify-between gap-1 border-t bg-background p-1">
                        {#if item.type === 'image' && !item.is_main}
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-6 px-1.5 text-[11px]"
                                onclick={() => setMain(item)}
                            >
                                <Star class="size-3" /> Set main
                            </Button>
                        {:else}
                            <span class="px-1.5 text-[11px] text-muted-foreground capitalize">{item.type}</span>
                        {/if}
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-6 px-1.5 text-destructive hover:text-destructive"
                            onclick={() => confirmDelete(item)}
                        >
                            <Trash2 class="size-3" />
                        </Button>
                    </div>
                </div>
            {/each}
        </div>
    {/if}

    {#if queue.length > 0}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            {#each queue as item (item.id)}
                <div class="relative overflow-hidden rounded-lg border bg-card">
                    <div class="aspect-square bg-muted">
                        {#if item.type === 'image'}
                            <img src={item.previewUrl} alt="" class="size-full object-cover opacity-70" />
                        {:else}
                            <video src={item.previewUrl} class="size-full object-cover opacity-70" muted>
                                <track kind="captions" />
                            </video>
                        {/if}
                    </div>
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-background/80 p-2 text-center">
                        {#if item.status === 'uploading'}
                            <Loader2 class="size-5 animate-spin text-primary" />
                            <span class="text-[11px] text-muted-foreground">{item.progress}%</span>
                        {:else if item.status === 'error'}
                            <span class="text-[11px] font-medium text-destructive">{item.error}</span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-6 px-1.5 text-[11px]"
                                onclick={() => dismissQueueItem(item.id)}
                            >
                                Dismiss
                            </Button>
                        {:else}
                            <span class="text-[11px] text-muted-foreground">Waiting…</span>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    {/if}

    <div
        role="group"
        class={[
            'flex flex-col items-center gap-2 rounded-lg border-2 border-dashed p-6 text-center transition-colors',
            dragOver ? 'border-primary bg-primary/5' : 'border-border',
        ].join(' ')}
        ondragover={(event) => {
            event.preventDefault();
            dragOver = true;
        }}
        ondragleave={() => (dragOver = false)}
        ondrop={onDrop}
    >
        <UploadCloud class="size-6 text-muted-foreground" />
        <p class="text-sm font-medium">Drag & drop images or videos here</p>
        <p class="text-xs text-muted-foreground">or</p>
        <Button type="button" variant="outline" size="sm" onclick={() => fileInput?.click()}>
            Browse files
        </Button>
        <input
            bind:this={fileInput}
            type="file"
            multiple
            accept="image/webp,video/mp4,video/quicktime"
            class="sr-only"
            onchange={(event) => {
                handleFiles(event.currentTarget.files);
                event.currentTarget.value = '';
            }}
        />
    </div>

    <div>
        <button
            type="button"
            class="inline-flex items-center gap-1 text-xs text-primary hover:underline"
            onclick={() => (showUrlForm = !showUrlForm)}
        >
            <Link class="size-3" />
            {showUrlForm ? 'Cancel' : 'Add from a URL instead'}
        </button>

        {#if showUrlForm}
            <form onsubmit={submitUrl} class="mt-2 flex flex-col gap-2 rounded-md border p-3 sm:flex-row">
                <FormSelect
                    bind:value={urlType}
                    options={[
                        { value: 'image', label: 'Image' },
                        { value: 'video', label: 'Video' },
                    ]}
                    class="sm:w-32"
                />
                <Input placeholder="https://example.com/photo.jpg" bind:value={urlValue} class="flex-1" />
                <Button type="submit" size="sm">Add</Button>
            </form>
        {/if}
    </div>
</section>

<ConfirmDialog
    bind:open={deleteDialogOpen}
    title="Remove this media?"
    description="This will permanently remove it from the product. This cannot be undone."
    onConfirm={removeMedia}
/>
