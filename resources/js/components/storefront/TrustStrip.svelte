<script lang="ts">
    import Headphones from '@lucide/svelte/icons/headphones';
    import Layers3 from '@lucide/svelte/icons/layers';
    import MessageSquare from '@lucide/svelte/icons/message-square';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';

    const benefits = [
        {
            icon: ShieldCheck,
            title: 'Professional-Grade Equipment',
            description: 'Thoughtfully selected for clinical work.',
        },
        {
            icon: Layers3,
            title: 'Multiple Trusted Brands',
            description: 'More choice for your practice.',
        },
        {
            icon: Headphones,
            title: 'Expert Product Guidance',
            description: 'Support with the details that matter.',
        },
        {
            icon: MessageSquare,
            title: 'Direct Sales Assistance',
            description: 'A real conversation, not a checkout.',
        },
    ];
</script>

<section class="border-b">
    <div
        class="container-wide grid grid-cols-2 gap-y-6 py-7 lg:grid-cols-4 lg:py-8"
    >
        {#each benefits as { icon: Icon, title, description }, i (title)}
            <div
                class="sf-reveal flex items-start gap-3 pr-3 lg:gap-4 {i > 0
                    ? 'lg:border-l lg:pl-7'
                    : ''}"
                style:--sf-i={i}
            >
                <Icon strokeWidth={1.4} class="size-6 shrink-0 text-primary" />
                <div>
                    <h2 class="text-[10px] font-semibold lg:text-[11px]">
                        {title}
                    </h2>
                    <p
                        class="mt-1.5 text-[9px] leading-relaxed text-muted-foreground lg:text-[10px]"
                    >
                        {description}
                    </p>
                </div>
            </div>
        {/each}
    </div>
</section>
