<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';

    let {
        eyebrow,
        title,
        description,
        href,
        label = 'View all equipment',
    }: {
        eyebrow: string;
        title: string;
        description?: string;
        href?: string;
        label?: string;
    } = $props();
</script>

<div class="mb-8 flex items-end justify-between gap-6 lg:mb-10">
    <div>
        <p class="eyebrow mb-3">{eyebrow}</p>
        <h2 class="section-heading">{title}</h2>
        {#if description}
            <p class="section-intro text-xs">{description}</p>
        {/if}
    </div>
    {#if href}
        <Link
            {href}
            class="hidden items-center gap-3 pb-1 text-[11px] font-semibold whitespace-nowrap hover:text-primary sm:flex"
        >
            {label}
            <ArrowUpRight class="size-4 text-primary" />
        </Link>
    {/if}
</div>
