<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { store as storeMedia, destroy as destroyMedia } from '@/routes/admin/products/media';

    type MediaItem = {
        id: number;
        type: string;
        is_main: boolean;
        alt_text: string | null;
        url?: string | null;
    };

    let { productId, media }: { productId: number; media: MediaItem[] } = $props();

    let uploadType = $state<'image' | 'video'>('image');
    let uploadSource = $state<'upload' | 'url'>('upload');
    let uploadUrl = $state('');
    let uploadFile: FileList | undefined = $state(undefined);
    let uploadIsMain = $state(false);

    function submitMedia(event: SubmitEvent) {
        event.preventDefault();

        const data = new FormData();
        data.append('source', uploadSource);
        data.append('type', uploadType);
        data.append('is_main', uploadIsMain ? '1' : '0');
        if (uploadSource === 'upload' && uploadFile?.[0]) {
            data.append('file', uploadFile[0]);
        } else {
            data.append('url', uploadUrl);
        }

        router.post(storeMedia.url(productId), data, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                uploadUrl = '';
                uploadFile = undefined;
                uploadIsMain = false;
            },
        });
    }

    function removeMedia(mediaId: number) {
        router.delete(destroyMedia.url({ product: productId, media: mediaId }), {
            preserveScroll: true,
        });
    }
</script>

<section class="space-y-4">
    <h2 class="text-lg font-semibold">Media</h2>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {#each media as item (item.id)}
            <div class="relative rounded-md border p-2 text-xs">
                {#if item.type === 'image' && item.url}
                    <img src={item.url} alt={item.alt_text ?? ''} class="mb-1 aspect-square w-full rounded object-cover" />
                {:else}
                    <div class="mb-1 flex aspect-square w-full items-center justify-center rounded bg-muted">
                        {item.type}
                    </div>
                {/if}
                {#if item.is_main}<span class="font-medium">Main</span>{/if}
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="mt-1 w-full text-destructive hover:text-destructive"
                    onclick={() => removeMedia(item.id)}
                >
                    <Trash2 class="size-3" /> Remove
                </Button>
            </div>
        {/each}
    </div>

    <form onsubmit={submitMedia} class="grid gap-3 rounded-md border p-4 text-sm">
        <div class="flex flex-wrap gap-x-4 gap-y-2">
            <label class="flex items-center gap-1">
                <input type="radio" bind:group={uploadSource} value="upload" /> Upload file
            </label>
            <label class="flex items-center gap-1">
                <input type="radio" bind:group={uploadSource} value="url" /> External URL
            </label>
        </div>

        <div class="flex flex-wrap gap-x-4 gap-y-2">
            <label class="flex items-center gap-1">
                <input type="radio" bind:group={uploadType} value="image" /> Image
            </label>
            <label class="flex items-center gap-1">
                <input type="radio" bind:group={uploadType} value="video" /> Video
            </label>
        </div>

        {#if uploadSource === 'upload'}
            <input class="min-w-0 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium" type="file" bind:files={uploadFile} accept={uploadType === 'image' ? 'image/*' : 'video/*'} />
        {:else}
            <Input placeholder="https://..." bind:value={uploadUrl} />
        {/if}

        <label class="flex items-center gap-2">
            <input type="checkbox" bind:checked={uploadIsMain} /> Set as main image
        </label>

        <Button type="submit">Add media</Button>
    </form>
</section>
