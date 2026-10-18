<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';

    const name = $derived(page.props.name);
</script>

<div
    class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
>
    <AppLogoIcon class="size-5" />
</div>
<div class="ml-1 grid flex-1 text-left text-sm">
    <span class="truncate leading-tight font-semibold">{name}</span>
    <span class="truncate text-[11px] leading-tight opacity-60">
        Dental Accessories Supplies
    </span>
</div>
