<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
</script>

<span class="inline-flex flex-col items-start gap-1">
    <span
        class="inline-flex items-center gap-2.5 text-[25px] font-extrabold tracking-[1.5px]"
    >
        <span
            class="relative flex size-8 items-center justify-center rounded-[7px] border-[2px] border-primary text-primary"
        >
            <span class="text-[23px] leading-none font-bold">D</span>
            <Plus
                class="absolute top-2 -left-1.5 size-3.5 bg-background"
                strokeWidth={3}
            />
        </span>
        {page.props.siteSettings.name}
        <span class="text-[27px] leading-none text-primary">.</span>
    </span>
    <span
        class="text-[9px] leading-none font-semibold tracking-[1px] whitespace-nowrap uppercase opacity-60"
    >
        Dental Accessories Supplies
    </span>
</span>
