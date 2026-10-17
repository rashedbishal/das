<script lang="ts">
    type Datum = { label: string; value: number };

    let { data }: { data: Datum[] } = $props();

    let hovered = $state<number | null>(null);

    const max = $derived(Math.max(1, ...data.map((d) => d.value)));
    const barWidth = 36;
    const gap = 20;
    const chartHeight = 140;
    const width = $derived(data.length * (barWidth + gap) - gap || barWidth);
</script>

{#if data.length === 0}
    <p class="py-8 text-center text-sm text-muted-foreground">
        No product data yet.
    </p>
{:else}
    <div class="overflow-x-auto">
        <svg
            viewBox="0 0 {width} {chartHeight + 36}"
            class="h-[176px] min-w-full"
            role="img"
            aria-label="Products per category"
        >
            <line
                x1="0"
                y1={chartHeight}
                x2={width}
                y2={chartHeight}
                class="stroke-border"
                stroke-width="1"
            />
            {#each data as d, i (d.label)}
                {@const h = Math.max(2, (d.value / max) * (chartHeight - 20))}
                {@const x = i * (barWidth + gap)}
                {@const isHovered = hovered === i}
                <g
                    role="img"
                    aria-label="{d.label}: {d.value}"
                    onpointerenter={() => (hovered = i)}
                    onpointerleave={() => (hovered = null)}
                    class="cursor-default"
                >
                    <text
                        x={x + barWidth / 2}
                        y={chartHeight - h - 8}
                        text-anchor="middle"
                        class={[
                            'fill-foreground text-[11px] font-medium transition-opacity',
                            isHovered ? 'opacity-100' : 'opacity-70',
                        ].join(' ')}
                    >
                        {d.value}
                    </text>
                    <rect
                        x={x}
                        y={chartHeight - h}
                        width={barWidth}
                        height={h}
                        rx="4"
                        class={[
                            'fill-primary transition-opacity',
                            isHovered ? 'opacity-100' : 'opacity-85',
                        ].join(' ')}
                    />
                    <rect
                        x={x}
                        y="0"
                        width={barWidth}
                        height={chartHeight}
                        fill="transparent"
                    />
                    <text
                        x={x + barWidth / 2}
                        y={chartHeight + 18}
                        text-anchor="middle"
                        class="fill-muted-foreground text-[10px]"
                    >
                        {d.label.length > 10 ? `${d.label.slice(0, 9)}…` : d.label}
                    </text>
                </g>
            {/each}
        </svg>
    </div>
{/if}
