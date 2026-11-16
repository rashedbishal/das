<script lang="ts">
    import { onDestroy } from 'svelte';

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

    let selectedPreview = $state<string | null>(null);

    const preview = $derived(selectedPreview ?? currentImage);

    function releasePreview() {
        if (selectedPreview) {
            URL.revokeObjectURL(selectedPreview);
            selectedPreview = null;
        }
    }

    function handleChange(event: Event) {
        releasePreview();
        const file = (event.currentTarget as HTMLInputElement).files?.[0];
        if (file) {
            selectedPreview = URL.createObjectURL(file);
        }
    }

    onDestroy(releasePreview);
</script>

{#if preview}
    <img src={preview} {alt} class="h-16 w-16 rounded-md object-cover" />
{/if}
<input
    {id}
    {name}
    type="file"
    accept="image/*"
    onchange={handleChange}
    class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium"
/>
